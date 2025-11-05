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
              })
              ->orWhereHas('landlord', function($q) use ($search) {
                  $q->where('name', 'like', "%{$search}%");
              });
        });
    }

    // Fetch apartments
    $apartments = $query->get();
    $landlords = Landlord::all();
    $locations = Apartment::distinct()->pluck('location');

    // SIMPLE TRANSFORMATION: No credit balance calculations
    $apartments->transform(function ($apt) use ($month) {
        // Get payment status for the selected month
        $paymentStatus = $apt->getPaymentStatusForReport($month);
        
        // Simple payment calculation - just use the amount paid
        $apt->totalPaid = $paymentStatus['amount_paid'];
        $apt->dueAmount = max(0, $apt->rent - $apt->totalPaid);

        if (!$apt->tenant) {
            $apt->status = 'empty';
            $apt->progressPercentage = 0;
        } else {
            // Calculate progress percentage based on actual payments only
            $apt->progressPercentage = min(100, ($apt->totalPaid / max(1, $apt->rent)) * 100);
            
            // Set status based on payment
            if ($paymentStatus['status'] === 'PAID') {
                $apt->status = $paymentStatus['is_partial'] ? 'partial' : 'paid';
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
