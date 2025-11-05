{{-- resources/views/admin/financial-reports/balance-sheet.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Balance Sheet')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">BALANCE SHEET</h4>
                <div>
                    <a href="{{ route('admin.financial-reports.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <a href="{{ route('admin.financial-reports.balance-sheet', ['as_of_date' => $balanceSheet['as_of_date'], 'export' => 'pdf']) }}" 
                       class="btn btn-primary">
                        <i class="bi bi-download"></i> Export PDF
                    </a>
                </div>
            </div>
            <p class="text-muted mb-0">As of: {{ \Carbon\Carbon::parse($balanceSheet['as_of_date'])->format('F j, Y') }}</p>
        </div>
        <div class="card-body">
            <!-- Financial Overview Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-light border-0">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Your Cash</h6>
                            <h3 class="text-success">UGX {{ number_format($balanceSheet['assets']['current_assets']['cash'], 2) }}</h3>
                            <small class="text-muted">Commissions & Fees</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light border-0">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Due to Landlords</h6>
                            <h3 class="text-warning">UGX {{ number_format($balanceSheet['liabilities']['current_liabilities']['accounts_payable'], 2) }}</h3>
                            <small class="text-muted">Landlord Share of Rent</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light border-0">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Your Net Worth</h6>
                            <h3 class="text-primary">UGX {{ number_format($balanceSheet['equity']['total_equity'], 2) }}</h3>
                            <small class="text-muted">Total Equity</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Assets -->
                <div class="col-md-6">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-success">
                                <tr>
                                    <th colspan="2" class="bg-success text-white">ASSETS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="table-light">
                                    <td colspan="2" class="fw-bold">Current Assets</td>
                                </tr>
                                <tr>
                                    <td width="70%">
                                        <span>Cash & Bank</span>
                                        <br>
                                        <small class="text-muted">Your commission income & fees</small>
                                    </td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['assets']['current_assets']['cash'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span>Accounts Receivable (Rent)</span>
                                        <br>
                                        <small class="text-muted">Unpaid rent from tenants</small>
                                    </td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['assets']['current_assets']['accounts_receivable'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span>Late Fees Receivable</span>
                                        <br>
                                        <small class="text-muted">Unpaid late fees from tenants</small>
                                    </td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['assets']['current_assets']['late_fees_receivable'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span>Prepaid Expenses</span>
                                        <br>
                                        <small class="text-muted">Expenses paid in advance</small>
                                    </td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['assets']['current_assets']['prepaid_expenses'], 2) }}</td>
                                </tr>
                                <tr class="fw-bold table-active">
                                    <td>Total Current Assets</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['assets']['current_assets']['total_current_assets'], 2) }}</td>
                                </tr>

                                <tr class="table-light">
                                    <td colspan="2" class="fw-bold">Fixed Assets</td>
                                </tr>
                                <tr>
                                    <td>Fixed Assets at Cost</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['assets']['fixed_assets']['fixed_assets'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Less: Accumulated Depreciation</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['assets']['fixed_assets']['accumulated_depreciation'], 2) }}</td>
                                </tr>
                                <tr class="fw-bold table-active">
                                    <td>Net Fixed Assets</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['assets']['fixed_assets']['net_fixed_assets'], 2) }}</td>
                                </tr>

                                <tr class="table-success fw-bold fs-5">
                                    <td>TOTAL ASSETS</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['assets']['total_assets'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Liabilities & Equity -->
                <div class="col-md-6">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-danger">
                                <tr>
                                    <th colspan="2" class="bg-danger text-white">LIABILITIES & EQUITY</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="table-light">
                                    <td colspan="2" class="fw-bold">Current Liabilities</td>
                                </tr>
                                <tr>
                                    <td width="70%">
                                        <span>Accounts Payable (to Landlords)</span>
                                        <br>
                                        <small class="text-muted">Landlord share of collected rent</small>
                                    </td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['liabilities']['current_liabilities']['accounts_payable'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span>Accrued Expenses</span>
                                        <br>
                                        <small class="text-muted">Expenses incurred but not paid</small>
                                    </td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['liabilities']['current_liabilities']['accrued_expenses'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span>Unearned Revenue (Advances)</span>
                                        <br>
                                        <small class="text-muted">Payments for future months</small>
                                    </td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['liabilities']['current_liabilities']['unearned_revenue'], 2) }}</td>
                                </tr>
                                <tr class="fw-bold table-active">
                                    <td>Total Liabilities</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['liabilities']['current_liabilities']['total_liabilities'], 2) }}</td>
                                </tr>

                                <tr class="table-light">
                                    <td colspan="2" class="fw-bold">Equity</td>
                                </tr>
                                <tr>
                                    <td>Opening Equity</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['equity']['opening_equity'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Add: Net Income</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['equity']['net_income'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Less: Drawings</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['equity']['drawings'], 2) }}</td>
                                </tr>
                                <tr class="fw-bold table-active">
                                    <td>Total Equity</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['equity']['total_equity'], 2) }}</td>
                                </tr>

                                <tr class="table-danger fw-bold fs-5">
                                    <td>TOTAL LIABILITIES & EQUITY</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['liabilities']['current_liabilities']['total_liabilities'] + $balanceSheet['equity']['total_equity'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Balance Check -->
                    <div class="alert {{ $balanceSheet['balance_check'] ? 'alert-success' : 'alert-danger' }} mt-3">
                        <h6>Balance Check:</h6>
                        <p class="mb-0">
                            Total Assets: UGX {{ number_format($balanceSheet['assets']['total_assets'], 2) }}<br>
                            Total Liabilities & Equity: UGX {{ number_format($balanceSheet['liabilities']['current_liabilities']['total_liabilities'] + $balanceSheet['equity']['total_equity'], 2) }}<br>
                            <strong>Status: {{ $balanceSheet['balance_check'] ? 'BALANCED ✅' : 'NOT BALANCED ❌' }}</strong>
                        </p>
                    </div>

                    <!-- Financial Explanation -->
                    <div class="alert alert-info mt-3">
                        <h6><i class="bi bi-info-circle"></i> Financial Structure Explanation</h6>
                        <p class="mb-2 small">
                            <strong>Cash Balance:</strong> Represents money owned by the company (commissions & fees only)<br>
                            <strong>Accounts Payable:</strong> Money collected from tenants that belongs to landlords<br>
                            <strong>Your Equity:</strong> Your actual net worth after accounting for landlord obligations
                        </p>
                        <hr class="my-2">
                        <p class="mb-0 small">
                            <strong>Note:</strong> Total rent collected is split between your commissions (revenue) and landlord payouts (liability).
                            This balance sheet shows your true financial position.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Additional Financial Metrics -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Financial Health Indicators</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <h5>{{ $balanceSheet['assets']['current_assets']['cash'] > 0 ? number_format(($balanceSheet['assets']['current_assets']['cash'] / max(1, $balanceSheet['liabilities']['current_liabilities']['accounts_payable'])) * 100, 0) : 0 }}%</h5>
                                    <small class="text-muted">Cash vs Landlord Payables</small>
                                </div>
                                <div class="col-md-3">
                                    <h5 class="{{ $balanceSheet['equity']['total_equity'] > 0 ? 'text-success' : 'text-danger' }}">
                                        UGX {{ number_format($balanceSheet['equity']['total_equity'], 0) }}
                                    </h5>
                                    <small class="text-muted">Net Worth</small>
                                </div>
                                <div class="col-md-3">
                                    <h5>
                                        @if($balanceSheet['assets']['total_assets'] > 0)
                                            {{ number_format(($balanceSheet['liabilities']['current_liabilities']['total_liabilities'] / $balanceSheet['assets']['total_assets']) * 100, 1) }}%
                                        @else
                                            0%
                                        @endif
                                    </h5>
                                    <small class="text-muted">Debt to Assets Ratio</small>
                                </div>
                                <div class="col-md-3">
                                    <h5 class="{{ $balanceSheet['balance_check'] ? 'text-success' : 'text-danger' }}">
                                        {{ $balanceSheet['balance_check'] ? 'BALANCED' : 'UNBALANCED' }}
                                    </h5>
                                    <small class="text-muted">Accounting Status</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table-active {
        background-color: rgba(0,0,0,0.02) !important;
    }
    .card.bg-light {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
</style>
@endpush