@extends('admin.layouts.app')
@section('title','Admin Dashboard')

@section('content')
<h3 class="mb-4">🏢 Admin Dashboard</h3>

{{-- Top Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm p-3 bg-primary text-white">
            <h6>Total Users</h6>
            <h3>{{ $totalUsers }}</h3>
            <i class="bi bi-people fs-3"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm p-3 bg-success text-white">
            <h6>Total Admins</h6>
            <h3>{{ $totalAdmins }}</h3>
            <i class="bi bi-shield-lock fs-3"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm p-3 bg-warning text-dark">
            <h6>Total Staff</h6>
            <h3>{{ $totalStaff }}</h3>
            <i class="bi bi-briefcase fs-3"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm p-3 bg-info text-dark">
            <h6>Total Revenue</h6>
            <h3>UGX {{ number_format($totalRevenue) }}</h3>
            <i class="bi bi-cash fs-3"></i>
        </div>
    </div>
</div>

{{-- Apartments & Tenants --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm p-3">
            <h6>Apartments Overview</h6>
            <canvas id="apartmentChart" height="200"></canvas>
            <div class="mt-2 d-flex justify-content-between">
                <span>Occupied: {{ $occupied }}</span>
                <span>Empty: {{ $empty }}</span>
                <span>Total: {{ $totalApartments }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm p-3">
            <h6>Tenants Overview</h6>
            <canvas id="tenantChart" height="200"></canvas>
            <div class="mt-2 d-flex justify-content-between">
                <span>With Apartment: {{ $tenantsWithApt }}</span>
                <span>Without Apartment: {{ $tenantsWithoutApt }}</span>
                <span>Total Credit: UGX {{ number_format($totalCredit) }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Monthly Revenue --}}
<div class="row g-3 mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm p-3">
            <h6>Monthly Revenue (Last 12 months)</h6>
            <canvas id="revenueChart" height="120"></canvas>
        </div>
    </div>
</div>

{{-- Payments Table --}}
<div class="row g-3">
    <div class="col-md-12">
        <div class="card shadow-sm p-3">
            <h6>Recent Payments</h6>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Tenant</th>
                            <th>Apartment</th>
                            <th>Month</th>
                            <th>Amount (UGX)</th>
                            <th>Gym Included</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments->take(10) as $payment)
                        <tr>
                            <td>{{ $payment->id }}</td>
                            <td>{{ $payment->tenant->name }}</td>
                            <td>{{ $payment->apartment?->number ?? 'Unassigned' }}</td>
                            <td>{{ \Carbon\Carbon::parse($payment->month)->format('M Y') }}</td>
                            <td>{{ number_format($payment->amount) }}</td>
                            <td>{{ $payment->includes_gym ? 'Yes' : 'No' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Chart.js Scripts --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Apartment Status Pie Chart
    const aptCtx = document.getElementById('apartmentChart').getContext('2d');
    new Chart(aptCtx, {
        type: 'doughnut',
        data: {
            labels: ['Occupied','Empty'],
            datasets: [{
                data: [{{ $occupied }}, {{ $empty }}],
                backgroundColor: ['#198754','#6c757d'],
                borderWidth: 1
            }]
        },
        options: { responsive:true, plugins:{ legend:{position:'bottom'} } }
    });

    // Tenant Status Pie Chart
    const tenantCtx = document.getElementById('tenantChart').getContext('2d');
    new Chart(tenantCtx, {
        type: 'doughnut',
        data: {
            labels: ['With Apartment','Without Apartment'],
            datasets: [{
                data: [{{ $tenantsWithApt }}, {{ $tenantsWithoutApt }}],
                backgroundColor: ['#0dcaf0','#6c757d'],
                borderWidth: 1
            }]
        },
        options: { responsive:true, plugins:{ legend:{position:'bottom'} } }
    });

    // Monthly Revenue Bar Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: @json($months),
            datasets: [{
                label: 'Revenue (UGX)',
                data: @json($monthlyRevenue),
                backgroundColor: '#ffc107'
            }]
        },
        options: { responsive:true, plugins:{ legend:{ display:false } } }
    });
</script>
@endpush

@endsection
