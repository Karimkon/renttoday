<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Apartment;
use App\Models\Landlord;
use App\Models\LatePaymentFee;
use App\Services\PesapalService;
use Carbon\Carbon;

class AdminPaymentController extends Controller
{
    protected $pesapalService;

    public function __construct(PesapalService $pesapalService)
    {
        $this->pesapalService = $pesapalService;
    }

    public function index(Request $request)
    {
        $query = Payment::with(['tenant', 'apartment.landlord']);

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by month
        if ($request->filled('month')) {
            $query->where('month', 'like', $request->month . '%');
        }

        $payments = $query->orderBy('month', 'desc')->paginate(20);
        $totalCollected = Payment::where('status', 'paid')->sum('amount');

        return view('admin.payments.index', compact('payments', 'totalCollected'));
    }

    public function create()
    {
        $tenants = Tenant::with(['apartment.landlord'])->get();
        return view('admin.payments.create', compact('tenants'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'month' => 'required|date_format:Y-m',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,pesapal,bank_transfer,mobile_money',
            'includes_gym' => 'nullable|boolean',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
        ]);

        $tenant = Tenant::with('apartment')->findOrFail($data['tenant_id']);
        $apartment = $tenant->apartment;

        if (!$apartment) {
            return back()->with('error', 'Selected tenant is not assigned to any apartment.');
        }

        // Handle different payment methods
        if ($data['payment_method'] === 'pesapal') {
            return $this->initiatePesapalPayment($tenant, $apartment, $data);
        } else {
            return $this->processManualPayment($tenant, $apartment, $data);
        }
    }

    /**
     * Process cash/bank/mobile money payments immediately
     */
    private function processManualPayment($tenant, $apartment, $data)
    {
        $rent = $apartment->rent;
        $totalAmount = $data['amount'] + ($tenant->credit_balance ?? 0);

        // Calculate months covered and remainder
        $monthsCovered = floor($totalAmount / $rent);
        $remainder = $totalAmount - ($monthsCovered * $rent);

        $startMonth = Carbon::createFromFormat('Y-m', $data['month']);

        $processedMonths = 0;

        for ($i = 0; $i < $monthsCovered; $i++) {
            $paymentMonth = $startMonth->copy()->addMonths($i)->format('Y-m');

            // Check if payment already exists for this month
            $exists = Payment::where('tenant_id', $tenant->id)
                ->whereYear('month', substr($paymentMonth, 0, 4))
                ->whereMonth('month', substr($paymentMonth, 5, 2))
                ->exists();

            if (!$exists) {
                Payment::create([
                    'tenant_id' => $tenant->id,
                    'apartment_id' => $apartment->id,
                    'month' => $paymentMonth . '-01',
                    'amount' => $rent,
                    'payment_method' => $data['payment_method'],
                    'reference_number' => $data['reference_number'],
                    'includes_gym' => $data['includes_gym'] ?? false,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'processed_by' => auth()->id(),
                    'notes' => $data['notes'] ?? null
                ]);

                $processedMonths++;

                // Clear any late fees for this month
                $this->clearLateFees($tenant->id, $paymentMonth);
            }
        }

        // Update tenant credit balance
        $tenant->update(['credit_balance' => $remainder]);

        $message = "Manual payment processed for {$processedMonths} month(s). ";
        $message .= $remainder > 0 ? "Remaining credit: UGX " . number_format($remainder) : "No credit balance.";

        return redirect()->route('admin.payments.index')
                         ->with('success', $message);
    }

    /**
     * Initiate Pesapal payment (admin-initiated for tenant)
     */
    private function initiatePesapalPayment($tenant, $apartment, $data)
    {
        try {
            // Calculate total amount including any late fees
            $lateFees = LatePaymentFee::where('tenant_id', $tenant->id)
                ->unpaid()
                ->sum('amount');

            $totalAmount = $data['amount'] + $lateFees;

            $orderData = [
                'id' => 'ADM-' . uniqid(),
                'currency' => 'UGX',
                'amount' => $totalAmount,
                'description' => "Rent Payment - {$apartment->number} - {$data['month']}",
                'callback_url' => route('admin.payments.pesapal-callback'),
                'notification_id' => config('pesapal.notification_id'),
                'billing_address' => [
                    'email_address' => $tenant->email,
                    'phone_number' => $tenant->phone,
                    'country_code' => 'UG'
                ]
            ];

            // Store payment intent in session
            session([
                'admin_payment_intent' => [
                    'tenant_id' => $tenant->id,
                    'apartment_id' => $apartment->id,
                    'month' => $data['month'],
                    'base_amount' => $data['amount'],
                    'late_fees' => $lateFees,
                    'total_amount' => $totalAmount,
                    'includes_gym' => $data['includes_gym'] ?? false,
                    'processed_by' => auth()->id(),
                    'notes' => $data['notes'] ?? null
                ]
            ]);

            $response = $this->pesapalService->submitOrder($orderData);

            if (isset($response['redirect_url'])) {
                // Create pending payment record
                Payment::create([
                    'tenant_id' => $tenant->id,
                    'apartment_id' => $apartment->id,
                    'month' => $data['month'] . '-01',
                    'amount' => $data['amount'],
                    'payment_method' => 'pesapal',
                    'includes_gym' => $data['includes_gym'] ?? false,
                    'status' => 'pending',
                    'order_tracking_id' => $response['order_tracking_id'] ?? null,
                    'processed_by' => auth()->id(),
                    'notes' => 'Admin-initiated Pesapal payment'
                ]);

                return redirect($response['redirect_url']);
            }

            throw new \Exception('Pesapal payment initiation failed');

        } catch (\Exception $e) {
            \Log::error('Admin Pesapal payment error: ' . $e->getMessage());
            return back()->with('error', 'Failed to initiate Pesapal payment: ' . $e->getMessage());
        }
    }

    /**
     * Handle Pesapal callback for admin-initiated payments
     */
    public function pesapalCallback(Request $request)
    {
        $paymentIntent = session('admin_payment_intent');
        
        if (!$paymentIntent) {
            return redirect()->route('admin.payments.index')
                ->with('error', 'Payment session expired.');
        }

        $orderTrackingId = $request->input('OrderTrackingId');
        $status = $this->pesapalService->getTransactionStatus($orderTrackingId);

        if ($status && $status['status_code'] == 1) {
            // Payment successful
            $this->processAdminPesapalSuccess($paymentIntent, $orderTrackingId);
            
            return redirect()->route('admin.payments.index')
                ->with('success', 'Pesapal payment completed successfully!');
        }

        // Payment failed or pending
        $this->updatePendingPayment($paymentIntent['tenant_id'], $paymentIntent['month'], 'failed');
        
        return redirect()->route('admin.payments.index')
            ->with('error', 'Pesapal payment was not completed.');
    }

    /**
     * Process successful admin-initiated Pesapal payment
     */
    private function processAdminPesapalSuccess($paymentIntent, $orderTrackingId)
    {
        $tenant = Tenant::find($paymentIntent['tenant_id']);
        $apartment = Apartment::find($paymentIntent['apartment_id']);
        
        $rent = $apartment->rent;
        $totalAmount = $paymentIntent['base_amount'] + ($tenant->credit_balance ?? 0);

        // Calculate months covered
        $monthsCovered = floor($totalAmount / $rent);
        $remainder = $totalAmount - ($monthsCovered * $rent);

        $startMonth = Carbon::createFromFormat('Y-m', $paymentIntent['month']);

        $processedMonths = 0;

        for ($i = 0; $i < $monthsCovered; $i++) {
            $paymentMonth = $startMonth->copy()->addMonths($i)->format('Y-m');

            // Update or create payment record
            Payment::updateOrCreate(
                [
                    'tenant_id' => $paymentIntent['tenant_id'],
                    'apartment_id' => $paymentIntent['apartment_id'],
                    'month' => $paymentMonth . '-01'
                ],
                [
                    'amount' => $rent,
                    'payment_method' => 'pesapal',
                    'includes_gym' => $paymentIntent['includes_gym'],
                    'status' => 'paid',
                    'paid_at' => now(),
                    'order_tracking_id' => $orderTrackingId,
                    'processed_by' => $paymentIntent['processed_by'],
                    'notes' => $paymentIntent['notes']
                ]
            );

            $processedMonths++;

            // Clear late fees for this month
            $this->clearLateFees($paymentIntent['tenant_id'], $paymentMonth);
        }

        // Update tenant credit balance
        $tenant->update(['credit_balance' => $remainder]);

        // Clear session
        session()->forget('admin_payment_intent');
    }

    /**
     * Clear late fees for a specific month
     */
    private function clearLateFees($tenantId, $month)
    {
        LatePaymentFee::where('tenant_id', $tenantId)
            ->where('month', $month)
            ->unpaid()
            ->update(['status' => 'paid', 'paid_at' => now()]);
    }

    /**
     * Update pending payment status
     */
    private function updatePendingPayment($tenantId, $month, $status)
    {
        Payment::where('tenant_id', $tenantId)
            ->where('month', 'like', $month . '%')
            ->where('status', 'pending')
            ->update(['status' => $status]);
    }

    public function edit(Payment $payment)
    {
        $tenants = Tenant::with('apartment')->get();
        return view('admin.payments.edit', compact('payment', 'tenants'));
    }

    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'month' => 'required|date_format:Y-m',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,pesapal,bank_transfer,mobile_money',
            'includes_gym' => 'nullable|boolean',
            'reference_number' => 'nullable|string|max:100',
            'status' => 'required|in:pending,paid,failed,refunded'
        ]);

        $tenant = Tenant::with('apartment')->findOrFail($data['tenant_id']);
        
        if (!$tenant->apartment) {
            return back()->with('error', 'Selected tenant is not assigned to any apartment.');
        }

        $data['apartment_id'] = $tenant->apartment->id;
        $data['month'] = $data['month'] . '-01';

        // If marking as paid, set paid_at timestamp
        if ($data['status'] === 'paid' && $payment->status !== 'paid') {
            $data['paid_at'] = now();
            $data['processed_by'] = auth()->id();
        }

        $payment->update($data);

        return redirect()->route('admin.payments.index')
                         ->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return redirect()->route('admin.payments.index')
                         ->with('success', 'Payment removed successfully.');
    }

    /**
     * Quick mark as paid action
     */
    public function markAsPaid(Payment $payment)
    {
        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'processed_by' => auth()->id(),
            'payment_method' => 'cash' // Default for quick mark
        ]);

        return back()->with('success', 'Payment marked as paid.');
    }

    /**
     * View payment details with landlord info
     */
    public function show(Payment $payment)
    {
        $payment->load(['tenant', 'apartment.landlord']);
        return view('admin.payments.show', compact('payment'));
    }
}