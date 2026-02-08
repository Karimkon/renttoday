<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Landlord;
use App\Models\Apartment;
use App\Models\LandlordExpense;
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
        $reportData = $this->prepareReportData($landlord, $month);
        $locations = $reportData['apartments']->pluck('location')->unique()->values();

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
            $reportData = $this->prepareReportData($landlord, $month);
            $locations = $reportData['apartments']->pluck('location')->unique()->values();

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
    private function prepareReportData(Landlord $landlord, $month)
    {
        $monthCarbon = Carbon::createFromFormat('Y-m', $month);
        $apartments = $landlord->apartments()->with(['tenant', 'payments'])->get();

        // Normalize locations for consistent grouping (prevents "MUKONO" vs "Mukono" split)
        foreach ($apartments as $apartment) {
            $apartment->location = ucwords(strtolower(trim($apartment->location)));
        }

        $totalCollectedRent = 0; 
        $totalCommissionRent = 0; 
        $totalDisplayRent = 0; 

        foreach ($apartments as $apartment) {
            $rowData = $apartment->getReportRowData($month);
            $totalCollectedRent += $rowData['rent_collected_agency'];
            $totalCommissionRent += $rowData['commissionable_amount'];

            if ($rowData['commissionable_amount'] > 0) {
                $totalDisplayRent += $rowData['commissionable_amount'];
            } elseif ($rowData['is_covered_by_advance']) {
                $totalDisplayRent += $apartment->rent;
            } else {
                $totalDisplayRent += $apartment->getTotalAmountCoveringMonth($month);
            }
        }

        $totalCommission = $totalCommissionRent * ($landlord->commission_rate / 100);
        $expenses = $landlord->getExpensesForMonth($month);
        $totalExpenses = $expenses->sum('amount');

        // Amount due = Rent collected BY AGENCY - Commission - Expenses
        $amountDue = $totalCollectedRent - $totalCommission - $totalExpenses;

        return [
            'landlord' => $landlord,
            'month' => $monthCarbon,
            'apartments' => $apartments,
            'totalRent' => $totalDisplayRent, // Legacy name, updated in views to "Total Rent Value"
            'totalDisplayRent' => $totalDisplayRent,
            'totalCollectedRent' => $totalCollectedRent,
            'totalCommissionRent' => $totalCommissionRent,
            'totalCommission' => $totalCommission,
            'expenses' => $expenses,
            'totalExpenses' => $totalExpenses,
            'amountDue' => $amountDue,
            'landlordPaymentStatus' => $landlord->getPaymentStatus($month),
        ];
    }

    /**
     * HTML Fallback when PDF generation fails
     */
    private function generateHtmlFallback(Landlord $landlord, $month)
    {
        try {
            $reportData = $this->prepareReportData($landlord, $month);
            $locations = $reportData['apartments']->pluck('location')->unique()->values();

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

    // =====================================================
    // LANDLORD EXPENSE MANAGEMENT
    // =====================================================

    /**
     * Show form to add expense for a landlord
     */
    public function createExpense(Landlord $landlord, Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $apartments = $landlord->apartments;
        $categories = LandlordExpense::getCategories();

        return view('admin.landlords.create-expense', compact('landlord', 'month', 'apartments', 'categories'));
    }

    /**
     * Store a new expense for a landlord
     */
    public function storeExpense(Request $request, Landlord $landlord)
    {
        $data = $request->validate([
            'month' => 'required|date_format:Y-m',
            'apartment_id' => 'nullable|exists:apartments,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|in:' . implode(',', array_keys(LandlordExpense::getCategories())),
            'expense_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
        ]);

        $data['landlord_id'] = $landlord->id;
        $data['created_by'] = auth()->id();

        LandlordExpense::create($data);

        return redirect()
    ->route('admin.landlords.monthly-report', ['landlord' => $landlord->id, 'month' => $data['month']])
    ->with('success', 'Expense added successfully. Amount: UGX ' . number_format($data['amount']));
    }

    /**
     * Show form to edit an expense
     */
    public function editExpense(Landlord $landlord, LandlordExpense $expense)
    {
        $apartments = $landlord->apartments;
        $categories = LandlordExpense::getCategories();

        return view('admin.landlords.edit-expense', compact('landlord', 'expense', 'apartments', 'categories'));
    }

    /**
     * Update an expense
     */
    public function updateExpense(Request $request, Landlord $landlord, LandlordExpense $expense)
    {
        $data = $request->validate([
            'month' => 'required|date_format:Y-m',
            'apartment_id' => 'nullable|exists:apartments,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|in:' . implode(',', array_keys(LandlordExpense::getCategories())),
            'expense_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
        ]);

        $expense->update($data);

        return redirect()
    ->route('admin.landlords.monthly-report', ['landlord' => $landlord->id, 'month' => $data['month']])
    ->with('success', 'Expense updated successfully.');
    }

    /**
 * Show all expenses for a landlord
 */
public function indexExpenses(Landlord $landlord, Request $request)
{
    $month = $request->get('month', now()->format('Y-m'));
    $expenses = $landlord->expenses()
        ->with('apartment')
        ->when($request->has('month'), function($query) use ($month) {
            $query->where('month', $month);
        })
        ->orderBy('expense_date', 'desc')
        ->paginate(20);

    return view('admin.landlords.expenses-index', compact('landlord', 'expenses', 'month'));
}
  public function destroyExpense(Landlord $landlord, LandlordExpense $expense)
{
    $month = $expense->month;
    $expense->delete();

    return redirect()
        ->route('admin.landlords.monthly-report', ['landlord' => $landlord->id, 'month' => $month])
        ->with('success', 'Expense deleted successfully.');
}
}