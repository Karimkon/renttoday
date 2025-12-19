<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Landlord;
use App\Models\Apartment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

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

    $totalDisplayRent = 0;
    $totalActualRent = 0;
    $totalCommissionRent = 0;
    
    foreach ($apartments as $apartment) {
        // Get ALL payments made in this month (actual cash received)
        $paymentsMadeThisMonth = $apartment->getPaymentsActuallyMadeInMonth($month);
        $actualAmountThisMonth = $paymentsMadeThisMonth->sum('amount');
        $totalActualRent += $actualAmountThisMonth;
        
        // Calculate how much of this payment applies to this specific month
        $amountForThisMonth = 0;
        foreach ($paymentsMadeThisMonth as $payment) {
            if ($payment->coversMonth($month)) {
                $amountForThisMonth += $payment->getAmountForMonth($month);
            }
        }
        
        // For display, show the total amount paid this month if it's an advance
        // Otherwise show just the amount covering this month
        if ($actualAmountThisMonth > $apartment->rent) {
            // This is an advance payment (more than one month's rent)
            $totalDisplayRent += $actualAmountThisMonth;
            $totalCommissionRent += $actualAmountThisMonth; // Commission on full advance
        } else {
            // Regular payment
            $totalDisplayRent += $amountForThisMonth;
            $totalCommissionRent += $actualAmountThisMonth;
        }
        
        // If no money was paid this month, check what covers this month
        if ($actualAmountThisMonth == 0) {
            $paymentsCoveringMonth = $apartment->payments()
                ->where('status', 'paid')
                ->get()
                ->filter(function($payment) use ($month) {
                    return $payment->coversMonth($month);
                });
            
            $amountCoveringMonth = $paymentsCoveringMonth->sum(function($payment) use ($month) {
                return $payment->getAmountForMonth($month);
            });
            
            // Add to display (but not to actual rent for commission)
            $totalDisplayRent += $amountCoveringMonth;
        }
    }
    
    $totalCommission = $totalCommissionRent * ($landlord->commission_rate / 100);
    $amountDue = $totalCommissionRent - $totalCommission;

    $landlordPaymentStatus = $landlord->getPaymentStatus($month);

    $reportData = [
        'landlord' => $landlord,
        'month' => $monthCarbon,
        'apartments' => $apartments,
        'totalDisplayRent' => $totalDisplayRent,
        'totalActualRent' => $totalActualRent,
        'totalCommissionRent' => $totalCommissionRent,
        'totalCommission' => $totalCommission,
        'amountDue' => $amountDue,
        'landlordPaymentStatus' => $landlordPaymentStatus,
    ];

    $locations = $landlord->apartments()->distinct()->pluck('location');

    return view('admin.landlords.report', compact('reportData', 'locations'));
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

    /**
     * Generate PDF report for landlord
     * 
     * KEY FIX: Only count money ACTUALLY PAID in the report month
     */
    public function generatePdfReport(Landlord $landlord, $month = null)
    {
        try {
            $month = $month ?? now()->format('Y-m');
            $monthCarbon = Carbon::createFromFormat('Y-m', $month);
            
            $apartments = $landlord->apartments()->with(['tenant', 'payments'])->get();

            // =====================================================
            // KEY FIX: Calculate totals based on ACTUAL payment date
            // =====================================================
            $allPaymentsThisMonth = collect();
            foreach ($apartments as $apartment) {
                $paymentsActuallyMadeThisMonth = $apartment->getPaymentsActuallyMadeInMonth($month);
                $allPaymentsThisMonth = $allPaymentsThisMonth->merge($paymentsActuallyMadeThisMonth);
            }
            
            $uniquePayments = $allPaymentsThisMonth->unique('id');
            $totalRent = $uniquePayments->sum('amount');
            $totalCommission = $totalRent * ($landlord->commission_rate / 100);
            
            $amountDue = $totalRent - $totalCommission;

        $landlordPaymentStatus = $landlord->getPaymentStatus($month);

        $reportData = [
            'landlord' => $landlord,
            'month' => $monthCarbon,
            'apartments' => $apartments,
            'totalRent' => $totalRent,
            'totalCommission' => $totalCommission,
            'amountDue' => $amountDue,
            'landlordPaymentStatus' => $landlordPaymentStatus,
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
            
            $apartments = $landlord->apartments()->with(['tenant', 'payments'])->get();

            // Same fix as above
            $totalRent = 0;
            $totalCommission = 0;
            
            foreach ($apartments as $apartment) {
                $paymentsActuallyMadeThisMonth = $apartment->payments()
                    ->where('status', 'paid')
                    ->where(function($query) use ($month) {
                        $query->whereRaw("DATE_FORMAT(actual_payment_date, '%Y-%m') = ?", [$month])
                              ->orWhere(function($q) use ($month) {
                                  $q->whereNull('actual_payment_date')
                                    ->whereRaw("DATE_FORMAT(paid_at, '%Y-%m') = ?", [$month]);
                              });
                    })
                    ->get();
                
                $rentPaid = $paymentsActuallyMadeThisMonth->sum('amount');
                $totalRent += $rentPaid;
                
                $commission = $rentPaid * ($landlord->commission_rate / 100);
                $totalCommission += $commission;
            }
            
            $amountDue = $totalRent - $totalCommission;
            $landlordPaymentStatus = $landlord->getPaymentStatus($month);

            $reportData = [
                'landlord' => $landlord,
                'month' => $monthCarbon,
                'apartments' => $apartments,
                'totalRent' => $totalRent,
                'totalCommission' => $totalCommission,
                'amountDue' => $amountDue,
                'landlordPaymentStatus' => $landlordPaymentStatus,
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