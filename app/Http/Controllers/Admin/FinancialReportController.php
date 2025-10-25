<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FinancialReportService;
use App\Models\Expense;
use Carbon\Carbon;

class FinancialReportController extends Controller
{
    protected $financialService;

    public function __construct(FinancialReportService $financialService)
    {
        $this->financialService = $financialService;
    }

    public function index()
    {
        $defaultStart = now()->startOfMonth()->format('Y-m-d');
        $defaultEnd = now()->endOfMonth()->format('Y-m-d');
        $defaultBalanceDate = now()->format('Y-m-d');

        return view('admin.financial-reports.index', compact('defaultStart', 'defaultEnd', 'defaultBalanceDate'));
    }

    public function incomeStatement(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $incomeStatement = $this->financialService->generateIncomeStatement($startDate, $endDate);

        if ($request->has('export') && $request->export == 'pdf') {
            return $this->exportIncomeStatementPdf($incomeStatement);
        }

        return view('admin.financial-reports.income-statement', compact('incomeStatement'));
    }

   public function balanceSheet(Request $request)
    {
        $asOfDate = $request->get('as_of_date', now()->format('Y-m-d'));
        
        $balanceSheet = $this->financialService->generateBalanceSheet($asOfDate);

        // Check if export is requested
        if ($request->has('export') && $request->get('export') === 'pdf') {
            $pdf = $this->financialService->exportBalanceSheetToPdf($balanceSheet);
            
            $filename = 'balance-sheet-' . Carbon::parse($asOfDate)->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
        }

        return view('admin.financial-reports.balance-sheet', compact('balanceSheet'));
    }

    public function profitAndLoss(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $profitLoss = $this->financialService->generateProfitAndLoss($startDate, $endDate);

        if ($request->has('export') && $request->export == 'pdf') {
            return $this->exportProfitLossPdf($profitLoss);
        }

        return view('admin.financial-reports.profit-loss', compact('profitLoss'));
    }

    // Expense Management
    public function expenses(Request $request)
    {
        $query = Expense::latest();

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('date', '<=', $request->end_date);
        }

        $expenses = $query->paginate(20);
        $expenseTypes = Expense::getTypes();
        $expenseCategories = Expense::getCategories();

        return view('admin.financial-reports.expenses', compact('expenses', 'expenseTypes', 'expenseCategories'));
    }

    public function createExpense()
    {
        $expenseTypes = Expense::getTypes();
        $expenseCategories = Expense::getCategories();
        
        return view('admin.financial-reports.create-expense', compact('expenseTypes', 'expenseCategories'));
    }

    // PDF Export Methods
    private function exportIncomeStatementPdf($incomeStatement)
    {
        $pdf = \PDF::loadView('admin.financial-reports.pdf.income-statement', compact('incomeStatement'));
        return $pdf->download("income-statement-{$incomeStatement['period']['start']}-to-{$incomeStatement['period']['end']}.pdf");
    }

    private function exportBalanceSheetPdf($balanceSheet)
    {
        $pdf = \PDF::loadView('admin.financial-reports.pdf.balance-sheet', compact('balanceSheet'));
        return $pdf->download("balance-sheet-{$balanceSheet['as_of_date']}.pdf");
    }

    private function exportProfitLossPdf($profitLoss)
    {
        $pdf = \PDF::loadView('admin.financial-reports.pdf.profit-loss', compact('profitLoss'));
        return $pdf->download("profit-loss-{$profitLoss['period']['start']}-to-{$profitLoss['period']['end']}.pdf");
    }
}