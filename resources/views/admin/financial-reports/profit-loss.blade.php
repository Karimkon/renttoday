{{-- resources/views/admin/financial-reports/profit-loss.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Profit & Loss Statement')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">PROFIT & LOSS STATEMENT</h4>
                <div>
                    <a href="{{ route('admin.financial-reports.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <a href="{{ route('admin.financial-reports.profit-loss', ['start_date' => $profitLoss['period']['start'], 'end_date' => $profitLoss['period']['end'], 'export' => 'pdf']) }}" 
                       class="btn btn-primary">
                        <i class="bi bi-download"></i> Export PDF
                    </a>
                </div>
            </div>
            <p class="text-muted mb-0">
                For the period: {{ \Carbon\Carbon::parse($profitLoss['period']['start'])->format('F j, Y') }} to {{ \Carbon\Carbon::parse($profitLoss['period']['end'])->format('F j, Y') }}
            </p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <!-- REVENUE -->
                        <tr class="table-success">
                            <th colspan="2" class="bg-success text-white">REVENUE</th>
                        </tr>
                        <tr>
                            <td width="70%">Rental Income</td>
                            <td class="text-end">UGX {{ number_format($profitLoss['revenue']['rental_income'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Commission Income</td>
                            <td class="text-end">UGX {{ number_format($profitLoss['revenue']['commission_income'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Late Fee Income</td>
                            <td class="text-end">UGX {{ number_format($profitLoss['revenue']['late_fee_income'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Other Income</td>
                            <td class="text-end">UGX {{ number_format($profitLoss['revenue']['other_income'], 2) }}</td>
                        </tr>
                        <tr class="fw-bold table-light">
                            <td>TOTAL REVENUE</td>
                            <td class="text-end">UGX {{ number_format($profitLoss['revenue']['total_revenue'], 2) }}</td>
                        </tr>

                        <!-- COST OF SALES -->
                        <tr class="table-warning">
                            <th colspan="2" class="bg-warning text-dark">COST OF SALES</th>
                        </tr>
                        <tr>
                            <td>Direct Property Costs</td>
                            <td class="text-end">UGX {{ number_format($profitLoss['cost_of_sales'], 2) }}</td>
                        </tr>
                        <tr class="fw-bold table-light">
                            <td>GROSS PROFIT</td>
                            <td class="text-end">UGX {{ number_format($profitLoss['gross_profit'], 2) }}</td>
                        </tr>

                        <!-- OPERATING EXPENSES -->
                        <tr class="table-danger">
                            <th colspan="2" class="bg-danger text-white">OPERATING EXPENSES</th>
                        </tr>
                        <tr>
                            <td>Operating & Administrative Expenses</td>
                            <td class="text-end">UGX {{ number_format($profitLoss['operating_expenses'], 2) }}</td>
                        </tr>
                        <tr class="fw-bold table-light">
                            <td>OPERATING PROFIT</td>
                            <td class="text-end">UGX {{ number_format($profitLoss['operating_profit'], 2) }}</td>
                        </tr>

                        <!-- OTHER EXPENSES -->
                        <tr class="table-info">
                            <th colspan="2" class="bg-info text-white">OTHER EXPENSES</th>
                        </tr>
                        <tr>
                            <td>Maintenance Expenses</td>
                            <td class="text-end">UGX {{ number_format($profitLoss['other_expenses']['maintenance'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Other Expenses</td>
                            <td class="text-end">UGX {{ number_format($profitLoss['other_expenses']['other'], 2) }}</td>
                        </tr>

                        <!-- NET PROFIT -->
                        <tr class="{{ $profitLoss['net_profit'] >= 0 ? 'table-success' : 'table-danger' }} fw-bold fs-5">
                            <td>NET {{ $profitLoss['net_profit'] >= 0 ? 'PROFIT' : 'LOSS' }} FOR THE PERIOD</td>
                            <td class="text-end">UGX {{ number_format(abs($profitLoss['net_profit']), 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Profitability Ratios -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6>Gross Profit Margin</h6>
                            <h3>{{ $profitLoss['revenue']['total_revenue'] > 0 ? number_format(($profitLoss['gross_profit'] / $profitLoss['revenue']['total_revenue']) * 100, 1) : 0 }}%</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6>Operating Margin</h6>
                            <h3>{{ $profitLoss['revenue']['total_revenue'] > 0 ? number_format(($profitLoss['operating_profit'] / $profitLoss['revenue']['total_revenue']) * 100, 1) : 0 }}%</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card {{ $profitLoss['net_profit'] >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
                        <div class="card-body text-center">
                            <h6>Net Profit Margin</h6>
                            <h3>{{ $profitLoss['revenue']['total_revenue'] > 0 ? number_format(($profitLoss['net_profit'] / $profitLoss['revenue']['total_revenue']) * 100, 1) : 0 }}%</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection