@extends('tenant.layouts.app')

@section('title', 'Tenant Dashboard')

@section('content')
<div class="container my-5">

    <!-- Header -->
    <div class="d-flex align-items-center mb-4">
        <img src="{{ asset('images/apartments.jpg') }}" class="rounded-circle me-3" width="80" height="80" alt="Tenant Logo">
        <div>
            <h3 class="mb-0">Welcome, {{ $tenant->name }}</h3>
            <small class="text-muted">Tenant Dashboard</small>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Apartment</h5>
                    <p class="card-text">{{ $apartment ? $apartment->number : 'Not Assigned' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Next Due Date</h5>
                    <p class="card-text">{{ $nextDue ? $nextDue->format('d M, Y') : 'N/A' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Status</h5>
                    @php
                        $badgeClass = $status === 'paid' ? 'bg-success' : ($status === 'partial' ? 'bg-warning text-dark' : 'bg-danger');
                    @endphp
                    <span class="badge {{ $badgeClass }} p-2">{{ ucfirst($status) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tenant Info -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header">Your Info</div>
        <div class="card-body">
            <p><strong>Email:</strong> {{ $tenant->email }}</p>
            <p><strong>Phone:</strong> {{ $tenant->phone ?? 'N/A' }}</p>
            <p><strong>Credit Balance:</strong> UGX {{ number_format($tenant->credit_balance ?? 0) }}</p>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="card shadow-sm">
        <div class="card-header">Recent Payments</div>
        <div class="card-body p-0">
            @if($payments->isEmpty())
                <p class="p-3 text-center text-muted">No payments found.</p>
            @else
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Month</th>
                                <th>Amount Paid</th>
                                <th>Includes Gym</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($payment->month)->format('M Y') }}</td>
                                <td>UGX {{ number_format($payment->amount) }}</td>
                                <td>{{ $payment->includes_gym ? 'Yes' : 'No' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
