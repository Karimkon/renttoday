@extends('admin.layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">🏢 Dashboard Overview</h1>
            <p class="text-muted mb-0">Welcome back, {{ auth()->user()->name }}! Here's what's happening today.</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-primary fs-6">{{ now()->format('l, F j, Y') }}</span>
        </div>
    </div>

    <!-- Key Metrics Grid -->
    <div class="row g-4 mb-4">
        <!-- Revenue Metrics -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                UGX {{ number_format($totalRevenue) }}
                            </div>
                            <div class="text-success small">
                                <i class="bi bi-arrow-up"></i> UGX {{ number_format($monthlyRevenue) }} this month
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-currency-dollar fs-1 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Apartments Metrics -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Apartments</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $totalApartments }} Total
                            </div>
                            <div class="text-success small">
                                <i class="bi bi-house-check"></i> {{ $occupancyRate }}% Occupied
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-building fs-1 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tenants Metrics -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Tenants</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $totalTenants }} Registered
                            </div>
                            <div class="text-info small">
                                <i class="bi bi-person-check"></i> {{ $tenantsWithApt }} Assigned
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people fs-1 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Payments -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Payments</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $pendingPayments }}
                            </div>
                            <div class="text-warning small">
                                <i class="bi bi-clock"></i> Needs attention
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-hourglass-split fs-1 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Revenue vs Expenses -->
        <div class="col-xl-8">
            <div class="card shadow h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Revenue vs Expenses (Last 12 Months)</h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                                data-bs-toggle="dropdown">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Last 6 Months</a></li>
                            <li><a class="dropdown-item" href="#">This Year</a></li>
                            <li><a class="dropdown-item" href="#">All Time</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="revenueExpenseChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-xl-4">
            <div class="row g-3 h-100">
                <div class="col-12">
                    <div class="card shadow h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-graph-up-arrow fs-1 text-success mb-2"></i>
                            <h4 class="text-success">{{ $collectionEfficiency }}%</h4>
                            <p class="text-muted mb-0">Collection Efficiency</p>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card shadow h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-person-badge fs-1 text-info mb-2"></i>
                            <h4 class="text-info">{{ $totalLandlords }}</h4>
                            <p class="text-muted mb-0">Active Landlords</p>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card shadow h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-cash-coin fs-1 text-warning mb-2"></i>
                            <h4 class="text-warning">UGX {{ number_format($outstandingLateFees) }}</h4>
                            <p class="text-muted mb-0">Outstanding Late Fees</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Analytics Row -->
    <div class="row g-4 mb-4">
        <!-- Apartment Distribution -->
        <div class="col-xl-4 col-md-6">
            <div class="card shadow h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-success">Apartment Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="apartmentChart" height="200"></canvas>
                    <div class="mt-3 text-center">
                        <span class="badge bg-success me-2">Occupied: {{ $occupied }}</span>
                        <span class="badge bg-secondary">Empty: {{ $empty }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Status -->
        <div class="col-xl-4 col-md-6">
            <div class="card shadow h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-info">Payment Status</h6>
                </div>
                <div class="card-body">
                    <canvas id="paymentStatusChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Tenant Distribution -->
        <div class="col-xl-4 col-md-6">
            <div class="card shadow h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-warning">Tenant Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="tenantChart" height="200"></canvas>
                    <div class="mt-3 text-center">
                        <small class="text-muted">Total Credit: UGX {{ number_format($totalCredit) }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Row -->
    <div class="row g-4">
        <!-- Recent Payments -->
        <div class="col-xl-8">
            <div class="card shadow h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Payments</h6>
                    <a href="{{ route('admin.payments.index') }}" class="btn btn-sm btn-outline-primary">
                        View All <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tenant</th>
                                    <th>Apartment</th>
                                    <th>Amount</th>
                                    <th>Month</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentPayments as $payment)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-light rounded me-2">
                                                <i class="bi bi-person fs-5 text-dark"></i>
                                            </div>
                                            <div>
                                                <strong>{{ $payment->tenant->name }}</strong>
                                                <br><small class="text-muted">{{ $payment->payment_method }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $payment->apartment?->number ?? 'N/A' }}</span>
                                    </td>
                                    <td class="fw-bold text-success">UGX {{ number_format($payment->amount) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($payment->month)->format('M Y') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $payment->status === 'paid' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </td>
                                    <td><small class="text-muted">{{ $payment->created_at->format('M j, H:i') }}</small></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Recent Apartments -->
        <div class="col-xl-4">
            <div class="card shadow h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-success">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.payments.create') }}" class="btn btn-primary btn-lg">
                            <i class="bi bi-plus-circle me-2"></i>Record Payment
                        </a>
                        <a href="{{ route('admin.apartments.create') }}" class="btn btn-success btn-lg">
                            <i class="bi bi-building me-2"></i>Add Apartment
                        </a>
                        <a href="{{ route('admin.tenants.create') }}" class="btn btn-info btn-lg">
                            <i class="bi bi-person-plus me-2"></i>Add Tenant
                        </a>
                        <a href="{{ route('admin.financial-reports.index') }}" class="btn btn-warning btn-lg">
                            <i class="bi bi-graph-up me-2"></i>View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Revenue vs Expenses Chart
    const revenueExpenseCtx = document.getElementById('revenueExpenseChart').getContext('2d');
    new Chart(revenueExpenseCtx, {
        type: 'line',
        data: {
            labels: @json($months),
            datasets: [
                {
                    label: 'Revenue',
                    data: @json($monthlyRevenueData),
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Expenses',
                    data: @json($monthlyExpenses),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'UGX ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Apartment Distribution Chart
    const aptCtx = document.getElementById('apartmentChart').getContext('2d');
    new Chart(aptCtx, {
        type: 'doughnut',
        data: {
            labels: ['Occupied', 'Empty'],
            datasets: [{
                data: [{{ $occupied }}, {{ $empty }}],
                backgroundColor: ['#198754', '#6c757d'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '70%'
        }
    });

    // Payment Status Chart
    const paymentCtx = document.getElementById('paymentStatusChart').getContext('2d');
    new Chart(paymentCtx, {
        type: 'pie',
        data: {
            labels: ['Paid', 'Pending', 'Failed'],
            datasets: [{
                data: [{{ $paymentStatus['Paid'] }}, {{ $paymentStatus['Pending'] }}, {{ $paymentStatus['Failed'] }}],
                backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Tenant Distribution Chart
    const tenantCtx = document.getElementById('tenantChart').getContext('2d');
    new Chart(tenantCtx, {
        type: 'doughnut',
        data: {
            labels: ['With Apartment', 'Without Apartment'],
            datasets: [{
                data: [{{ $tenantsWithApt }}, {{ $tenantsWithoutApt }}],
                backgroundColor: ['#0dcaf0', '#6c757d'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '70%'
        }
    });
</script>

<style>
    .card {
        border: none;
        border-radius: 0.75rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
    }
    
    .border-left-primary { border-left: 4px solid #4e73df !important; }
    .border-left-success { border-left: 4px solid #1cc88a !important; }
    .border-left-info { border-left: 4px solid #36b9cc !important; }
    .border-left-warning { border-left: 4px solid #f6c23e !important; }
    
    .avatar-sm {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endpush

@endsection