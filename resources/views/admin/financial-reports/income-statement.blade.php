@extends('admin.layouts.app')

@section('title', 'Income Statement')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">INCOME STATEMENT</h4>
                        <div>
                            <a href="{{ route('admin.financial-reports.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                            <a href="{{ route('admin.financial-reports.income-statement', ['start_date' => $incomeStatement['period']['start'], 'end_date' => $incomeStatement['period']['end'], 'export' => 'pdf']) }}" 
                               class="btn btn-primary">
                                <i class="bi bi-download"></i> Export PDF
                            </a>
                        </div>
                    </div>
                    <p class="text-muted mb-0">
                        For the period: {{ \Carbon\Carbon::parse($incomeStatement['period']['start'])->format('F j, Y') }} to {{ \Carbon\Carbon::parse($incomeStatement['period']['end'])->format('F j, Y') }}
                    </p>
                </div>
                <div class="card-body">
                    <!-- Revenue Section -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th colspan="2" class="bg-success text-white">REVENUE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td width="70%">Rental Income</td>
                                    <td class="text-end">UGX {{ number_format($incomeStatement['revenue']['rental_income'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Commission Income</td>
                                    <td class="text-end">UGX {{ number_format($incomeStatement['revenue']['commission_income'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Other Income</td>
                                    <td class="text-end">UGX {{ number_format($incomeStatement['revenue']['other_income'], 2) }}</td>
                                </tr>
                                <tr class="table-success fw-bold">
                                    <td>TOTAL REVENUE</td>
                                    <td class="text-end">UGX {{ number_format($incomeStatement['revenue']['total_revenue'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Expenses Section -->
                    <div class="table-responsive mt-4">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th colspan="2" class="bg-danger text-white">EXPENSES</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td width="70%">Operating Expenses</td>
                                    <td class="text-end">UGX {{ number_format($incomeStatement['expenses']['operating'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Administrative Expenses</td>
                                    <td class="text-end">UGX {{ number_format($incomeStatement['expenses']['administrative'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Maintenance Expenses</td>
                                    <td class="text-end">UGX {{ number_format($incomeStatement['expenses']['maintenance'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Other Expenses</td>
                                    <td class="text-end">UGX {{ number_format($incomeStatement['expenses']['other'], 2) }}</td>
                                </tr>
                                <tr class="table-danger fw-bold">
                                    <td>TOTAL EXPENSES</td>
                                    <td class="text-end">UGX {{ number_format($incomeStatement['expenses']['total_expenses'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Net Income -->
                    <div class="table-responsive mt-4">
                        <table class="table table-bordered">
                            <tbody>
                                <tr class="{{ $incomeStatement['net_income'] >= 0 ? 'table-success' : 'table-danger' }} fw-bold fs-5">
                                    <td width="70%">NET {{ $incomeStatement['net_income'] >= 0 ? 'INCOME' : 'LOSS' }}</td>
                                    <td class="text-end">UGX {{ number_format(abs($incomeStatement['net_income']), 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6>Total Revenue</h6>
                                    <h3>UGX {{ number_format($incomeStatement['revenue']['total_revenue'], 0) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h6>Total Expenses</h6>
                                    <h3>UGX {{ number_format($incomeStatement['expenses']['total_expenses'], 0) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card {{ $incomeStatement['net_income'] >= 0 ? 'bg-info' : 'bg-warning' }} text-white">
                                <div class="card-body text-center">
                                    <h6>Net {{ $incomeStatement['net_income'] >= 0 ? 'Income' : 'Loss' }}</h6>
                                    <h3>UGX {{ number_format(abs($incomeStatement['net_income']), 0) }}</h3>
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