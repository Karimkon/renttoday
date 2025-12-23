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
    $monthCarbon = Carbon::createFromFormat('Y-m', $month);

    $apartments = $landlord->apartments()->with(['tenant', 'payments'])->get();

    $totalDisplayRent = 0;
    $totalActualRent = 0;
    $totalCommissionRent = 0;

    foreach ($apartments as $apartment) {
        // Get ALL payments made in this month (actual cash received)
        // FIX: This now correctly filters out advance payment split records
        $paymentsMadeThisMonth = $apartment->getPaymentsActuallyMadeInMonth($month);

        // FIX: Calculate total correctly - for advance payments use original_amount
        $actualAmountThisMonth = $paymentsMadeThisMonth->sum(function($payment) {
            if ($payment->is_advance_payment && $payment->original_amount) {
                return $payment->original_amount;
            }
            return $payment->amount;
        });
        $totalActualRent += $actualAmountThisMonth;

        // Calculate how much of this payment applies to this specific month
        $amountForThisMonth = 0;
        foreach ($paymentsMadeThisMonth as $payment) {
            if ($payment->coversMonth($month)) {
                $amountForThisMonth += $payment->getAmountForMonth($month);
            }
        }

        // For display and commission, show actual cash received this month
        if ($actualAmountThisMonth > 0) {
            $totalDisplayRent += $actualAmountThisMonth;
            $totalCommissionRent += $actualAmountThisMonth; // Commission on full amount received
        }

        // If no money was paid this month, check what covers this month (for display only)
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

            // Add to display (but not to actual rent for commission - already counted when advance was paid)
            $totalDisplayRent += $amountCoveringMonth;
        }
    }

    $totalCommission = $totalCommissionRent * ($landlord->commission_rate / 100);

    // Get expenses for this landlord in this month
    $expenses = $landlord->getExpensesForMonth($month);
    $totalExpenses = $expenses->sum('amount');

    // Amount due = Rent collected - Commission - Expenses
    // Note: Commission is calculated on rent collected, expenses are deducted AFTER commission
    $amountDue = $totalCommissionRent - $totalCommission - $totalExpenses;

    $landlordPaymentStatus = $landlord->getPaymentStatus($month);

    $reportData = [
        'landlord' => $landlord,
        'month' => $monthCarbon,
        'apartments' => $apartments,
        'totalDisplayRent' => $totalDisplayRent,
        'totalActualRent' => $totalActualRent,
        'totalCommissionRent' => $totalCommissionRent,
        'totalCommission' => $totalCommission,
        'expenses' => $expenses,
        'totalExpenses' => $totalExpenses,
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
            // FIX: Use original_amount for advance payments to avoid double-counting
            // =====================================================
            $allPaymentsThisMonth = collect();
            foreach ($apartments as $apartment) {
                $paymentsActuallyMadeThisMonth = $apartment->getPaymentsActuallyMadeInMonth($month);
                $allPaymentsThisMonth = $allPaymentsThisMonth->merge($paymentsActuallyMadeThisMonth);
            }

            $uniquePayments = $allPaymentsThisMonth->unique('id');

            // FIX: Sum correctly - for advance payments use original_amount
            $totalRent = $uniquePayments->sum(function($payment) {
                if ($payment->is_advance_payment && $payment->original_amount) {
                    return $payment->original_amount;
                }
                return $payment->amount;
            });
            $totalCommission = $totalRent * ($landlord->commission_rate / 100);

            // Get expenses for this landlord in this month
            $expenses = $landlord->getExpensesForMonth($month);
            $totalExpenses = $expenses->sum('amount');

            // Amount due = Rent collected - Commission - Expenses
            $amountDue = $totalRent - $totalCommission - $totalExpenses;

        $landlordPaymentStatus = $landlord->getPaymentStatus($month);

        $reportData = [
            'landlord' => $landlord,
            'month' => $monthCarbon,
            'apartments' => $apartments,
            'totalRent' => $totalRent,
            'totalCommission' => $totalCommission,
            'expenses' => $expenses,
            'totalExpenses' => $totalExpenses,
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

            // Same fix as above - use the model method and handle advance payments correctly
            $totalRent = 0;
            $totalCommission = 0;

            foreach ($apartments as $apartment) {
                $paymentsActuallyMadeThisMonth = $apartment->getPaymentsActuallyMadeInMonth($month);

                // FIX: Sum correctly - for advance payments use original_amount
                $rentPaid = $paymentsActuallyMadeThisMonth->sum(function($payment) {
                    if ($payment->is_advance_payment && $payment->original_amount) {
                        return $payment->original_amount;
                    }
                    return $payment->amount;
                });
                $totalRent += $rentPaid;

                $commission = $rentPaid * ($landlord->commission_rate / 100);
                $totalCommission += $commission;
            }

            // Get expenses for this landlord in this month
            $expenses = $landlord->getExpensesForMonth($month);
            $totalExpenses = $expenses->sum('amount');

            $amountDue = $totalRent - $totalCommission - $totalExpenses;
            $landlordPaymentStatus = $landlord->getPaymentStatus($month);

            $reportData = [
                'landlord' => $landlord,
                'month' => $monthCarbon,
                'apartments' => $apartments,
                'totalRent' => $totalRent,
                'totalCommission' => $totalCommission,
                'expenses' => $expenses,
                'totalExpenses' => $totalExpenses,
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