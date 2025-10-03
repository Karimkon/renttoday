@extends('secretary.layouts.app')
@section('title','Payments')

@section('content')
<h3 class="mb-4">Payments</h3>
<a href="{{ route('secretary.payments.create') }}" class="btn btn-primary mb-3"><i class="bi bi-plus-circle"></i> Add Payment</a>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

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
