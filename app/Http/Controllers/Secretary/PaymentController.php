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
    public function index(Request $request)
{
    $query = Payment::with(['tenant', 'apartment']);

    // Filters
    if ($request->filled('tenant_id')) {
        $query->where('tenant_id', $request->tenant_id);
    }

    if ($request->filled('from_month')) {
        $query->where('month', '>=', Carbon::createFromFormat('Y-m', $request->from_month)->startOfMonth());
    }

    if ($request->filled('to_month')) {
        $query->where('month', '<=', Carbon::createFromFormat('Y-m', $request->to_month)->endOfMonth());
    }

    $payments = $query->orderBy('month', 'desc')->get();
    $tenants = Tenant::all();

    return view('secretary.payments.index', compact('payments', 'tenants'));
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
        'amount'        => 'required|numeric|min:1',
        'includes_gym'  => 'nullable|boolean',
    ]);

    $tenant = Tenant::with('apartment')->findOrFail($data['tenant_id']);
    $apartment = $tenant->apartment;

    if (!$apartment) {
        return back()->with('error', 'This tenant is not assigned to any apartment.');
    }

    $rent = $apartment->rent;
    $totalAmount = $data['amount'] + ($tenant->credit_balance ?? 0); // Add any existing credit

    // Calculate how many months are covered by this total
    $monthsCovered = floor($totalAmount / $rent);
    $remainder = $totalAmount - ($monthsCovered * $rent);

    // Get the starting month
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

    // Update credit balance with leftover money
    $tenant->update(['credit_balance' => $remainder]);

    return redirect()->route('secretary.payments.index')
                     ->with('success', "Payment added for {$monthsCovered} month(s). Remaining credit: UGX {$remainder}");
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

    public function export(Request $request)
{
    $format = $request->format ?? 'excel'; // default to Excel

    // You can pass current filters to the export class
    $filters = $request->only(['tenant_id', 'status', 'month']);

    if($format === 'pdf'){
        $payments = Payment::with(['tenant', 'apartment'])->filter($filters)->get(); // you can create a scope filter()
        $pdf = \PDF::loadView('secretary.payments.export_pdf', compact('payments'));
        return $pdf->download('payments.pdf');
    }

    return Excel::download(new PaymentsExport($filters), 'payments.xlsx');
}

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return redirect()->route('secretary.payments.index')
                         ->with('success', 'Payment removed successfully.');
    }
}