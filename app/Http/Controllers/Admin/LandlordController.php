<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Landlord;
use App\Models\Apartment;
use Carbon\Carbon;
use PDF;

class LandlordController extends Controller
{
    public function index(Request $request)
    {
        $query = Landlord::withCount('apartments');

        // Search filter
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        $landlords = $query->paginate(20);

        return view('admin.landlords.index', compact('landlords'));
    }

    public function create()
    {
        return view('admin.landlords.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:landlords',
            'phone' => 'nullable|string|max:20',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'address' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        Landlord::create($request->all());

        return redirect()->route('admin.landlords.index')
                         ->with('success', 'Landlord added successfully.');
    }

    public function show(Landlord $landlord)
    {
        $landlord->load('apartments.tenant', 'apartments.payments');
        
        // Get current month for report
        $currentMonth = now()->format('Y-m');
        $selectedMonth = request('month', $currentMonth);

        return view('admin.landlords.show', compact('landlord', 'selectedMonth'));
    }

    // Add this method to your LandlordController
public function showReport(Landlord $landlord, $month = null)
{
    $month = $month ?? now()->format('Y-m');
    $monthCarbon = Carbon::createFromFormat('Y-m', $month);
    
    // Get report data using your service
    $reportData = [
        'landlord' => $landlord,
        'month' => $monthCarbon,
        'apartments' => $landlord->apartments()->with(['tenant', 'payments' => function($q) use ($month) {
            $q->where('month', 'like', $month . '%');
        }])->get(),
        'totalRent' => $landlord->totalRentCollected($month . '-01', $month . '-31'),
        'totalCommission' => $landlord->calculateCommission($month . '-01', $month . '-31'),
        'amountDue' => $landlord->amountDueToLandlord($month . '-01', $month . '-31')
    ];

    // Get unique locations
    $locations = $landlord->apartments()->distinct()->pluck('location');

    return view('admin.landlords.report', compact('reportData', 'locations'));
}


//  LandlordController for PDF report export
public function generatePdfReport(Landlord $landlord, $month = null)
{
    $month = $month ?? now()->format('Y-m');
    $monthCarbon = Carbon::createFromFormat('Y-m', $month);
    
    // Get apartments with payments for the selected month - FIXED QUERY
    $apartments = $landlord->apartments()->with(['tenant', 'payments' => function($q) use ($month) {
        $q->where('month', 'like', $month . '%'); // This should get payments for the specific month
    }])->get();

    // Calculate totals correctly - FIXED CALCULATION
    $totalRent = $apartments->sum(function($apartment) {
        return $apartment->payments->sum('amount'); // Sum all payments for this apartment
    });
    
    $totalCommission = $totalRent * ($landlord->commission_rate / 100);
    $amountDue = $totalRent - $totalCommission;

    $reportData = [
        'landlord' => $landlord,
        'month' => $monthCarbon,
        'apartments' => $apartments,
        'totalRent' => $totalRent,
        'totalCommission' => $totalCommission,
        'amountDue' => $amountDue
    ];

    $locations = $landlord->apartments()->distinct()->pluck('location');

    // Use the PDF-specific view
    $pdf = PDF::loadView('admin.landlords.report-pdf', compact('reportData', 'locations'));
    
    $filename = "rent-today-report-{$landlord->name}-{$month}.pdf";
    return $pdf->download($filename);
}
    public function edit(Landlord $landlord)
    {
        return view('admin.landlords.edit', compact('landlord'));
    }

    public function update(Request $request, Landlord $landlord)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:landlords,email,' . $landlord->id,
            'phone' => 'nullable|string|max:20',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'address' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        $landlord->update($request->all());

        return redirect()->route('admin.landlords.index')
                         ->with('success', 'Landlord updated successfully.');
    }

    public function destroy(Landlord $landlord)
    {
        // Check if landlord has apartments
        if ($landlord->apartments()->count() > 0) {
            return redirect()->route('admin.landlords.index')
                             ->with('error', 'Cannot delete landlord with assigned apartments.');
        }

        $landlord->delete();

        return redirect()->route('admin.landlords.index')
                         ->with('success', 'Landlord deleted successfully.');
    }

}