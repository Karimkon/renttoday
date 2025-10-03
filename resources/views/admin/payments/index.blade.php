@extends('admin.layouts.app')
@section('title','Payments')

@section('content')
<h3 class="mb-4">💰 Payments Dashboard</h3>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.payments.create') }}" class="btn btn-primary">➕ Add Payment</a>
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
            @foreach($payments as $p)
            <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->tenant->name }}</td>
                <td>{{ $p->apartment?->number ?? 'Unassigned' }}</td>
                <td>{{ \Carbon\Carbon::parse($p->month)->format('d M, Y') }}</td>
                <td>{{ number_format($p->amount) }}</td>
                <td>{{ $p->includes_gym ? 'Yes' : 'No' }}</td>
                <td>
                    <a href="{{ route('admin.payments.edit', $p) }}" class="btn btn-sm btn-warning">✏️ Edit</a>
                    <form action="{{ route('admin.payments.destroy', $p) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger">🗑 Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
