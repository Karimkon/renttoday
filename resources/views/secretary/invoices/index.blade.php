@extends('secretary.layouts.app')
@section('title','Invoices')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">🧾 Invoices</h3>
        <a href="{{ route('secretary.invoices.create') }}" class="btn btn-primary">
            ➕ Create Invoice
        </a>
    </div>

    <div class="table-responsive shadow-sm rounded">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-primary">
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
                    <td>UGX {{ number_format($invoice->amount) }}</td>
                    <td>
                        @if($invoice->status == 'paid')
                            <span class="badge bg-success">Paid</span>
                        @else
                            <span class="badge bg-warning text-dark">Pending</span>
                        @endif
                    </td>
                    <td class="d-flex flex-wrap gap-1">
                        <a href="{{ route('secretary.invoices.show', $invoice) }}" class="btn btn-sm btn-info">View</a>
                        <a href="{{ route('secretary.invoices.pdf', $invoice) }}" target="_blank" class="btn btn-sm btn-primary">PDF</a>

                        <form action="{{ route('secretary.invoices.destroy', $invoice) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this invoice?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </form>

                        @if($invoice->status != 'paid')
                        <form action="{{ route('secretary.invoices.markPaid', $invoice) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-success">Mark Paid & Email</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<style>
    /* Table hover and shadow */
    .table-hover tbody tr:hover {
        background-color: #e2f0fb;
        transition: background-color 0.2s ease-in-out;
    }

    /* Badge styles */
    .badge {
        font-size: 0.85rem;
        padding: 0.4em 0.6em;
    }

    /* Buttons spacing and hover effect */
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
        transition: all 0.2s;
    }
    .btn-sm:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    }

    /* Responsive table */
    @media (max-width: 768px) {
        .d-flex.flex-wrap.gap-1 {
            flex-direction: column;
        }
        .btn-sm {
            width: 100%;
        }
    }
</style>
@endsection
