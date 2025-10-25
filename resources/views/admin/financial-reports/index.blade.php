@extends('admin.layouts.app')

@section('title', 'Financial Reports')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Financial Reports</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Income Statement Card -->
        <div class="col-xl-4 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">Income Statement</h4>
                            <p class="text-muted mb-0">Revenue vs Expenses</p>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-success rounded-circle">
                                <i class="bi bi-graph-up-arrow font-22"></i>
                            </span>
                        </div>
                    </div>
                    
                    <form action="{{ route('admin.financial-reports.income-statement') }}" method="GET" class="mt-3">
                        <div class="mb-2">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $defaultStart }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $defaultEnd }}" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">Generate Report</button>
                            <button type="submit" name="export" value="pdf" class="btn btn-outline-success">
                                <i class="bi bi-download"></i> Export PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Balance Sheet Card -->
        <div class="col-xl-4 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">Balance Sheet</h4>
                            <p class="text-muted mb-0">Assets = Liabilities + Equity</p>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-primary rounded-circle">
                                <i class="bi bi-wallet2 font-22"></i>
                            </span>
                        </div>
                    </div>
                    
                    <form action="{{ route('admin.financial-reports.balance-sheet') }}" method="GET" class="mt-3">
                        <div class="mb-3">
                            <label class="form-label">As Of Date</label>
                            <input type="date" name="as_of_date" class="form-control" value="{{ $defaultBalanceDate }}" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Generate Report</button>
                            <button type="submit" name="export" value="pdf" class="btn btn-outline-primary">
                                <i class="bi bi-download"></i> Export PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Profit & Loss Card -->
        <div class="col-xl-4 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">Profit & Loss</h4>
                            <p class="text-muted mb-0">Detailed P&L Statement</p>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-info rounded-circle">
                                <i class="bi bi-cash-coin font-22"></i>
                            </span>
                        </div>
                    </div>
                    
                    <form action="{{ route('admin.financial-reports.profit-loss') }}" method="GET" class="mt-3">
                        <div class="mb-2">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $defaultStart }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $defaultEnd }}" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-info">Generate Report</button>
                            <button type="submit" name="export" value="pdf" class="btn btn-outline-info">
                                <i class="bi bi-download"></i> Export PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Expense Management</h5>
                            <p class="text-muted">Track and manage all business expenses</p>
                            <a href="{{ route('admin.financial-reports.expenses') }}" class="btn btn-outline-primary">
                                <i class="bi bi-receipt"></i> Manage Expenses
                            </a>
                        </div>
                        <div class="col-md-6">
                            <h5>Quick Reports</h5>
                            <p class="text-muted">Generate common period reports</p>
                            <div class="btn-group">
                                <a href="{{ route('admin.financial-reports.income-statement', ['start_date' => now()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->endOfMonth()->format('Y-m-d')]) }}" 
                                   class="btn btn-outline-secondary btn-sm">This Month</a>
                                <a href="{{ route('admin.financial-reports.income-statement', ['start_date' => now()->startOfQuarter()->format('Y-m-d'), 'end_date' => now()->endOfQuarter()->format('Y-m-d')]) }}" 
                                   class="btn btn-outline-secondary btn-sm">This Quarter</a>
                                <a href="{{ route('admin.financial-reports.income-statement', ['start_date' => now()->startOfYear()->format('Y-m-d'), 'end_date' => now()->endOfYear()->format('Y-m-d')]) }}" 
                                   class="btn btn-outline-secondary btn-sm">This Year</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection