<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Income Statement - {{ \Carbon\Carbon::parse($incomeStatement['period']['start'])->format('M j, Y') }} to {{ \Carbon\Carbon::parse($incomeStatement['period']['end'])->format('M j, Y') }}</title>
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
        .revenue-table th { background-color: #d1ecf1; }
        .expenses-table th { background-color: #f8d7da; }
        .summary-table th { background-color: #d4edda; }
        .company-info { margin-bottom: 20px; }
        .negative { color: #dc3545; }
        .positive { color: #28a745; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <h1>{{ config('app.name', 'Property Management System') }}</h1>
            <p>Income Statement</p>
        </div>
        <p>For the period: <strong>{{ \Carbon\Carbon::parse($incomeStatement['period']['start'])->format('F j, Y') }} to {{ \Carbon\Carbon::parse($incomeStatement['period']['end'])->format('F j, Y') }}</strong></p>
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
                <td class="text-right">UGX {{ number_format($incomeStatement['revenue']['rental_income'], 2) }}</td>
            </tr>
            <tr>
                <td>Commission Income</td>
                <td class="text-right">UGX {{ number_format($incomeStatement['revenue']['commission_income'], 2) }}</td>
            </tr>
            <tr>
                <td>Other Income</td>
                <td class="text-right">UGX {{ number_format($incomeStatement['revenue']['other_income'], 2) }}</td>
            </tr>
            <tr class="total-row">
                <td><strong>Total Revenue</strong></td>
                <td class="text-right"><strong>UGX {{ number_format($incomeStatement['revenue']['total_revenue'], 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Expenses Section -->
    <table class="table expenses-table">
        <thead>
            <tr>
                <th colspan="2">EXPENSES</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td width="70%">Operating Expenses</td>
                <td class="text-right">UGX {{ number_format($incomeStatement['expenses']['operating'], 2) }}</td>
            </tr>
            <tr>
                <td>Administrative Expenses</td>
                <td class="text-right">UGX {{ number_format($incomeStatement['expenses']['administrative'], 2) }}</td>
            </tr>
            <tr>
                <td>Maintenance Expenses</td>
                <td class="text-right">UGX {{ number_format($incomeStatement['expenses']['maintenance'], 2) }}</td>
            </tr>
            <tr>
                <td>Other Expenses</td>
                <td class="text-right">UGX {{ number_format($incomeStatement['expenses']['other'], 2) }}</td>
            </tr>
            <tr class="total-row">
                <td><strong>Total Expenses</strong></td>
                <td class="text-right"><strong>UGX {{ number_format($incomeStatement['expenses']['total_expenses'], 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Net Income Summary -->
    <table class="table summary-table">
        <thead>
            <tr>
                <th colspan="2">SUMMARY</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td width="70%"><strong>Total Revenue</strong></td>
                <td class="text-right"><strong>UGX {{ number_format($incomeStatement['revenue']['total_revenue'], 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Total Expenses</strong></td>
                <td class="text-right"><strong>UGX {{ number_format($incomeStatement['expenses']['total_expenses'], 2) }}</strong></td>
            </tr>
            <tr class="total-row">
                <td><strong>NET INCOME</strong></td>
                <td class="text-right {{ $incomeStatement['net_income'] < 0 ? 'negative' : 'positive' }}">
                    <strong>UGX {{ number_format($incomeStatement['net_income'], 2) }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Performance Indicator -->
    <div style="margin-top: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 5px;">
        <h4>Performance Summary:</h4>
        <p>
            <strong>Profit Margin:</strong> 
            @if($incomeStatement['revenue']['total_revenue'] > 0)
                {{ number_format(($incomeStatement['net_income'] / $incomeStatement['revenue']['total_revenue']) * 100, 2) }}%
            @else
                0%
            @endif
            <br>
            <strong>Status:</strong> 
            <span class="{{ $incomeStatement['net_income'] >= 0 ? 'positive' : 'negative' }}">
                {{ $incomeStatement['net_income'] >= 0 ? 'Profitable' : 'Loss Making' }}
            </span>
        </p>
    </div>

    <!-- Footer -->
    <div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; font-size: 10px; color: #666;">
        <p>Generated on: {{ now()->format('F j, Y \\a\\t g:i A') }}</p>
        <p>Page 1 of 1</p>
    </div>
</body>
</html>