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
use App\Services\RentReminderService;
use Illuminate\Support\Facades\Log;

class AdminPaymentController extends Controller
{
    protected $pesapalService;
    protected $reminderService;

    public function __construct(PesapalService $pesapalService, RentReminderService $reminderService)
    {
        $this->pesapalService = $pesapalService;
        $this->reminderService = $reminderService;
    }

    public function index(Request $request)
    {
        $query = Payment::with(['tenant', 'apartment.landlord', 'processedBy']);

        // Extended search filters
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('month')) {
            $query->where('month', 'like', $request->month . '%');
        }

        // NEW: Search by tenant name
        if ($request->filled('tenant_search')) {
            $query->whereHas('tenant', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->tenant_search . '%');
            });
        }

        // NEW: Search by apartment number
        if ($request->filled('apartment_search')) {
            $query->whereHas('apartment', function($q) use ($request) {
                $q->where('number', 'like', '%' . $request->apartment_search . '%');
            });
        }

        // NEW: Search by landlord name
        if ($request->filled('landlord_search')) {
            $query->whereHas('apartment.landlord', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->landlord_search . '%');
            });
        }

        // NEW: Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // NEW: Filter by amount range
        if ($request->filled('amount_min')) {
            $query->where('amount', '>=', $request->amount_min);
        }

        if ($request->filled('amount_max')) {
            $query->where('amount', '<=', $request->amount_max);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);
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
            'reference_number' => 'nullable|string|max:100',
            'actual_payment_date' => 'nullable|date',
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
   // In your AdminPaymentController - update the processManualPayment method
private function processManualPayment($tenant, $apartment, $data)
{
    $rent = $apartment->rent;
    $totalAmount = $data['amount'];

    // Calculate how many full months are covered
    $monthsCovered = floor($totalAmount / $rent);
    $remainder = $totalAmount - ($monthsCovered * $rent);

    $startMonth = Carbon::createFromFormat('Y-m', $data['month']);
    $processedMonths = 0;

     // Use actual payment date or current date
    $paymentDate = $data['actual_payment_date'] ? Carbon::createFromFormat('Y-m-d', $data['actual_payment_date']) : now();

    // Create payments for each covered month
    for ($i = 0; $i < $monthsCovered; $i++) {
        $paymentMonth = $startMonth->copy()->addMonths($i)->format('Y-m');

        // Check if payment already exists for this month
        $exists = Payment::where('tenant_id', $tenant->id)
            ->whereYear('month', substr($paymentMonth, 0, 4))
            ->whereMonth('month', substr($paymentMonth, 5, 2))
            ->exists();

        if (!$exists) {
            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'apartment_id' => $apartment->id,
                'month' => $paymentMonth . '-01',
                'amount' => $rent,
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'],
                'includes_gym' => $data['includes_gym'] ?? false,
                'status' => 'paid',
                'paid_at' => $paymentDate,
                'actual_payment_date' => $data['actual_payment_date'] ?? $paymentDate->format('Y-m-d'),
                'processed_by' => auth()->id(),
                'notes' => $data['notes'] ?? ($i > 0 ? 'Advance payment' : null)
            ]);

            $processedMonths++;
        }
    }

    // If there's a remainder, create a partial payment for the first month
    if ($remainder > 0 && $processedMonths == 0) {
        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'apartment_id' => $apartment->id,
            'month' => $data['month'] . '-01',
            'amount' => $remainder,
            'payment_method' => $data['payment_method'],
            'reference_number' => $data['reference_number'],
            'includes_gym' => $data['includes_gym'] ?? false,
            'status' => 'paid',
            'paid_at' => now(),
            'processed_by' => auth()->id(),
            'notes' => $data['notes'] ?? 'Partial payment'
        ]);

        $processedMonths = 1;
    }

    $message = "Payment processed for {$processedMonths} month(s). ";
    if ($monthsCovered > 1) {
        $message .= "Advance payment covering {$monthsCovered} months.";
    } elseif ($remainder > 0 && $remainder < $rent) {
        $message .= "Partial payment of UGX " . number_format($remainder) . " recorded.";
    }

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
            Log::error('Admin Pesapal payment error: ' . $e->getMessage());
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
            $payment = Payment::updateOrCreate(
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

            // Send payment confirmation SMS
            try {
                $this->reminderService->sendPaymentConfirmation($payment);
            } catch (\Exception $e) {
                Log::error('Failed to send payment confirmation SMS: ' . $e->getMessage());
            }

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
            'actual_payment_date' => 'nullable|date',
            'status' => 'required|in:pending,paid,failed,refunded',
            'notes' => 'nullable|string' // ADDED: Allow notes editing
        ]);

        $tenant = Tenant::with('apartment')->findOrFail($data['tenant_id']);
        
        if (!$tenant->apartment) {
            return back()->with('error', 'Selected tenant is not assigned to any apartment.');
        }

        $data['apartment_id'] = $tenant->apartment->id;
        $data['month'] = $data['month'] . '-01';

        // Track if status is changing to paid
        $wasPaid = $payment->status === 'paid';
        $isNowPaid = $data['status'] === 'paid';

        // If marking as paid and it wasn't paid before, set paid_at timestamp
        if ($isNowPaid && !$wasPaid) {
            $data['paid_at'] = now();
            $data['processed_by'] = auth()->id();
            
            // Send payment confirmation SMS only if it's a new payment
            try {
                $this->reminderService->sendPaymentConfirmation($payment);
            } catch (\Exception $e) {
                Log::error('Failed to send payment confirmation SMS: ' . $e->getMessage());
            }
        }

        // If changing from paid to another status, clear paid_at
        if ($wasPaid && !$isNowPaid) {
            $data['paid_at'] = null;
            $data['processed_by'] = null;
        }

         // Handle actual payment date
    if ($data['status'] === 'paid') {
        $data['paid_at'] = now();
        $data['actual_payment_date'] = $data['actual_payment_date'] ?? now()->format('Y-m-d');
        $data['processed_by'] = auth()->id();
    } else {
        $data['paid_at'] = null;
        $data['actual_payment_date'] = null;
        $data['processed_by'] = null;
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

        // Send payment confirmation SMS
        try {
            $this->reminderService->sendPaymentConfirmation($payment);
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation SMS: ' . $e->getMessage());
        }

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

    /**
     * Send manual rent reminder to specific tenant
     */
    public function sendManualReminder(Tenant $tenant)
    {
        try {
            $success = $this->reminderService->sendRentReminderToTenant($tenant);
            
            if ($success) {
                return back()->with('success', 'Rent reminder sent successfully to ' . $tenant->name);
            } else {
                return back()->with('error', 'Failed to send rent reminder to ' . $tenant->name);
            }
        } catch (\Exception $e) {
            Log::error('Manual rent reminder error: ' . $e->getMessage());
            return back()->with('error', 'Error sending reminder: ' . $e->getMessage());
        }
    }
}