<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Apartment;
use App\Models\Tenant;
use App\Models\Landlord;
use Carbon\Carbon;

class AdminApartmentController extends Controller
{
   public function index(Request $request)
{
    $month = $request->month ?? Carbon::now()->format('Y-m');
    $statusFilter = $request->status ?? null;
    $landlordFilter = $request->landlord_id ?? null;
    $locationFilter = $request->location ?? null;

    // Build base query with relationships
    $query = Apartment::with(['tenant', 'payments', 'landlord']);

    // Apply basic filters
    if ($landlordFilter) {
        $query->where('landlord_id', $landlordFilter);
    }

    if ($locationFilter) {
        $query->where('location', $locationFilter);
    }

    // Apply advanced filters
    if ($request->filled('rent_min')) {
        $query->where('rent', '>=', $request->rent_min);
    }

    if ($request->filled('rent_max')) {
        $query->where('rent', '<=', $request->rent_max);
    }

    if ($request->filled('rooms')) {
        $query->where('rooms', '>=', $request->rooms);
    }

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('number', 'like', "%{$search}%")
              ->orWhereHas('tenant', function($q) use ($search) {
                  $q->where('name', 'like', "%{$search}%");
              });
        });
    }

    // Fetch apartments
    $apartments = $query->get();
    $landlords = Landlord::all();
    $locations = Apartment::distinct()->pluck('location');

    // Transform apartments to calculate rent data
    $apartments->transform(function ($apt) use ($month) {
        $startOfMonth = Carbon::parse($month)->startOfMonth();
        $endOfMonth = Carbon::parse($month)->endOfMonth();

        // Sum of payments for the selected month
        $apt->totalPaid = $apt->payments()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        if (!$apt->tenant) {
            $apt->status = 'empty';
            $apt->dueAmount = 0;
            $apt->progressPercentage = 0;
        } else {
            // Calculate months since apartment was created
            $monthsSinceStart = max(1, now()->diffInMonths($apt->created_at->copy()->startOfMonth()) + 1);

            // Include tenant credit in due calculation
            $credit = $apt->tenant->credit_balance ?? 0;
            $apt->dueAmount = max(0, ($apt->rent * $monthsSinceStart) - $apt->totalPaid - $credit);

            // Calculate progress percentage
            $apt->progressPercentage = min(100, (($apt->totalPaid + $credit) / max(1, $apt->rent)) * 100);

            // Determine payment status
            $totalAvailable = $apt->totalPaid + $credit;
            if ($totalAvailable >= $apt->rent) {
                $apt->status = 'paid';
            } elseif ($totalAvailable > 0) {
                $apt->status = 'partial';
            } else {
                $apt->status = 'unpaid';
            }
        }

        return $apt;
    });

    // Apply status filter after transformation
    if ($statusFilter) {
        $apartments = $apartments->filter(fn($apt) => $apt->status === $statusFilter);
    }

    // Apply progress filter
    if ($request->filled('progress')) {
        $apartments = $apartments->filter(function($apt) use ($request) {
            if (!$apt->tenant) return false;
            
            switch ($request->progress) {
                case 'full': return $apt->progressPercentage >= 100;
                case 'partial': return $apt->progressPercentage > 0 && $apt->progressPercentage < 100;
                case 'none': return $apt->progressPercentage == 0;
                case 'overdue': return $apt->dueAmount > 0;
                default: return true;
            }
        });
    }

    // Apply tenant status filter
    if ($request->filled('tenant_status')) {
        $apartments = $apartments->filter(function($apt) use ($request) {
            switch ($request->tenant_status) {
                case 'with_tenant': return $apt->tenant !== null;
                case 'without_tenant': return $apt->tenant === null;
                case 'with_credit': return $apt->tenant && ($apt->tenant->credit_balance ?? 0) > 0;
                default: return true;
            }
        });
    }

    // Sort by logical payment status order
    $statusOrder = ['paid', 'partial', 'unpaid', 'empty'];
    $apartments = $apartments->sortBy(fn($apt) => array_search($apt->status, $statusOrder))->values();

    return view('admin.apartments.index', compact(
        'apartments',
        'month',
        'statusFilter',
        'landlords',
        'landlordFilter',
        'locations',
        'locationFilter'
    ));
}

    public function create()
    {
        $tenants = Tenant::all();
        $landlords = Landlord::all();
        return view('admin.apartments.create', compact('tenants', 'landlords'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'number' => 'required|unique:apartments',
            'rooms' => 'required|integer|min:1',
            'rent' => 'required|numeric',
            'tenant_id' => 'nullable|exists:tenants,id',
            'landlord_id' => 'required|exists:landlords,id',
            'location' => 'required|string|max:255',
        ]);

        Apartment::create($request->all());

        return redirect()->route('admin.apartments.index')
                         ->with('success', 'Apartment added successfully.');
    }

    public function edit(Apartment $apartment)
    {
        $tenants = Tenant::all();
        $landlords = Landlord::all();
        return view('admin.apartments.edit', compact('apartment', 'tenants', 'landlords'));
    }

    public function update(Request $request, Apartment $apartment)
    {
        $request->validate([
            'number' => 'required|unique:apartments,number,' . $apartment->id,
            'rooms' => 'required|integer|min:1',
            'rent' => 'required|numeric',
            'tenant_id' => 'nullable|exists:tenants,id',
            'landlord_id' => 'required|exists:landlords,id',
            'location' => 'required|string|max:255',
        ]);

        $apartment->update($request->all());

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
