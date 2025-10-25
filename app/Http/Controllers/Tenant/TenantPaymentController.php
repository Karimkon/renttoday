<?php
// app/Http/Controllers/Tenant/TenantPaymentController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\LatePaymentFee;
use App\Services\PesapalService;

class TenantPaymentController extends Controller
{
    protected $pesapalService;

    public function __construct(PesapalService $pesapalService)
    {
        $this->pesapalService = $pesapalService;
    }

    public function pay(Request $request, Payment $payment)
    {
        $tenant = auth()->user()->tenant;
        $apartment = $tenant->apartment;
        
        if (!$apartment) {
            return back()->with('error', 'No apartment assigned.');
        }

        // Calculate total amount including late fees
        $baseAmount = $payment->amount ?? $apartment->rent;
        $lateFees = LatePaymentFee::where('tenant_id', $tenant->id)
            ->unpaid()
            ->sum('amount');

        $totalAmount = $baseAmount + $lateFees;

        $orderData = [
            'id' => uniqid(),
            'currency' => 'UGX',
            'amount' => $totalAmount,
            'description' => "Rent Payment + Late Fees - {$apartment->number}",
            'callback_url' => route('tenant.payments.callback'),
            'notification_id' => config('pesapal.notification_id'),
            'billing_address' => [
                'email_address' => $tenant->email,
                'phone_number' => $tenant->phone,
                'country_code' => 'UG'
            ]
        ];

        // Store payment intent in session
        session([
            'payment_intent' => [
                'apartment_id' => $apartment->id,
                'tenant_id' => $tenant->id,
                'base_amount' => $baseAmount,
                'late_fees' => $lateFees,
                'total_amount' => $totalAmount,
                'month' => $payment->month ?? now()->format('Y-m')
            ]
        ]);

        $response = $this->pesapalService->submitOrder($orderData);

        if (isset($response['redirect_url'])) {
            return redirect($response['redirect_url']);
        }

        return back()->with('error', 'Payment initiation failed.');
    }

    public function callback(Request $request)
    {
        $paymentIntent = session('payment_intent');
        
        if (!$paymentIntent) {
            return redirect()->route('tenant.payments.index')
                ->with('error', 'Payment session expired.');
        }

        $orderTrackingId = $request->input('OrderTrackingId');
        $status = $this->pesapalService->getTransactionStatus($orderTrackingId);

        if ($status && $status['status_code'] == 1) {
            // Payment successful
            $this->processSuccessfulPayment($paymentIntent, $orderTrackingId);
            
            return redirect()->route('tenant.payments.index')
                ->with('success', 'Payment completed successfully! Late fees cleared.');
        }

        return redirect()->route('tenant.payments.index')
            ->with('error', 'Payment was not completed.');
    }

    private function processSuccessfulPayment($paymentIntent, $orderTrackingId)
    {
        // Create or update main payment
        $payment = Payment::updateOrCreate(
            [
                'apartment_id' => $paymentIntent['apartment_id'],
                'tenant_id' => $paymentIntent['tenant_id'],
                'month' => $paymentIntent['month']
            ],
            [
                'amount' => $paymentIntent['base_amount'],
                'status' => 'paid',
                'paid_at' => now(),
                'order_tracking_id' => $orderTrackingId
            ]
        );

        // Mark late fees as paid
        if ($paymentIntent['late_fees'] > 0) {
            LatePaymentFee::where('tenant_id', $paymentIntent['tenant_id'])
                ->unpaid()
                ->update(['status' => 'paid', 'paid_at' => now()]);
        }

        // Clear session
        session()->forget('payment_intent');
    }
}