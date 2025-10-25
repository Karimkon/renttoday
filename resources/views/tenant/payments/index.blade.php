@extends('tenant.layouts.app')

@section('title', 'My Payments')

@section('content')
<div class="container">
    <h3>My Payments</h3>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php
        $tenant = auth()->user()->tenant;
        $nextMonth = now()->startOfMonth();
        $hasPayments = !$payments->isEmpty();
    @endphp

    @if(!$hasPayments)
        <div class="alert alert-info">No payments found. You can pay your next month's rent now.</div>
        <form action="{{ route('tenant.payments.pay', ['payment' => 0]) }}" method="POST">
            @csrf
            <input type="hidden" name="amount" value="{{ $tenant->apartment->rent }}">
            <input type="hidden" name="month" value="{{ $nextMonth->format('Y-m-d') }}">
            <button class="btn btn-primary btn-sm">Pay Now</button>
        </form>
    @else
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Amount (UGX)</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($payment->month)->format('F Y') }}</td>
                        <td>{{ number_format($payment->amount) }}</td>
                        <td>
                            @if($payment->status === 'paid')
                                <span class="badge bg-success">Paid</span>
                            @else
                                <span class="badge bg-warning text-dark">Unpaid</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->status === 'paid')
                                <span class="badge bg-success">Paid</span>
                            @elseif(\Carbon\Carbon::parse($payment->month)->lessThanOrEqualTo(now()))
                                <form action="{{ route('tenant.payments.pay', $payment) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-primary btn-sm">Pay Now</button>
                                </form>
                            @else
                                <span class="text-muted">Future</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
