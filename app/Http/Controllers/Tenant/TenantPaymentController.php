<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PesapalService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TenantPaymentController extends Controller
{
    protected $pesapal;

    public function __construct(PesapalService $pesapal)
    {
        $this->pesapal = $pesapal;
    }

    // List tenant payments
    public function index()
    {
        $tenant = auth()->user()->tenant;
        $payments = Payment::where('tenant_id', $tenant->id)->orderBy('month', 'desc')->get();

        return view('tenant.payments.index', compact('payments'));
    }

    // Initiate payment
  public function pay($paymentId, Request $request)
{
    $tenant = auth()->user()->tenant;

    if ($paymentId == 0) {
        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'month' => $request->month ?? now()->format('Y-m-d'),
            'amount' => $request->amount ?? $tenant->apartment->rent,
            'status' => 'unpaid',
        ]);
    } else {
        $payment = Payment::findOrFail($paymentId);
    }

    // Pesapal requires merchant_reference, currency, and customer info
    $orderData = [
        'amount' => $payment->amount,
        'currency' => 'UGX',
        'description' => "Rent Payment for " . \Carbon\Carbon::parse($payment->month)->format('F Y'),
        'callback_url' => route('tenant.payments.callback'),
        'notification_id' => config('services.pesapal.notification_id') ?? null, // optional
        'billing_address' => [
            'email_address' => $tenant->email,
            'phone_number' => $tenant->phone ?? '',
            'country_code' => 'UG',
            'first_name' => $tenant->name,
            'last_name' => '',
            'line_1' => 'Apartment Rent Payment',
            'line_2' => '',
            'city' => 'Kampala',
            'state' => 'Central',
            'postal_code' => ''
        ],
        'merchant_reference' => (string) Str::uuid(),
    ];

    \Log::info('Pesapal Request Payload:', $orderData);

    $response = $this->pesapal->submitOrder($orderData);

    \Log::info('Pesapal raw response:', ['response' => $response]);

    $redirectUrl = $response['redirect_url'] ?? null;

    if (!$redirectUrl) {
        \Log::error('Pesapal response missing redirect_url', ['response' => $response]);
        return back()->with('error', 'Payment initiation failed. Please try again.');
    }

    return redirect()->away($redirectUrl);
}



    // Handle Pesapal callback
    public function callback(Request $request)
    {
        $transactionId = $request->get('transaction_id');
        $reference = $request->get('reference');

        $status = $this->pesapal->getTransactionStatus($reference);

        if ($status && $status['status'] === 'COMPLETED') {
            $payment = Payment::where('transaction_id', $reference)->first();
            if ($payment) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => Carbon::now(),
                    'transaction_id' => $transactionId,
                ]);
            }
        }

        return redirect()->route('tenant.payments.index')
                         ->with('success', 'Payment status updated.');
    }
}
