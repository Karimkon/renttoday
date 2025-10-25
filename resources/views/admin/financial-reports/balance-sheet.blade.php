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
                                    <td width="70%">Cash & Bank</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['assets']['current_assets']['cash'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Accounts Receivable (Rent)</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['assets']['current_assets']['accounts_receivable'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Late Fees Receivable</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['assets']['current_assets']['late_fees_receivable'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Prepaid Expenses</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['assets']['current_assets']['prepaid_expenses'], 2) }}</td>
                                </tr>
                                <tr class="fw-bold">
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
                                <tr class="fw-bold">
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
                                    <td width="70%">Accounts Payable (to Landlords)</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['liabilities']['current_liabilities']['accounts_payable'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Accrued Expenses</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['liabilities']['current_liabilities']['accrued_expenses'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Unearned Revenue (Advances)</td>
                                    <td class="text-end">UGX {{ number_format($balanceSheet['liabilities']['current_liabilities']['unearned_revenue'], 2) }}</td>
                                </tr>
                                <tr class="fw-bold">
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
                                <tr class="fw-bold">
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection