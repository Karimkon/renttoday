<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Expense;
use App\Models\Apartment;
use App\Models\Landlord;
use Carbon\Carbon;
use App\Models\LatePaymentFee;
use Illuminate\Support\Facades\Mail;
use PDF;

class FinancialReportService
{
    public function generateIncomeStatement($startDate, $endDate)
    {
        // Rental Income
        $rentalIncome = Payment::whereBetween('month', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('amount');

        // Commission Income (from landlords)
        $commissionIncome = 0;
        $landlords = Landlord::with(['payments' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('month', [$startDate, $endDate])
                  ->where('status', 'paid');
        }])->get();

        foreach ($landlords as $landlord) {
            $totalRent = $landlord->payments->sum('amount');
            $commissionIncome += $totalRent * ($landlord->commission_rate / 100);
        }

        // Other Income (you can expand this)
        $otherIncome = 0;

        $totalRevenue = $rentalIncome + $commissionIncome + $otherIncome;

        // Expenses
        $operatingExpenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->where('type', 'operating')
            ->sum('amount');

        $administrativeExpenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->where('type', 'administrative')
            ->sum('amount');

        $maintenanceExpenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->where('type', 'maintenance')
            ->sum('amount');

        $otherExpenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->where('type', 'other')
            ->sum('amount');

        $totalExpenses = $operatingExpenses + $administrativeExpenses + $maintenanceExpenses + $otherExpenses;

        $netIncome = $totalRevenue - $totalExpenses;

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'revenue' => [
                'rental_income' => $rentalIncome,
                'commission_income' => $commissionIncome,
                'other_income' => $otherIncome,
                'total_revenue' => $totalRevenue
            ],
            'expenses' => [
                'operating' => $operatingExpenses,
                'administrative' => $administrativeExpenses,
                'maintenance' => $maintenanceExpenses,
                'other' => $otherExpenses,
                'total_expenses' => $totalExpenses
            ],
            'net_income' => $netIncome
        ];
    }

 public function generateBalanceSheet($asOfDate)
    {
        // ASSETS
        $cash = $this->calculateCashBalance($asOfDate);
        $accountsReceivable = $this->calculateAccountsReceivable($asOfDate);
        $prepaidExpenses = $this->calculatePrepaidExpenses($asOfDate);
        $lateFeesReceivable = $this->calculateLateFeesReceivable($asOfDate);
        
        $totalCurrentAssets = $cash + $accountsReceivable + $prepaidExpenses + $lateFeesReceivable;
        
        $fixedAssets = $this->calculateFixedAssets($asOfDate);
        $accumulatedDepreciation = $this->calculateAccumulatedDepreciation($asOfDate);
        $netFixedAssets = $fixedAssets - $accumulatedDepreciation;
        
        $totalAssets = $totalCurrentAssets + $netFixedAssets;

        // LIABILITIES
        $accountsPayable = $this->calculateAccountsPayable($asOfDate); // Due to landlords
        $accruedExpenses = $this->calculateAccruedExpenses($asOfDate);
        $unearnedRevenue = $this->calculateUnearnedRevenue($asOfDate); // Advance payments
        
        $totalLiabilities = $accountsPayable + $accruedExpenses + $unearnedRevenue;

        // EQUITY
        $openingEquity = $this->getOpeningEquity();
        $netIncome = $this->calculateNetIncomeToDate($asOfDate);
        $drawings = $this->calculateDrawings($asOfDate);
        
        $totalEquity = $openingEquity + $netIncome - $drawings;

        $balanceCheck = $totalAssets == ($totalLiabilities + $totalEquity);

        return [
            'as_of_date' => $asOfDate,
            'assets' => [
                'current_assets' => [
                    'cash' => $cash,
                    'accounts_receivable' => $accountsReceivable,
                    'prepaid_expenses' => $prepaidExpenses,
                    'late_fees_receivable' => $lateFeesReceivable,
                    'total_current_assets' => $totalCurrentAssets
                ],
                'fixed_assets' => [
                    'fixed_assets' => $fixedAssets,
                    'accumulated_depreciation' => $accumulatedDepreciation,
                    'net_fixed_assets' => $netFixedAssets
                ],
                'total_assets' => $totalAssets
            ],
            'liabilities' => [
                'current_liabilities' => [
                    'accounts_payable' => $accountsPayable,
                    'accrued_expenses' => $accruedExpenses,
                    'unearned_revenue' => $unearnedRevenue,
                    'total_liabilities' => $totalLiabilities
                ]
            ],
            'equity' => [
                'opening_equity' => $openingEquity,
                'net_income' => $netIncome,
                'drawings' => $drawings,
                'total_equity' => $totalEquity
            ],
            'balance_check' => $balanceCheck
        ];
    }

    public function exportBalanceSheetToPdf($balanceSheetData)
{
    $pdf = PDF::loadView('admin.financial-reports.pdf.balance-sheet', [
        'balanceSheet' => $balanceSheetData
    ]);
    
    // Set paper size and orientation
    $pdf->setPaper('A4', 'portrait');
    
    return $pdf;
}

    public function generateProfitAndLoss($startDate, $endDate)
    {
        $incomeStatement = $this->generateIncomeStatement($startDate, $endDate);
        
        // Add late fee income
        $lateFeeIncome = LatePaymentFee::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('amount');

        $totalRevenue = $incomeStatement['revenue']['total_revenue'] + $lateFeeIncome;
        
        // Calculate gross profit (Revenue - Cost of Sales)
        $costOfSales = $this->calculateCostOfSales($startDate, $endDate);
        $grossProfit = $totalRevenue - $costOfSales;
        
        // Calculate operating profit (Gross Profit - Operating Expenses)
        $operatingExpenses = $incomeStatement['expenses']['operating'] + 
                           $incomeStatement['expenses']['administrative'];
        $operatingProfit = $grossProfit - $operatingExpenses;
        
        // Net profit (Operating Profit - Other Expenses + Other Income)
        $netProfit = $operatingProfit - $incomeStatement['expenses']['maintenance'] 
                   - $incomeStatement['expenses']['other'];

        return [
            'period' => $incomeStatement['period'],
            'revenue' => array_merge($incomeStatement['revenue'], [
                'late_fee_income' => $lateFeeIncome,
                'total_revenue' => $totalRevenue
            ]),
            'cost_of_sales' => $costOfSales,
            'gross_profit' => $grossProfit,
            'operating_expenses' => $operatingExpenses,
            'operating_profit' => $operatingProfit,
            'other_expenses' => [
                'maintenance' => $incomeStatement['expenses']['maintenance'],
                'other' => $incomeStatement['expenses']['other']
            ],
            'net_profit' => $netProfit
        ];
    }

    public function exportIncomeStatementToPdf($incomeStatementData)
{
    $pdf = PDF::loadView('admin.financial-reports.pdf.income-statement', [
        'incomeStatement' => $incomeStatementData
    ]);
    
    $pdf->setPaper('A4', 'portrait');
    
    return $pdf;
}

public function exportProfitAndLossToPdf($profitLossData)
{
    $pdf = PDF::loadView('admin.financial-reports.pdf.profit-loss', [
        'profitLoss' => $profitLossData
    ]);
    
    $pdf->setPaper('A4', 'portrait');
    
    return $pdf;
}

    // LATE PAYMENT FEE SYSTEM
    public function checkAndApplyLateFees()
    {
        $currentMonth = now()->format('Y-m');
        $fifthDay = now()->setDay(5);
        
        // Only run after 5th of the month
        if (now()->lessThan($fifthDay)) {
            return;
        }

        $apartments = Apartment::with(['tenant', 'payments' => function($query) use ($currentMonth) {
            $query->where('month', 'like', $currentMonth . '%');
        }])->whereHas('tenant')->get();

        $lateFeesApplied = 0;

        foreach ($apartments as $apartment) {
            $currentMonthPayment = $apartment->payments->first();
            
            // If no payment for current month and it's after 5th
            if (!$currentMonthPayment || $currentMonthPayment->status !== 'paid') {
                $lateFeeAmount = $apartment->rent * 0.10; // 10% late fee
                
                // Check if late fee already applied this month
                $existingLateFee = LatePaymentFee::where('apartment_id', $apartment->id)
                    ->where('month', $currentMonth)
                    ->first();

                if (!$existingLateFee) {
                    LatePaymentFee::create([
                        'apartment_id' => $apartment->id,
                        'tenant_id' => $apartment->tenant_id,
                        'month' => $currentMonth,
                        'amount' => $lateFeeAmount,
                        'original_rent' => $apartment->rent,
                        'due_date' => now()->setDay(5)->format('Y-m-d'),
                        'status' => 'unpaid'
                    ]);

                    $lateFeesApplied++;
                    
                    // Send reminder message
                    $this->sendLatePaymentReminder($apartment, $lateFeeAmount);
                }
            }
        }

        return $lateFeesApplied;
    }

    public function sendLatePaymentReminder($apartment, $lateFeeAmount)
    {
        $tenant = $apartment->tenant;
        $totalDue = $apartment->rent + $lateFeeAmount;
        
        try {
            Mail::send('emails.late_payment_reminder', [
                'tenant' => $tenant,
                'apartment' => $apartment,
                'lateFeeAmount' => $lateFeeAmount,
                'totalDue' => $totalDue,
                'dueDate' => now()->setDay(5)->format('F j, Y')
            ], function($message) use ($tenant) {
                $message->to($tenant->email)
                        ->subject('Late Payment Reminder - ' . config('app.name'));
            });

            // You can also send SMS here if you have SMS integration
            $this->sendSmsReminder($tenant, $apartment, $lateFeeAmount);

        } catch (\Exception $e) {
            \Log::error('Failed to send late payment reminder: ' . $e->getMessage());
        }
    }

    private function sendSmsReminder($tenant, $apartment, $lateFeeAmount)
    {
        // Implement SMS sending logic here
        // This could use services like Africa's Talking, Twilio, etc.
        $message = "Hello {$tenant->name}, your rent for {$apartment->number} is overdue. ";
        $message .= "Late fee: UGX " . number_format($lateFeeAmount) . ". ";
        $message .= "Total due: UGX " . number_format($apartment->rent + $lateFeeAmount) . ". ";
        $message .= "Please pay immediately to avoid further charges.";
        
        \Log::info("SMS would be sent to {$tenant->phone}: {$message}");
    }

    // NEW HELPER METHODS FOR BALANCE SHEET
    private function calculateLateFeesReceivable($asOfDate)
    {
        return LatePaymentFee::where('status', 'unpaid')
            ->where('due_date', '<=', $asOfDate)
            ->sum('amount');
    }

    private function calculateUnearnedRevenue($asOfDate)
    {
        // Advance payments for future months
        return Payment::where('month', '>', $asOfDate)
            ->where('status', 'paid')
            ->sum('amount');
    }

    private function calculateCostOfSales($startDate, $endDate)
    {
        // For property management, cost of sales might include:
        // - Direct property maintenance
        // - Property taxes
        // - Insurance
        return Expense::whereBetween('date', [$startDate, $endDate])
            ->whereIn('category', ['maintenance', 'insurance', 'taxes'])
            ->sum('amount');
    }

    private function calculateAccumulatedDepreciation($asOfDate)
    {
        // This would come from a fixed_assets_depreciation table
        // For now, return 0 or a fixed percentage of fixed assets
        return 0;
    }

    // Helper methods for balance sheet calculations
    private function calculateCashBalance($asOfDate)
    {
        // Sum all payments received minus expenses paid
        $totalPayments = Payment::where('status', 'paid')
            ->where('created_at', '<=', $asOfDate)
            ->sum('amount');

        $totalExpenses = Expense::where('date', '<=', $asOfDate)
            ->sum('amount');

        return $totalPayments - $totalExpenses; // Simplified calculation
    }

    private function calculateAccountsReceivable($asOfDate)
    {
        // Unpaid rent for current and past months
        $currentMonth = Carbon::parse($asOfDate)->format('Y-m');
        $apartments = Apartment::with(['payments' => function($query) use ($asOfDate) {
            $query->where('created_at', '<=', $asOfDate);
        }])->whereHas('tenant')->get();

        $receivable = 0;
        foreach ($apartments as $apartment) {
            $monthsOccupied = $this->getMonthsOccupied($apartment, $asOfDate);
            $totalPaid = $apartment->payments->sum('amount');
            $totalDue = $apartment->rent * $monthsOccupied;
            $receivable += max(0, $totalDue - $totalPaid);
        }

        return $receivable;
    }

    private function calculateAccountsPayable($asOfDate)
    {
        // Amount due to landlords (rent collected but not yet paid out)
        $landlords = Landlord::with(['payments' => function($query) use ($asOfDate) {
        $query->where('payments.created_at', '<=', $asOfDate) // Specify payments table
              ->where('payments.status', 'paid'); // Specify payments table
    }])->get();

        $payable = 0;
        foreach ($landlords as $landlord) {
            $totalRent = $landlord->payments->sum('amount');
            $commission = $totalRent * ($landlord->commission_rate / 100);
            $payable += ($totalRent - $commission);
        }

        return $payable;
    }

    private function calculateFixedAssets($asOfDate)
    {
        // This would come from a fixed_assets table
        // For now, return a fixed value or 0
        return 0;
    }

    private function getOpeningEquity()
    {
        // This would be your starting capital
        return 0; // You can configure this
    }

    private function calculateNetIncomeToDate($asOfDate)
    {
        $startOfYear = Carbon::parse($asOfDate)->startOfYear()->format('Y-m-d');
        $incomeStatement = $this->generateIncomeStatement($startOfYear, $asOfDate);
        return $incomeStatement['net_income'];
    }

    private function calculateDrawings($asOfDate)
    {
        // This would come from a drawings table
        return 0;
    }

    private function calculateAccruedExpenses($asOfDate)
    {
        // Expenses incurred but not yet paid
        return Expense::where('date', '<=', $asOfDate)
            ->where('status', 'unpaid')
            ->sum('amount');
    }

    private function calculatePrepaidExpenses($asOfDate)
    {
        // Expenses paid in advance
        return Expense::where('date', '>', $asOfDate)
            ->where('status', 'paid')
            ->sum('amount');
    }

    private function getMonthsOccupied($apartment, $asOfDate)
    {
        if (!$apartment->tenant || !$apartment->created_at) {
            return 0;
        }

        $startDate = $apartment->created_at;
        $endDate = Carbon::parse($asOfDate);

        return $startDate->diffInMonths($endDate) + 1;
    }


}