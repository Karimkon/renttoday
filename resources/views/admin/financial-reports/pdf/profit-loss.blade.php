<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profit & Loss Statement - {{ \Carbon\Carbon::parse($profitLoss['period']['start'])->format('M j, Y') }} to {{ \Carbon\Carbon::parse($profitLoss['period']['end'])->format('M j, Y') }}</title>
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
        .table .subtotal-row { background-color: #f8f9fa; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .revenue-table th { background-color: #d1ecf1; }
        .gross-profit-table th { background-color: #d4edda; }
        .operating-table th { background-color: #fff3cd; }
        .net-profit-table th { background-color: #cce7ff; }
        .company-info { margin-bottom: 20px; }
        .negative { color: #dc3545; }
        .positive { color: #28a745; }
        .indent { padding-left: 20px !important; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <h1>{{ config('app.name', 'Property Management System') }}</h1>
            <p>Profit & Loss Statement</p>
        </div>
        <p>For the period: <strong>{{ \Carbon\Carbon::parse($profitLoss['period']['start'])->format('F j, Y') }} to {{ \Carbon\Carbon::parse($profitLoss['period']['end'])->format('F j, Y') }}</strong></p>
    </div>

    <!-- Revenue Section -->
    <table class="table revenue-table">
        <thead>
            <tr>
                <th colspan="2">REVENUE</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td width="70%">Rental Income</td>
                <td class="text-right">UGX {{ number_format($profitLoss['revenue']['rental_income'], 2) }}</td>
            </tr>
            <tr>
                <td>Commission Income</td>
                <td class="text-right">UGX {{ number_format($profitLoss['revenue']['commission_income'], 2) }}</td>
            </tr>
            <tr>
                <td>Late Fee Income</td>
                <td class="text-right">UGX {{ number_format($profitLoss['revenue']['late_fee_income'], 2) }}</td>
            </tr>
            <tr>
                <td>Other Income</td>
                <td class="text-right">UGX {{ number_format($profitLoss['revenue']['other_income'], 2) }}</td>
            </tr>
            <tr class="total-row">
                <td><strong>Total Revenue</strong></td>
                <td class="text-right"><strong>UGX {{ number_format($profitLoss['revenue']['total_revenue'], 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Cost of Sales -->
    <table class="table">
        <tbody>
            <tr>
                <td width="70%">Cost of Sales</td>
                <td class="text-right">UGX {{ number_format($profitLoss['cost_of_sales'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Gross Profit -->
    <table class="table gross-profit-table">
        <thead>
            <tr>
                <th colspan="2">GROSS PROFIT</th>
            </tr>
        </thead>
        <tbody>
            <tr class="total-row">
                <td><strong>Gross Profit (Revenue - Cost of Sales)</strong></td>
                <td class="text-right {{ $profitLoss['gross_profit'] < 0 ? 'negative' : 'positive' }}">
                    <strong>UGX {{ number_format($profitLoss['gross_profit'], 2) }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Operating Expenses -->
    <table class="table operating-table">
        <thead>
            <tr>
                <th colspan="2">OPERATING EXPENSES</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td width="70%">Operating & Administrative Expenses</td>
                <td class="text-right">UGX {{ number_format($profitLoss['operating_expenses'], 2) }}</td>
            </tr>
            <tr class="subtotal-row">
                <td><strong>Operating Profit (Gross Profit - Operating Expenses)</strong></td>
                <td class="text-right {{ $profitLoss['operating_profit'] < 0 ? 'negative' : 'positive' }}">
                    <strong>UGX {{ number_format($profitLoss['operating_profit'], 2) }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Other Expenses -->
    <table class="table">
        <thead>
            <tr>
                <th colspan="2">OTHER EXPENSES</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="indent">Maintenance Expenses</td>
                <td class="text-right">UGX {{ number_format($profitLoss['other_expenses']['maintenance'], 2) }}</td>
            </tr>
            <tr>
                <td class="indent">Other Expenses</td>
                <td class="text-right">UGX {{ number_format($profitLoss['other_expenses']['other'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Net Profit -->
    <table class="table net-profit-table">
        <thead>
            <tr>
                <th colspan="2">NET PROFIT</th>
            </tr>
        </thead>
        <tbody>
            <tr class="total-row">
                <td><strong>Net Profit for the Period</strong></td>
                <td class="text-right {{ $profitLoss['net_profit'] < 0 ? 'negative' : 'positive' }}">
                    <strong>UGX {{ number_format($profitLoss['net_profit'], 2) }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Financial Ratios -->
    <div style="margin-top: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 5px;">
        <h4>Financial Ratios:</h4>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td width="50%"><strong>Gross Profit Margin:</strong></td>
                <td>
                    @if($profitLoss['revenue']['total_revenue'] > 0)
                        {{ number_format(($profitLoss['gross_profit'] / $profitLoss['revenue']['total_revenue']) * 100, 2) }}%
                    @else
                        0%
                    @endif
                </td>
            </tr>
            <tr>
                <td><strong>Operating Profit Margin:</strong></td>
                <td>
                    @if($profitLoss['revenue']['total_revenue'] > 0)
                        {{ number_format(($profitLoss['operating_profit'] / $profitLoss['revenue']['total_revenue']) * 100, 2) }}%
                    @else
                        0%
                    @endif
                </td>
            </tr>
            <tr>
                <td><strong>Net Profit Margin:</strong></td>
                <td class="{{ $profitLoss['net_profit'] >= 0 ? 'positive' : 'negative' }}">
                    @if($profitLoss['revenue']['total_revenue'] > 0)
                        {{ number_format(($profitLoss['net_profit'] / $profitLoss['revenue']['total_revenue']) * 100, 2) }}%
                    @else
                        0%
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; font-size: 10px; color: #666;">
        <p>Generated on: {{ now()->format('F j, Y \\a\\t g:i A') }}</p>
        <p>Page 1 of 1</p>
    </div>
</body>
</html>