<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Landlord;
use App\Models\ReportNote;
use App\Models\Apartment;
use Carbon\Carbon;
use PDF;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class LandlordController extends Controller
{
    public function index(Request $request)
    {
        $query = Landlord::withCount('apartments');

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
        
        $currentMonth = now()->format('Y-m');
        $selectedMonth = request('month', $currentMonth);

        return view('admin.landlords.show', compact('landlord', 'selectedMonth'));
    }

    public function showReport(Landlord $landlord, $month = null)
{
    $month = $month ?? now()->format('Y-m');
    $monthCarbon = Carbon::createFromFormat('Y-m', $month);
    
    $apartments = $landlord->apartments()->with(['tenant', 'payments'])->get();

    // Calculate totals using new logic that includes advance payments
    $totalRent = 0;
    $totalCommission = 0;
    
    foreach ($apartments as $apartment) {
        $paymentStatus = $apartment->getPaymentStatusForReport($month);
        if ($paymentStatus['amount_paid'] > 0) {
            $totalRent += $paymentStatus['amount_paid'];
        }
    }
    
    $totalCommission = $totalRent * ($landlord->commission_rate / 100);
    $amountDue = $totalRent - $totalCommission;

    $landlordPaymentStatus = $landlord->getPaymentStatus($month);

    // FIX: Get the report note properly - use first() instead of firstOrCreate()
    $reportNote = ReportNote::where('landlord_id', $landlord->id)
        ->where('month', $month)
        ->first();

    // If no note exists, create a dummy object to avoid errors
    if (!$reportNote) {
        $reportNote = new ReportNote([
            'landlord_id' => $landlord->id,
            'month' => $month,
            'notes' => '',
            'created_by' => Auth::id()
        ]);
    }

    $reportData = [
        'landlord' => $landlord,
        'month' => $monthCarbon,
        'apartments' => $apartments,
        'totalRent' => $totalRent,
        'totalCommission' => $totalCommission,
        'amountDue' => $amountDue,
        'landlordPaymentStatus' => $landlordPaymentStatus,
        'reportNote' => $reportNote
    ];

    $locations = $landlord->apartments()->distinct()->pluck('location');

    return view('admin.landlords.report', compact('reportData', 'locations'));
}

  public function saveReportNote(Request $request, Landlord $landlord)
{
    try {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'notes' => 'nullable|string|max:5000'
        ]);

        $reportNote = ReportNote::updateOrCreate(
            [
                'landlord_id' => $landlord->id,
                'month' => $validated['month']
            ],
            [
                'notes' => $validated['notes'] ?? '',
                'created_by' => auth()->id()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Notes saved successfully!',
            'data' => [
                'updated_at' => $reportNote->updated_at->format('M j, Y g:i A')
            ]
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('Validation error:', $e->errors());
        
        return response()->json([
            'success' => false,
            'message' => 'Validation failed: ' . collect($e->errors())->flatten()->implode(', ')
        ], 422);
        
    } catch (\Exception $e) {
        \Log::error('Save note error: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage()
        ], 500);
    }
}

    public function markPaymentPaid(Request $request, Landlord $landlord)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'amount' => 'required|numeric'
        ]);

        $landlord->markPaymentAsPaid($request->month, $request->amount);

        return back()->with('success', 'Payment status updated to PAID for ' . Carbon::createFromFormat('Y-m', $request->month)->format('F Y'));
    }

    /**
     * Mark landlord payment as unpaid
     */
    public function markPaymentUnpaid(Request $request, Landlord $landlord)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m'
        ]);

        $landlord->markPaymentAsUnpaid($request->month);

        return back()->with('success', 'Payment status updated to UNPAID for ' . Carbon::createFromFormat('Y-m', $request->month)->format('F Y'));
    }

   public function generatePdfReport(Landlord $landlord, $month = null)
{
    try {
        $month = $month ?? now()->format('Y-m');
        $monthCarbon = Carbon::createFromFormat('Y-m', $month);
        
        // Get apartments with payments for the selected month
        $apartments = $landlord->apartments()->with(['tenant', 'payments' => function($q) use ($month) {
            $q->where('month', 'like', $month . '%');
        }])->get();

        // Calculate totals
        $totalRent = 0;
        $totalCommission = 0;
        
        foreach ($apartments as $apartment) {
            $paymentStatus = $apartment->getPaymentStatusForReport($month);
            if ($paymentStatus['amount_paid'] > 0) {
                $totalRent += $paymentStatus['amount_paid'];
            }
        }
        
        $totalCommission = $totalRent * ($landlord->commission_rate / 100);
        $amountDue = $totalRent - $totalCommission;

        // Get payment status for this month
        $landlordPaymentStatus = $landlord->getPaymentStatus($month);

        // Get report note for PDF (but don't require it)
        $reportNote = ReportNote::where('landlord_id', $landlord->id)
            ->where('month', $month)
            ->first();

        $reportData = [
            'landlord' => $landlord,
            'month' => $monthCarbon,
            'apartments' => $apartments,
            'totalRent' => $totalRent,
            'totalCommission' => $totalCommission,
            'amountDue' => $amountDue,
            'landlordPaymentStatus' => $landlordPaymentStatus,
            'reportNote' => $reportNote // This can be null
        ];

        $locations = $landlord->apartments()->distinct()->pluck('location');

        // SHARED HOSTING FIX: Configure DomPDF for shared hosting
        $pdf = PDF::loadView('admin.landlords.report-pdf', compact('reportData', 'locations'));
        
        // Set paper and orientation
        $pdf->setPaper('A4', 'portrait');
        
        // CRITICAL: Set DomPDF options for shared hosting
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'chroot' => base_path(),
            'tempDir' => storage_path('app/temp'),
            'fontDir' => storage_path('fonts'),
            'fontCache' => storage_path('fonts'),
            'isFontSubsettingEnabled' => false,
            'defaultFont' => 'helvetica',
            'debugPng' => false,
            'debugKeepTemp' => false,
            'debugCss' => false,
            'debugLayout' => false,
            'debugLayoutLines' => false,
            'debugLayoutBlocks' => false,
            'debugLayoutInline' => false,
            'debugLayoutPaddingBox' => false,
        ]);
        
        // Ensure temp directory exists
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $filename = $this->sanitizeFilename("rent-today-report-{$landlord->name}-{$month}.pdf");
        
        return $pdf->download($filename);
        
    } catch (\Exception $e) {
        // Log the error for debugging
        Log::error('PDF Generation Error', [
            'landlord_id' => $landlord->id,
            'month' => $month,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Fallback: Return HTML version
        return $this->generateHtmlFallback($landlord, $month);
    }
}

    /**
     * Sanitize filename for cross-platform compatibility
     */
    private function sanitizeFilename($filename)
    {
        // Remove special characters and spaces
        $filename = preg_replace('/[^A-Za-z0-9\-_.]/', '-', $filename);
        $filename = preg_replace('/-+/', '-', $filename); // Remove multiple dashes
        return $filename;
    }

    /**
     * HTML Fallback when PDF generation fails
     */
    private function generateHtmlFallback(Landlord $landlord, $month)
    {
        try {
            $monthCarbon = Carbon::createFromFormat('Y-m', $month);
            
            $apartments = $landlord->apartments()->with(['tenant', 'payments' => function($q) use ($month) {
                $q->where('month', 'like', $month . '%');
            }])->get();

            $totalRent = 0;
            $totalCommission = 0;
            
            foreach ($apartments as $apartment) {
                $paymentStatus = $apartment->getPaymentStatusForReport($month);
                if ($paymentStatus['amount_paid'] > 0) {
                    $totalRent += $paymentStatus['amount_paid'];
                }
            }
            
            $totalCommission = $totalRent * ($landlord->commission_rate / 100);
            $amountDue = $totalRent - $totalCommission;
            $landlordPaymentStatus = $landlord->getPaymentStatus($month);

            // Get report note for HTML fallback
            $reportNote = ReportNote::where('landlord_id', $landlord->id)
                ->where('month', $month)
                ->first();

            $reportData = [
                'landlord' => $landlord,
                'month' => $monthCarbon,
                'apartments' => $apartments,
                'totalRent' => $totalRent,
                'totalCommission' => $totalCommission,
                'amountDue' => $amountDue,
                'landlordPaymentStatus' => $landlordPaymentStatus,
                'reportNote' => $reportNote
            ];

            $locations = $landlord->apartments()->distinct()->pluck('location');

            return view('admin.landlords.report-pdf', compact('reportData', 'locations'))
                ->with('isHtmlFallback', true);
                
        } catch (\Exception $e) {
            return back()->with('error', 'PDF generation failed: ' . $e->getMessage());
        }
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
        if ($landlord->apartments()->count() > 0) {
            return redirect()->route('admin.landlords.index')
                             ->with('error', 'Cannot delete landlord with assigned apartments.');
        }

        $landlord->delete();

        return redirect()->route('admin.landlords.index')
                         ->with('success', 'Landlord deleted successfully.');
    }
}