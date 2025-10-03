<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Apartment;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function index()
    {
        // Load all payments with tenant and apartment
        $payments = Payment::with(['tenant', 'apartment'])->orderBy('month', 'desc')->get();

        return view('secretary.payments.index', compact('payments'));
    }

    public function create()
    {
        $tenants = Tenant::with('apartment')->get();
        return view('secretary.payments.create', compact('tenants'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_id'     => 'required|exists:tenants,id',
            'month'         => 'required|date_format:Y-m',
            'amount'        => 'required|numeric',
            'includes_gym'  => 'nullable|boolean',
        ]);

        $tenant = Tenant::find($data['tenant_id']);

        // Assign apartment automatically
        if(!$tenant->apartment) {
            return back()->with('error', 'Selected tenant is not assigned to any apartment.');
        }

        $data['apartment_id'] = $tenant->apartment->id;

        // Get tenant billing day (default to 1)
        $billingDay = $tenant->billing_day ?? 1;
        $day = str_pad($billingDay, 2, '0', STR_PAD_LEFT);

        // Convert month to full date with billing day
        $data['month'] = $data['month'] . '-' . $day;

        Payment::create($data);

        return redirect()->route('secretary.payments.index')
                         ->with('success', 'Payment added successfully.');
    }

    public function edit(Payment $payment)
    {
        $tenants = Tenant::with('apartment')->get();
        return view('secretary.payments.edit', compact('payment', 'tenants'));
    }

    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'tenant_id'     => 'required|exists:tenants,id',
            'month'         => 'required|date_format:Y-m',
            'amount'        => 'required|numeric',
            'includes_gym'  => 'nullable|boolean',
        ]);

        $tenant = Tenant::find($data['tenant_id']);

        if(!$tenant->apartment) {
            return back()->with('error', 'Selected tenant is not assigned to any apartment.');
        }

        $data['apartment_id'] = $tenant->apartment->id;

        $billingDay = $tenant->billing_day ?? 1;
        $day = str_pad($billingDay, 2, '0', STR_PAD_LEFT);
        $data['month'] = $data['month'] . '-' . $day;

        $payment->update($data);

        return redirect()->route('secretary.payments.index')
                         ->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return redirect()->route('secretary.payments.index')
                         ->with('success', 'Payment removed successfully.');
    }
}
