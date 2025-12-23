<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Apartment;
use App\Models\Landlord;
use App\Models\LatePaymentFee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
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

    /**
     * Get payment status for a tenant for a specific month (AJAX endpoint)
     * Returns remaining balance and payment info
     */
    public function getPaymentStatus(Request $request)
    {
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'month' => 'required|date_format:Y-m'
        ]);

        $tenant = Tenant::with('apartment')->findOrFail($request->tenant_id);
        $apartment = $tenant->apartment;

        if (!$apartment) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant has no apartment assigned'
            ]);
        }

        $monthlyRent = $apartment->rent;

        // Get existing payments for this month
        $existingPayments = $apartment->payments()
            ->where('status', 'paid')
            ->get()
            ->filter(function($payment) use ($request) {
                return $payment->coversMonth($request->month);
            });

        $totalPaid = $existingPayments->sum(function($payment) use ($request) {
            return $payment->getAmountForMonth($request->month);
        });

        $remainingBalance = max(0, $monthlyRent - $totalPaid);
        $isFullyPaid = $totalPaid >= $monthlyRent;

        return response()->json([
            'success' => true,
            'data' => [
                'monthly_rent' => $monthlyRent,
                'total_paid' => $totalPaid,
                'remaining_balance' => $remainingBalance,
                'is_fully_paid' => $isFullyPaid,
                'suggested_amount' => $remainingBalance > 0 ? $remainingBalance : $monthlyRent,
                'payment_count' => $existingPayments->count(),
                'message' => $isFullyPaid
                    ? 'This month is fully paid (UGX ' . number_format($totalPaid) . ')'
                    : ($totalPaid > 0
                        ? 'Partial payment made: UGX ' . number_format($totalPaid) . '. Remaining: UGX ' . number_format($remainingBalance)
                        : 'No payments yet for this month')
            ]
        ]);
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
            'notes' => 'nullable|string',
            'paid_to_landlord_directly' => 'nullable|boolean'
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

private function processManualPayment($tenant, $apartment, $data)
{
    $rent = $apartment->rent;
    $totalAmount = $data['amount'];
    $paymentDate = $data['actual_payment_date']
        ? Carbon::createFromFormat('Y-m-d', $data['actual_payment_date'])
        : now();
    $targetMonth = $data['month'];
    $startMonth = Carbon::createFromFormat('Y-m', $targetMonth);
    $paidToLandlordDirectly = isset($data['paid_to_landlord_directly']) && $data['paid_to_landlord_directly'];

    // Log the input for debugging
    \Log::info('=== PAYMENT PROCESSING START ===');
    \Log::info('Tenant: ' . $tenant->name);
    \Log::info('Apartment: ' . $apartment->number);
    \Log::info('Rent: ' . number_format($rent));
    \Log::info('Total Amount: ' . number_format($totalAmount));
    \Log::info('Target Month: ' . $targetMonth);

    // Calculate full months and remainder
    $fullMonths = floor($totalAmount / $rent);
    $remainder = $totalAmount - ($fullMonths * $rent); // Better calculation

    \Log::info('Full Months: ' . $fullMonths);
    \Log::info('Remainder: ' . number_format($remainder));

    $createdPayments = [];
    $message = "";

    // Create separate payments for EACH month
    $currentMonth = $startMonth->copy();
    
    // 1. Create full month payments
    for ($i = 0; $i < $fullMonths; $i++) {
        $paymentMonth = $currentMonth->copy()->addMonths($i);
        
        \Log::info('Creating payment for month ' . ($i + 1) . ': ' . $paymentMonth->format('F Y'));
        
        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'apartment_id' => $apartment->id,
            'month' => $paymentMonth->format('Y-m') . '-01',
            'amount' => $rent,
            'payment_method' => $data['payment_method'],
            'reference_number' => $data['reference_number'] ?? null,
            'status' => 'paid',
            'paid_at' => $paymentDate,
            'actual_payment_date' => $data['actual_payment_date'] ?? $paymentDate->format('Y-m-d'),
            'processed_by' => auth()->id(),
            'notes' => $fullMonths > 1 ?
                "Part of advance payment: UGX " . number_format($totalAmount) . " for " . $fullMonths . " months" :
                ($data['notes'] ?? null),
            'is_advance_payment' => $fullMonths > 1,
            'original_amount' => $fullMonths > 1 ? $totalAmount : null,
            'paid_to_landlord_directly' => $paidToLandlordDirectly
        ]);
        
        $createdPayments[] = $payment;
        \Log::info('Created payment ID: ' . $payment->id . ' for ' . $paymentMonth->format('F Y'));
    }
    
    // 2. Create partial payment for remainder (if any)
    if ($remainder > 0) {
        $partialMonth = $currentMonth->copy()->addMonths($fullMonths);
        
        \Log::info('Creating partial payment for: ' . $partialMonth->format('F Y') . ' Amount: ' . number_format($remainder));
        
        $partialPayment = Payment::create([
            'tenant_id' => $tenant->id,
            'apartment_id' => $apartment->id,
            'month' => $partialMonth->format('Y-m') . '-01',
            'amount' => $remainder,
            'payment_method' => $data['payment_method'],
            'reference_number' => $data['reference_number'] ?? null,
            'status' => 'paid',
            'paid_at' => $paymentDate,
            'actual_payment_date' => $data['actual_payment_date'] ?? $paymentDate->format('Y-m-d'),
            'processed_by' => auth()->id(),
            'notes' => "Partial payment from advance of UGX " . number_format($totalAmount),
            'is_advance_payment' => false,
            'paid_to_landlord_directly' => $paidToLandlordDirectly
        ]);
        
        $createdPayments[] = $partialPayment;
        \Log::info('Created partial payment ID: ' . $partialPayment->id);
    }
    
    \Log::info('Total payments created: ' . count($createdPayments));

    // Build success message
    if ($fullMonths >= 2) {
        $message = "✅ ADVANCE PAYMENT PROCESSED: {$fullMonths} months paid in advance";
        if ($remainder > 0) {
            $nextMonth = $startMonth->copy()->addMonths($fullMonths);
            $message .= " + UGX " . number_format($remainder) . " partial for " . $nextMonth->format('F Y');
        }
        
        // List all created months
        $monthList = [];
        foreach ($createdPayments as $p) {
            $month = Carbon::parse($p->month)->format('F Y');
            $amount = number_format($p->amount);
            $monthList[] = "{$month}: UGX {$amount}";
        }
        $message .= "\n\n📅 Payment Breakdown:\n" . implode("\n", $monthList);
        
    } elseif ($fullMonths == 1 && $remainder == 0) {
        $message = "✅ Payment processed for " . $startMonth->format('F Y');
    } elseif ($fullMonths == 1 && $remainder > 0) {
        $message = "✅ Payment processed: 1 month + UGX " . number_format($remainder) . " partial advance";
    } elseif ($fullMonths == 0 && $remainder > 0) {
        $message = "✅ Partial payment of UGX " . number_format($totalAmount) . " recorded for " . $startMonth->format('F Y');
    }

    // Send SMS for first payment only
    if (!empty($createdPayments) && isset($data['send_sms']) && $data['send_sms']) {
        try {
            $this->reminderService->sendPaymentConfirmation($createdPayments[0]);
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation SMS: ' . $e->getMessage());
        }
    }

    return redirect()->route('admin.payments.index')
                     ->with('success', $message);
}

    /**
     * Process partial payments to complete rent
     */
    public function addPartialPayment(Request $request, Tenant $tenant)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'month' => 'required|date_format:Y-m',
            'payment_method' => 'required'
        ]);

        $apartment = $tenant->apartment;
        if (!$apartment) {
            return back()->with('error', 'Tenant has no assigned apartment.');
        }

        $month = $request->month;
        $existingPayments = $apartment->payments()->forMonth($month)->where('status', 'paid')->get();
        $totalPaid = $existingPayments->sum(function($payment) use ($month) {
            return $payment->getAmountForMonth($month);
        });

        $remaining = $apartment->rent - $totalPaid;

        if ($request->amount > $remaining) {
            return back()->with('error', "Amount exceeds remaining balance. Remaining: UGX " . number_format($remaining));
        }

        // Process the partial payment
        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'apartment_id' => $apartment->id,
            'month' => $month . '-01',
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'status' => 'paid',
            'paid_at' => now(),
            'processed_by' => auth()->id(),
            'notes' => 'Additional partial payment',
            'is_advance_payment' => false
        ]);

        $newTotal = $totalPaid + $request->amount;
        $message = "Partial payment of UGX " . number_format($request->amount) . " added. ";
        $message .= "Total paid for " . Carbon::createFromFormat('Y-m', $month)->format('F Y') . ": UGX " . number_format($newTotal);

        if ($newTotal >= $apartment->rent) {
            $message .= " - Rent now fully paid!";
        } else {
            $message .= " - Remaining: UGX " . number_format($apartment->rent - $newTotal);
        }

        return redirect()->route('admin.payments.index')->with('success', $message);
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
    
    // If this is an advance payment, get the original amount for display
    if ($payment->is_advance_payment) {
        $originalAmount = $payment->original_amount;
        if ($originalAmount && $originalAmount > $payment->amount) {
            // Show the original full amount in the form
            $payment->original_amount_display = $originalAmount;
        }
    }
    
    return view('admin.payments.edit', compact('payment', 'tenants'));
}

public function update(Request $request, Payment $payment)
{
    $data = $request->validate([
        'tenant_id' => 'required|exists:tenants,id',
        'month' => 'required|date_format:Y-m',
        'amount' => 'required|numeric|min:1',
        'original_amount' => 'nullable|numeric|min:1', // For advance payments
        'payment_method' => 'required|in:cash,pesapal,bank_transfer,mobile_money',
        'reference_number' => 'nullable|string|max:100',
        'actual_payment_date' => 'nullable|date',
        'status' => 'required|in:pending,paid,failed,refunded',
        'notes' => 'nullable|string',
        'is_advance_payment' => 'nullable|boolean',
        'allocated_months' => 'nullable|array' // For advance payments
    ]);

    $tenant = Tenant::with('apartment')->findOrFail($data['tenant_id']);
    
    if (!$tenant->apartment) {
        return back()->with('error', 'Selected tenant is not assigned to any apartment.');
    }

    $data['apartment_id'] = $tenant->apartment->id;
    $data['month'] = $data['month'] . '-01';

    // Handle advance payment fields
    if ($request->has('is_advance_payment') && $request->is_advance_payment) {
        // For advance payments, we need to handle allocation
        if ($request->has('original_amount') && $request->original_amount) {
            $data['original_amount'] = $request->original_amount;
            $data['is_advance_payment'] = true;
            
            // Calculate how many months this advance covers
            $rent = $tenant->apartment->rent;
            $fullMonths = floor($request->original_amount / $rent);
            
            // Auto-allocate months if not provided
            if (!$request->filled('allocated_months') && $fullMonths > 0) {
                $startMonth = Carbon::createFromFormat('Y-m', $request->month);
                $allocatedMonths = [];
                
                for ($i = 0; $i < $fullMonths; $i++) {
                    $allocatedMonths[] = $startMonth->copy()->addMonths($i)->format('Y-m');
                }
                
                $data['allocated_months'] = $allocatedMonths;
            }
        }
    } else {
        // Regular payment - clear advance fields
        $data['original_amount'] = null;
        $data['is_advance_payment'] = false;
        $data['allocated_months'] = null;
    }

    // Handle payment status and dates
    if ($data['status'] === 'paid') {
        $data['paid_at'] = now();
        $data['actual_payment_date'] = $data['actual_payment_date'] ?? now()->format('Y-m-d');
        $data['processed_by'] = auth()->id();
        
        // If marking as paid now, send SMS
        if ($payment->status !== 'paid') {
            try {
                $this->reminderService->sendPaymentConfirmation($payment);
            } catch (\Exception $e) {
                Log::error('Failed to send payment confirmation SMS: ' . $e->getMessage());
            }
        }
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

    
/**
 * Download receipt PDF for a payment
 */
public function downloadReceipt(Payment $payment)
{
    try {
        $receiptData = $payment->getReceiptData();
        
        // For shared hosting: Use simple HTML view first
        $html = view('admin.payments.receipt-pdf', compact('receiptData'))->render();
        
        // Configure DomPDF for shared hosting
        $pdf = Pdf::loadHTML($html);
        
        // Set paper and orientation
        $pdf->setPaper('A4', 'portrait');
        
        // CRITICAL: Set DomPDF options for shared hosting
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false, // Disable remote URLs
            'chroot' => base_path(), // Set root directory
            'tempDir' => storage_path('temp'), // Set temp directory
            'fontDir' => storage_path('fonts'), // Font directory
            'fontCache' => storage_path('fonts'), // Font cache
            'isFontSubsettingEnabled' => false, // Disable font subsetting
            'defaultFont' => 'helvetica', // Use basic font
            'debugPng' => false,
            'debugKeepTemp' => false,
            'debugCss' => false,
            'debugLayout' => false,
            'debugLayoutLines' => false,
            'debugLayoutBlocks' => false,
            'debugLayoutInline' => false,
            'debugLayoutPaddingBox' => false,
        ]);
        
        // Ensure temp directory exists
        $tempDir = storage_path('temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $filename = 'payment-receipt-' . $payment->receipt_number . '.pdf';
        
        return $pdf->download($filename);
        
    } catch (\Exception $e) {
        Log::error('Receipt generation error: ' . $e->getMessage() . 
                  ' | Trace: ' . $e->getTraceAsString());
        
        // Fallback: Return HTML version for printing
        return $this->generateHtmlFallback($payment);
    }
}

/**
 * Generate HTML fallback when PDF fails
 */
private function generateHtmlFallback(Payment $payment)
{
    try {
        $receiptData = $payment->getReceiptData();
        
        return view('admin.payments.receipt-html', compact('receiptData'))
            ->with('isHtmlFallback', true);
            
    } catch (\Exception $e) {
        Log::error('HTML fallback error: ' . $e->getMessage());
        
        // Ultimate fallback: Simple text response
        return response()->view('admin.payments.receipt-simple', [
            'payment' => $payment,
            'error' => 'PDF generation failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * Email receipt to tenant
 */
public function emailReceipt(Request $request, Payment $payment)
{
    $request->validate([
        'email' => 'required|email'
    ]);
    
    try {
        $receiptData = $payment->getReceiptData();
        
        // Generate PDF - FIX: Change PDF:: to Pdf::
        $pdf = Pdf::loadView('admin.payments.receipt-pdf', compact('receiptData'));
        
        // Email the receipt
        Mail::send('emails.payment-receipt', compact('receiptData'), function($message) use ($request, $payment, $pdf) {
            $message->to($request->email)
                    ->subject('Payment Receipt - ' . $payment->receipt_number . ' - Rent Today')
                    ->attachData($pdf->output(), 'payment-receipt-' . $payment->receipt_number . '.pdf');
        });
        
        return back()->with('success', 'Receipt emailed successfully to ' . $request->email);
        
    } catch (\Exception $e) {
        Log::error('Email receipt error: ' . $e->getMessage());
        return back()->with('error', 'Failed to email receipt: ' . $e->getMessage());
    }
}
}