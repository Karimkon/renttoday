@extends('secretary.layouts.app')
@section('title','Payments')

@section('content')
<h3 class="mb-4">Payments</h3>
<a href="{{ route('secretary.payments.create') }}" class="btn btn-primary mb-3"><i class="bi bi-plus-circle"></i> Add Payment</a>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
<form method="GET" class="row g-3 mb-3">
    <div class="col-md-3">
        <select name="tenant_id" class="form-select">
            <option value="">-- Select Tenant --</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" {{ request('tenant_id') == $tenant->id ? 'selected' : '' }}>
                    {{ $tenant->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <input type="month" name="from_month" value="{{ request('from_month') }}" class="form-control" placeholder="From Month">
    </div>

    <div class="col-md-3">
        <input type="month" name="to_month" value="{{ request('to_month') }}" class="form-control" placeholder="To Month">
    </div>

    <div class="col-md-3">
        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
        <a href="{{ route('secretary.payments.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
    </div>
</form>

<table class="table table-bordered table-striped shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Tenant</th>
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
            <td>{{ \Carbon\Carbon::parse($payment->month)->format('d M, Y') }}</td>
            <td>{{ number_format($payment->amount) }}</td>
            <td>{{ $payment->includes_gym ? 'Yes' : 'No' }}</td>
            <td>
                <a href="{{ route('secretary.payments.edit', $payment) }}" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i> Edit</a>
                <form action="{{ route('secretary.payments.destroy', $payment) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection