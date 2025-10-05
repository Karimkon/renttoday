@extends('secretary.layouts.app')

@section('content')
    <h2 class="mb-4">Secretary Dashboard</h2>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <h3>{{ $tenantsCount }}</h3>
                    <p class="text-muted">Active Tenants</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <h3>{{ $apartmentsCount }}</h3>
                    <p class="text-muted">Apartments</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <h3>UGX {{ number_format($paymentsThisMonth, 0) }}</h3>
                    <p class="text-muted">Payments (This Month)</p>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
    <div class="col-md-2">
        <div class="card text-center shadow-sm border-0 bg-success text-white">
            <div class="card-body">
                <h3>{{ $statusCounts['paid'] }}</h3>
                <p>Paid Apartments</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center shadow-sm border-0 bg-warning text-dark">
            <div class="card-body">
                <h3>{{ $statusCounts['partial'] }}</h3>
                <p>Partial Apartments</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center shadow-sm border-0 bg-danger text-white">
            <div class="card-body">
                <h3>{{ $statusCounts['unpaid'] }}</h3>
                <p>Unpaid Apartments</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center shadow-sm border-0 bg-secondary text-white">
            <div class="card-body">
                <h3>{{ $statusCounts['empty'] }}</h3>
                <p>Empty Apartments</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm border-0 bg-info text-white">
            <div class="card-body">
                <h3>UGX {{ number_format($totalDue, 0) }}</h3>
                <p>Total Due Amount</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card text-center shadow-sm border-0 bg-primary text-white">
            <div class="card-body">
                <h3>{{ $paidAhead }}</h3>
                <p>Tenants Paid Ahead</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card text-center shadow-sm border-0 bg-primary text-white">
            <div class="card-body">
                <h3>UGX {{ number_format($paidAheadSum, 0) }}</h3>
                <p>Total Paid Ahead Amount</p>
            </div>
        </div>
    </div>

</div>

@endsection
