<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Apartment;
use App\Models\Tenant;
use App\Models\Landlord;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Log;

class AdminApartmentController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->month ?? Carbon::now()->format('Y-m');
        $statusFilter = $request->status ?? null;
        $landlordFilter = $request->landlord_id ?? null;
        $locationFilter = $request->location ?? null;

        $selectedMonth = Carbon::createFromFormat('Y-m', $month);
        $previousMonth = $selectedMonth->copy()->subMonth()->format('Y-m');
        $expectedRentTotal = 0;
        $previousMonthExpectedRent = 0;

        // Build base query with relationships
        $query = Apartment::with(['tenant', 'payments', 'landlord']);

        // Apply basic filters BEFORE pagination
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

        // Tenant status filter
        if ($request->filled('tenant_status')) {
            switch ($request->tenant_status) {
                case 'with_tenant':
                    $query->whereNotNull('tenant_id');
                    break;
                case 'without_tenant':
                    $query->whereNull('tenant_id');
                    break;
                case 'with_credit':
                    $query->whereHas('tenant', function($q) {
                        $q->where('credit_balance', '>', 0);
                    });
                    break;
            }
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

        // Get ALL apartments (not paginated yet) to calculate status
        $allApartments = $query->get();
        
        $landlords = Landlord::all();
        $locations = Apartment::distinct()->pluck('location');

        // Transform each apartment with payment status
        // Transform each apartment with payment status
  // Transform each apartment with payment status
$allApartments->transform(function ($apt) use ($month, $selectedMonth, &$expectedRentTotal) {
    $paymentStatus = $apt->getPaymentStatusForReport($month);
    
    $apt->totalPaid = $paymentStatus['amount_paid'];
    $apt->dueAmount = max(0, $apt->rent - $apt->totalPaid);
    
    // Calculate expected rent for this apartment in the selected month
    $apt->expectedRent = $apt->calculateExpectedRent($selectedMonth);
    $expectedRentTotal += $apt->expectedRent;
    
    // Calculate expected rent for previous month (for comparison)
    $previousMonth = $selectedMonth->copy()->subMonth();
    $apt->previousMonthExpectedRent = $apt->calculateExpectedRent($previousMonth);

    // ADD THIS: Calculate commission
    $apt->expectedCommission = 0;
    $apt->collectedCommission = 0;
    
    if ($apt->landlord && $apt->landlord->commission_rate) {
        // Calculate expected commission based on expected rent
        $apt->expectedCommission = $apt->expectedRent * ($apt->landlord->commission_rate / 100);
        
        // Calculate collected commission based on actual payments
        $apt->collectedCommission = $apt->totalPaid * ($apt->landlord->commission_rate / 100);
    }

    if (!$apt->tenant) {
        $apt->status = 'empty';
        $apt->progressPercentage = 0;
    } else {
        $apt->progressPercentage = min(100, ($apt->totalPaid / max(1, $apt->rent)) * 100);
        
        if ($paymentStatus['status'] === 'PAID') {
            $apt->status = $paymentStatus['is_partial'] ? 'partial' : 'paid';
        } else {
            $apt->status = 'unpaid';
        }
    }

    return $apt;
});

// Calculate total commission across all apartments
$expectedCommissionTotal = 0;
$collectedCommissionTotal = 0;

foreach ($allApartments as $apt) {
    $expectedCommissionTotal += $apt->expectedCommission;
    $collectedCommissionTotal += $apt->collectedCommission;
}
        // NOW apply status filter AFTER calculating payment status
        if ($statusFilter) {
            $allApartments = $allApartments->filter(function($apt) use ($statusFilter) {
                return $apt->status === $statusFilter;
            });
        }

        // Apply progress filter
        if ($request->filled('progress')) {
            $allApartments = $allApartments->filter(function($apt) use ($request) {
                switch ($request->progress) {
                    case 'full':
                        return $apt->progressPercentage >= 100;
                    case 'partial':
                        return $apt->progressPercentage > 0 && $apt->progressPercentage < 100;
                    case 'none':
                        return $apt->progressPercentage == 0 && $apt->tenant;
                    case 'overdue':
                        return $apt->progressPercentage == 0 && $apt->tenant && $apt->dueAmount > 0;
                    default:
                        return true;
                }
            });
        }

        // Manual pagination
        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        
        $paginatedApartments = $allApartments->slice($offset, $perPage)->values();
        
        // Create paginator instance
        $apartments = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedApartments,
            $allApartments->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.apartments.index', compact(
            'apartments',
            'month',
            'statusFilter',
            'landlords',
            'landlordFilter',
            'locations',
            'locationFilter',
            'expectedRentTotal',
            'expectedCommissionTotal',
    'collectedCommissionTotal'
        ));
    }

    /**
     * Export filtered apartments to PDF (Print View)
     */
    public function exportPdf(Request $request)
    {
        $month = $request->month ?? Carbon::now()->format('Y-m');
        $statusFilter = $request->status ?? null;
        
        // Get filtered apartments (reuse same logic from index)
        $apartments = $this->getFilteredApartments($request);
        
        $landlords = Landlord::all();
        
        // Return a print-friendly HTML view instead of PDF
        return view('admin.apartments.export-pdf', compact('apartments', 'month', 'statusFilter', 'landlords'));
    }

    /**
     * Export filtered apartments to Excel (CSV)
     */
    public function exportExcel(Request $request)
    {
        $month = $request->month ?? Carbon::now()->format('Y-m');
        
        // Get filtered apartments (reuse same logic from index)
        $apartments = $this->getFilteredApartments($request);
        
        // Generate CSV
        $filename = "apartments-" . ($request->status ? $request->status . "-" : "") . $month . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($apartments, $month) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'Number',
                'Landlord',
                'Commission %',
                'Location',
                'Rooms',
                'Monthly Rent',
                'Tenant',
                'Phone',
                'Status',
                'Amount Paid',
                'Due Amount',
                'Progress %',
                'Month'
            ]);
            
            // CSV Data
            foreach ($apartments as $apartment) {
                fputcsv($file, [
                    $apartment->number,
                    $apartment->landlord->name ?? 'N/A',
                    $apartment->landlord->commission_rate ?? 'N/A',
                    $apartment->location ?? 'N/A',
                    $apartment->rooms,
                    number_format($apartment->rent, 0, '.', ''),
                    $apartment->tenant->name ?? 'Vacant',
                    $apartment->tenant->phone ?? 'N/A',
                    ucfirst($apartment->status),
                    number_format($apartment->totalPaid, 0, '.', ''),
                    number_format($apartment->dueAmount, 0, '.', ''),
                    number_format($apartment->progressPercentage, 1, '.', ''),
                    Carbon::createFromFormat('Y-m', $month)->format('F Y')
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Helper method to get filtered apartments (DRY principle)
     */
    private function getFilteredApartments(Request $request)
    {
        $month = $request->month ?? Carbon::now()->format('Y-m');
        $statusFilter = $request->status ?? null;
        $landlordFilter = $request->landlord_id ?? null;
        $locationFilter = $request->location ?? null;

        // Build base query
        $query = Apartment::with(['tenant', 'payments', 'landlord']);

        // Apply filters
        if ($landlordFilter) {
            $query->where('landlord_id', $landlordFilter);
        }

        if ($locationFilter) {
            $query->where('location', $locationFilter);
        }

        if ($request->filled('rent_min')) {
            $query->where('rent', '>=', $request->rent_min);
        }

        if ($request->filled('rent_max')) {
            $query->where('rent', '<=', $request->rent_max);
        }

        if ($request->filled('rooms')) {
            $query->where('rooms', '>=', $request->rooms);
        }

        if ($request->filled('tenant_status')) {
            switch ($request->tenant_status) {
                case 'with_tenant':
                    $query->whereNotNull('tenant_id');
                    break;
                case 'without_tenant':
                    $query->whereNull('tenant_id');
                    break;
                case 'with_credit':
                    $query->whereHas('tenant', function($q) {
                        $q->where('credit_balance', '>', 0);
                    });
                    break;
            }
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

        // Get all apartments
        $allApartments = $query->get();

        // Transform with payment status
        $allApartments->transform(function ($apt) use ($month) {
            $paymentStatus = $apt->getPaymentStatusForReport($month);
            
            $apt->totalPaid = $paymentStatus['amount_paid'];
            $apt->dueAmount = max(0, $apt->rent - $apt->totalPaid);

            if (!$apt->tenant) {
                $apt->status = 'empty';
                $apt->progressPercentage = 0;
            } else {
                $apt->progressPercentage = min(100, ($apt->totalPaid / max(1, $apt->rent)) * 100);
                
                if ($paymentStatus['status'] === 'PAID') {
                    $apt->status = $paymentStatus['is_partial'] ? 'partial' : 'paid';
                } else {
                    $apt->status = 'unpaid';
                }
            }

            return $apt;
        });

        // Apply status filter
        if ($statusFilter) {
            $allApartments = $allApartments->filter(function($apt) use ($statusFilter) {
                return $apt->status === $statusFilter;
            });
        }

        // Apply progress filter
        if ($request->filled('progress')) {
            $allApartments = $allApartments->filter(function($apt) use ($request) {
                switch ($request->progress) {
                    case 'full':
                        return $apt->progressPercentage >= 100;
                    case 'partial':
                        return $apt->progressPercentage > 0 && $apt->progressPercentage < 100;
                    case 'none':
                        return $apt->progressPercentage == 0 && $apt->tenant;
                    case 'overdue':
                        return $apt->progressPercentage == 0 && $apt->tenant && $apt->dueAmount > 0;
                    default:
                        return true;
                }
            });
        }

        return $allApartments;
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