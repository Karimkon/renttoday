@extends('admin.layouts.app')
@section('title','Payments Dashboard')

@section('content')
<h3 class="mb-4">💰 Payments Dashboard</h3>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.payments.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Payment</a>
    <div class="fw-bold fs-5">Total Collected: UGX {{ number_format($totalCollected) }}</div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="table-responsive shadow-sm">
    <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Tenant</th>
                <th>Apartment</th>
                <th>Month</th>
                <th>Amount (UGX)</th>
                <th>Gym Included</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td>{{ $payment->id }}</td>
                <td>{{ $payment->tenant->name }}</td>
                <td>{{ $payment->apartment?->number ?? 'Unassigned' }}</td>
                <td>{{ \Carbon\Carbon::parse($payment->month)->format('d M, Y') }}</td>
                <td>{{ number_format($payment->amount) }}</td>
                <td>{{ $payment->includes_gym ? 'Yes' : 'No' }}</td>
                <td>
                    <a href="{{ route('admin.payments.edit', $payment) }}" class="btn btn-sm btn-warning mb-1">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <form action="{{ route('admin.payments.destroy', $payment) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger mb-1">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
