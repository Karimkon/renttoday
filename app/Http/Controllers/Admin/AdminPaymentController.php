<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Apartment;
use Carbon\Carbon;

class AdminPaymentController extends Controller
{
   public function index()
{
    // Load all payments with tenant and apartment
    $payments = Payment::with(['tenant', 'apartment'])
                       ->orderBy('month', 'desc')
                       ->get();

    // Calculate total collected
    $totalCollected = $payments->sum('amount');

    return view('admin.payments.index', compact('payments', 'totalCollected'));
}


    public function create()
    {
        $tenants = Tenant::with('apartment')->get();
        return view('admin.payments.create', compact('tenants'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_id'     => 'required|exists:tenants,id',
            'month'         => 'required|date_format:Y-m',
            'amount'        => 'required|numeric|min:1',
            'includes_gym'  => 'nullable|boolean',
        ]);

        $tenant = Tenant::with('apartment')->findOrFail($data['tenant_id']);
        $apartment = $tenant->apartment;

        if (!$apartment) {
            return back()->with('error', 'Selected tenant is not assigned to any apartment.');
        }

        $rent = $apartment->rent;
        $totalAmount = $data['amount'] + ($tenant->credit_balance ?? 0);

        $monthsCovered = floor($totalAmount / $rent);
        $remainder = $totalAmount - ($monthsCovered * $rent);

        $startMonth = Carbon::createFromFormat('Y-m', $data['month']);

        for ($i = 0; $i < $monthsCovered; $i++) {
            $paymentMonth = $startMonth->copy()->addMonths($i)->format('Y-m');

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
                    'includes_gym' => $data['includes_gym'] ?? false,
                ]);
            }
        }

        $tenant->update(['credit_balance' => $remainder]);

        return redirect()->route('admin.payments.index')
                         ->with('success', "Payment added for {$monthsCovered} month(s). Remaining credit: UGX {$remainder}");
    }

    public function edit(Payment $payment)
    {
        $tenants = Tenant::with('apartment')->get();
        return view('admin.payments.edit', compact('payment','tenants'));
    }

    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'tenant_id'     => 'required|exists:tenants,id',
            'month'         => 'required|date_format:Y-m',
            'amount'        => 'required|numeric|min:1',
            'includes_gym'  => 'nullable|boolean',
        ]);

        $tenant = Tenant::with('apartment')->findOrFail($data['tenant_id']);
        if (!$tenant->apartment) {
            return back()->with('error', 'Selected tenant is not assigned to any apartment.');
        }

        $data['apartment_id'] = $tenant->apartment->id;

        $billingDay = $tenant->billing_day ?? 1;
        $day = str_pad($billingDay,2,'0',STR_PAD_LEFT);
        $data['month'] = $data['month'].'-'.$day;

        $payment->update($data);

        return redirect()->route('admin.payments.index')
                         ->with('success','Payment updated successfully.');
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return redirect()->route('admin.payments.index')
                         ->with('success','Payment removed successfully.');
    }
}
