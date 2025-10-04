<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Apartment;
use App\Models\Tenant;
use Carbon\Carbon;

class ApartmentController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->month ?? Carbon::now()->format('Y-m');
        $statusFilter = $request->status ?? null;

        // Load apartments with tenant and payments for selected month
        $apartments = Apartment::with(['tenant', 'payments'])->get();

        // Transform apartments for status, totalPaid, and dueAmount
       
        $apartments->transform(function($apt) use ($month) {
            $apt->totalPaid = $apt->payments->sum('amount');

            if(!$apt->tenant) {
                $apt->status = 'empty';
                $apt->dueAmount = 0;
            } else {
                // months since apartment creation
                $monthsSinceStart = max(1, now()->diffInMonths($apt->created_at->copy()->startOfMonth()) + 1);
                $apt->dueAmount = max(0, ($apt->rent * $monthsSinceStart) - $apt->totalPaid - ($apt->tenant->credit_balance ?? 0));


                // Status based on totalPaid
                if($apt->totalPaid >= $apt->rent) $apt->status = 'paid';
                elseif($apt->totalPaid > 0) $apt->status = 'partial';
                else $apt->status = 'unpaid';
            }

            return $apt;
        });


        // Optional: sort by status: paid → partial → unpaid → empty
        $statusOrder = ['paid','partial','unpaid','empty'];
        $apartments = $apartments->sortBy(fn($apt) => array_search($apt->status, $statusOrder));


        return view('secretary.apartments.index', compact('apartments','month','statusFilter'));
    }

    public function create()
    {
        $tenants = Tenant::all();
        return view('secretary.apartments.create', compact('tenants'));
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
        $data['tenant_id'] = $data['tenant_id'] ?: null; // Convert empty string to null

        Apartment::create($data);

        return redirect()->route('secretary.apartments.index')
                         ->with('success', 'Apartment added.');
    }

    public function edit(Apartment $apartment)
    {
        $tenants = Tenant::all();
        return view('secretary.apartments.edit', compact('apartment', 'tenants'));
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
        $data['tenant_id'] = $data['tenant_id'] ?: null; // Normalize empty tenant

        $apartment->update($data);

        return redirect()->route('secretary.apartments.index')
                         ->with('success', 'Apartment updated.');
    }

    public function destroy(Apartment $apartment)
    {
        $apartment->delete();
        return redirect()->route('secretary.apartments.index')
                         ->with('success', 'Apartment removed.');
    }
}
