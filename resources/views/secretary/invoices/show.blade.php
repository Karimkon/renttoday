@extends('secretary.layouts.app')
@section('title','Create Invoice')

@section('content')
<table class="table table-bordered table-striped shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Tenant</th>
            <th>Month</th>
            <th>Amount (UGX)</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoices as $invoice)
        <tr>
            <td>{{ $invoice->id }}</td>
            <td>{{ $invoice->tenant->name }}</td>
            <td>{{ \Carbon\Carbon::parse($invoice->month)->format('F, Y') }}</td>
            <td>{{ number_format($invoice->amount) }}</td>
            <td>
                @if($invoice->status == 'paid')
                    <span class="badge bg-success">Paid</span>
                @else
                    <span class="badge bg-warning text-dark">Pending</span>
                @endif
            </td>
            <td>
                <a href="{{ route('secretary.invoices.show', $invoice) }}" class="btn btn-sm btn-info mb-1">View</a>
                <a href="{{ route('secretary.invoices.pdf', $invoice) }}" target="_blank" class="btn btn-sm btn-primary mb-1">PDF</a>

                @if($invoice->status != 'paid')
                <form action="{{ route('secretary.invoices.markPaid', $invoice) }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-success mb-1">Mark Paid & Email</button>
                </form>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@endsection