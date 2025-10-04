<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Apartment;
use App\Models\Tenant;
use Carbon\Carbon;

class AdminApartmentController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->month ?? Carbon::now()->format('Y-m');
        $statusFilter = $request->status ?? null;

        // Load apartments with tenant and payments
        $apartments = Apartment::with(['tenant', 'payments'])->get();

        // Transform apartments to include totalPaid, dueAmount, and status
        $apartments->transform(function($apt) use ($month) {
            $apt->totalPaid = $apt->payments->sum('amount');

            if (!$apt->tenant) {
                $apt->status = 'empty';
                $apt->dueAmount = 0;
            } else {
                // Months since apartment creation
                $monthsSinceStart = max(1, now()->diffInMonths($apt->created_at->copy()->startOfMonth()) + 1);

                // Include tenant credit in due calculation
                $apt->dueAmount = max(0, ($apt->rent * $monthsSinceStart) - $apt->totalPaid - ($apt->tenant->credit_balance ?? 0));

                // Determine status based on payments + credit
                if ($apt->totalPaid + ($apt->tenant->credit_balance ?? 0) >= $apt->rent) {
                    $apt->status = 'paid';
                } elseif ($apt->totalPaid + ($apt->tenant->credit_balance ?? 0) > 0) {
                    $apt->status = 'partial';
                } else {
                    $apt->status = 'unpaid';
                }
            }

            return $apt;
        });

        // Optional: filter by status
        if ($statusFilter) {
            $apartments = $apartments->filter(fn($apt) => $apt->status == $statusFilter);
        }

        // Sort by status order: paid → partial → unpaid → empty
        $statusOrder = ['paid','partial','unpaid','empty'];
        $apartments = $apartments->sortBy(fn($apt) => array_search($apt->status, $statusOrder));

        return view('admin.apartments.index', compact('apartments','month','statusFilter'));
    }

    public function create()
    {
        $tenants = Tenant::all();
        return view('admin.apartments.create', compact('tenants'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'number' => 'required|unique:apartments',
            'rooms' => 'required|integer|min:1',
            'rent' => 'required|numeric',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $data = $request->all();
        $data['tenant_id'] = $data['tenant_id'] ?: null;

        Apartment::create($data);

        return redirect()->route('admin.apartments.index')
                         ->with('success', 'Apartment added successfully.');
    }

    public function edit(Apartment $apartment)
    {
        $tenants = Tenant::all();
        return view('admin.apartments.edit', compact('apartment', 'tenants'));
    }

    public function update(Request $request, Apartment $apartment)
    {
        $request->validate([
            'number' => 'required|unique:apartments,number,'.$apartment->id,
            'rooms' => 'required|integer|min:1',
            'rent' => 'required|numeric',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $data = $request->all();
        $data['tenant_id'] = $data['tenant_id'] ?: null;

        $apartment->update($data);

        return redirect()->route('admin.apartments.index')
                         ->with('success', 'Apartment updated successfully.');
    }

    public function destroy(Apartment $apartment)
    {
        $apartment->delete();
        return redirect()->route('admin.apartments.index')
                         ->with('success', 'Apartment removed successfully.');
    }
}
