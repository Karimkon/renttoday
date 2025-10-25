<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance Sheet - {{ \Carbon\Carbon::parse($balanceSheet['as_of_date'])->format('F j, Y') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #333; }
        .header p { margin: 5px 0; color: #666; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f8f9fa; font-weight: bold; }
        .table .total-row { background-color: #e9ecef; font-weight: bold; }
        .table .section-header { background-color: #dee2e6; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .assets-table th { background-color: #d1ecf1; }
        .liabilities-table th { background-color: #f8d7da; }
        .balance-check { margin-top: 20px; padding: 10px; border: 1px solid #ddd; }
        .balanced { background-color: #d4edda; color: #155724; }
        .not-balanced { background-color: #f8d7da; color: #721c24; }
        .company-info { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <h1>{{ config('app.name', 'Property Management System') }}</h1>
            <p>Balance Sheet</p>
        </div>
        <p>As of: <strong>{{ \Carbon\Carbon::parse($balanceSheet['as_of_date'])->format('F j, Y') }}</strong></p>
    </div>

    <div class="row">
        <!-- Assets -->
        <div style="width: 48%; float: left; margin-right: 4%;">
            <table class="table assets-table">
                <thead>
                    <tr>
                        <th colspan="2">ASSETS</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="section-header">
                        <td colspan="2">Current Assets</td>
                    </tr>
                    <tr>
                        <td>Cash & Bank</td>
                        <td class="text-right">UGX {{ number_format($balanceSheet['assets']['current_assets']['cash'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Accounts Receivable (Rent)</td>
                        <td class="text-right">UGX {{ number_format($balanceSheet['assets']['current_assets']['accounts_receivable'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Late Fees Receivable</td>
                        <td class="text-right">UGX {{ number_format($balanceSheet['assets']['current_assets']['late_fees_receivable'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Prepaid Expenses</td>
                        <td class="text-right">UGX {{ number_format($balanceSheet['assets']['current_assets']['prepaid_expenses'], 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>Total Current Assets</td>
                        <td class="text-right">UGX {{ number_format($balanceSheet['assets']['current_assets']['total_current_assets'], 2) }}</td>
                    </tr>

                    <tr class="section-header">
                        <td colspan="2">Fixed Assets</td>
                    </tr>
                    <tr>
                        <td>Fixed Assets at Cost</td>
                        <td class="text-right">UGX {{ number_format($balanceSheet['assets']['fixed_assets']['fixed_assets'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Less: Accumulated Depreciation</td>
                        <td class="text-right">UGX {{ number_format($balanceSheet['assets']['fixed_assets']['accumulated_depreciation'], 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>Net Fixed Assets</td>
                        <td class="text-right">UGX {{ number_format($balanceSheet['assets']['fixed_assets']['net_fixed_assets'], 2) }}</td>
                    </tr>

                    <tr class="total-row" style="background-color: #cce7ff;">
                        <td><strong>TOTAL ASSETS</strong></td>
                        <td class="text-right"><strong>UGX {{ number_format($balanceSheet['assets']['total_assets'], 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Liabilities & Equity -->
        <div style="width: 48%; float: left;">
            <table class="table liabilities-table">
                <thead>
                    <tr>
                        <th colspan="2">LIABILITIES & EQUITY</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="section-header">
                        <td colspan="2">Current Liabilities</td>
                    </tr>
                    <tr>
                        <td>Accounts Payable (to Landlords)</td>
                        <td class="text-right">UGX {{ number_format($balanceSheet['liabilities']['current_liabilities']['accounts_payable'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Accrued Expenses</td>
                        <td class="text-right">UGX {{ number_format($balanceSheet['liabilities']['current_liabilities']['accrued_expenses'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Unearned Revenue (Advances)</td>
                        <td class="text-right">UGX {{ number_format($balanceSheet['liabilities']['current_liabilities']['unearned_revenue'], 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>Total Liabilities</td>
                        <td class="text-right">UGX {{ number_format($balanceSheet['liabilities']['current_liabilities']['total_liabilities'], 2) }}</td>
                    </tr>

                    <tr class="section-header">
                        <td colspan="2">Equity</td>
                    </tr>
                    <tr>
                        <td>Opening Equity</td>
                        <td class="text-right">UGX {{ number_format($balanceSheet['equity']['opening_equity'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Add: Net Income</td>
                        <td class="text-right">UGX {{ number_format($balanceSheet['equity']['net_income'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Less: Drawings</td>
                        <td class="text-right">UGX {{ number_format($balanceSheet['equity']['drawings'], 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>Total Equity</td>
                        <td class="text-right">UGX {{ number_format($balanceSheet['equity']['total_equity'], 2) }}</td>
                    </tr>

                    <tr class="total-row" style="background-color: #f8d7da;">
                        <td><strong>TOTAL LIABILITIES & EQUITY</strong></td>
                        <td class="text-right"><strong>UGX {{ number_format($balanceSheet['liabilities']['current_liabilities']['total_liabilities'] + $balanceSheet['equity']['total_equity'], 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div style="clear: both;"></div>

    <!-- Balance Check -->
    <div class="balance-check {{ $balanceSheet['balance_check'] ? 'balanced' : 'not-balanced' }}">
        <h4>Balance Check:</h4>
        <p>
            Total Assets: <strong>UGX {{ number_format($balanceSheet['assets']['total_assets'], 2) }}</strong><br>
            Total Liabilities & Equity: <strong>UGX {{ number_format($balanceSheet['liabilities']['current_liabilities']['total_liabilities'] + $balanceSheet['equity']['total_equity'], 2) }}</strong><br>
            <strong>Status: {{ $balanceSheet['balance_check'] ? 'BALANCED ✅' : 'NOT BALANCED ❌' }}</strong>
        </p>
    </div>

    <!-- Footer -->
    <div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; font-size: 10px; color: #666;">
        <p>Generated on: {{ now()->format('F j, Y \\a\\t g:i A') }}</p>
        <p>Page 1 of 1</p>
    </div>
</body>
</html>