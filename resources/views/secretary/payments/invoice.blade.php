@extends('secretary.layouts.app')
@section('title','Invoice')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>🧾 Invoice #{{ $payment->id }}</h3>
    <a href="{{ route('secretary.payments.index') }}" class="btn btn-secondary">Back to Payments</a>
</div>

<div class="card shadow-sm p-4 mb-4">
    <div class="row">
        <div class="col-md-6">
            <h5>Tenant Details</h5>
            <p>
                <strong>Name:</strong> {{ $payment->tenant->name }}<br>
                <strong>Email:</strong> {{ $payment->tenant->email }}<br>
                <strong>Phone:</strong> {{ $payment->tenant->phone ?? '-' }}
            </p>
        </div>
        <div class="col-md-6 text-end">
            <h5>Apartment Details</h5>
            @if($payment->apartment)
            <p>
                <strong>Apartment #:</strong> {{ $payment->apartment->number }}<br>
                <strong>Rent:</strong> UGX {{ number_format($payment->apartment->rent) }}
            </p>
            @else
            <p><em>Unassigned Apartment</em></p>
            @endif
        </div>
    </div>

    <hr>

    <h5>Payment Info</h5>
    <table class="table table-bordered">
        <tr>
            <th>Month</th>
            <td>{{ \Carbon\Carbon::parse($payment->month)->format('F, Y') }}</td>
        </tr>
        <tr>
            <th>Amount</th>
            <td>UGX {{ number_format($payment->amount) }}</td>
        </tr>
        <tr>
            <th>Gym Included</th>
            <td>{{ $payment->includes_gym ? 'Yes' : 'No' }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td>
                @if($payment->status == 'paid')
                    <span class="badge bg-success">Paid</span>
                @else
                    <span class="badge bg-warning text-dark">Pending</span>
                @endif
            </td>
        </tr>
    </table>

    @if($payment->status != 'paid')
    <form action="{{ route('secretary.payments.markPaid', $payment) }}" method="POST">
        @csrf
        <button class="btn btn-success mt-3">✅ Mark as Paid & Send Email</button>
    </form>
    @endif
</div>
@endsection
