{{-- resources/views/admin/payments/index.blade.php --}}
@extends('admin.layouts.app')
@section('title','Payments Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-cash-stack me-2"></i> Payments Dashboard</h3>
        <div class="fw-bold fs-5 text-success">Total Collected: UGX {{ number_format($totalCollected) }}</div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="">All Methods</option>
                        <option value="cash" {{ request('payment_method')=='cash'?'selected':'' }}>Cash</option>
                        <option value="pesapal" {{ request('payment_method')=='pesapal'?'selected':'' }}>Pesapal</option>
                        <option value="bank_transfer" {{ request('payment_method')=='bank_transfer'?'selected':'' }}>Bank Transfer</option>
                        <option value="mobile_money" {{ request('payment_method')=='mobile_money'?'selected':'' }}>Mobile Money</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option>
                        <option value="paid" {{ request('status')=='paid'?'selected':'' }}>Paid</option>
                        <option value="failed" {{ request('status')=='failed'?'selected':'' }}>Failed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Month</label>
                    <input type="month" name="month" class="form-control" value="{{ request('month') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('admin.payments.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Payment
        </a>
        
        <div class="btn-group">
            <a href="{{ route('admin.payments.index', ['status' => 'pending']) }}" class="btn btn-outline-warning">
                Pending Payments
            </a>
            <a href="{{ route('admin.payments.index', ['payment_method' => 'pesapal']) }}" class="btn btn-outline-info">
                Pesapal Payments
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Payments Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Tenant</th>
                            <th>Apartment</th>
                            <th>Landlord</th>
                            <th>Month</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Processed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                        <tr>
                            <td>{{ $payment->id }}</td>
                            <td>{{ $payment->tenant->name }}</td>
                            <td>{{ $payment->apartment?->number ?? 'N/A' }}</td>
                            <td>{{ $payment->apartment?->landlord?->name ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($payment->month)->format('M Y') }}</td>
                            <td class="fw-bold">UGX {{ number_format($payment->amount) }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $payment->payment_method_label }}</span>
                                @if($payment->reference_number)
                                <br><small class="text-muted">{{ $payment->reference_number }}</small>
                                @endif
                            </td>
                            <td>
                                @if($payment->status === 'paid')
                                    <span class="badge bg-success">{{ $payment->status_label }}</span>
                                    <br><small class="text-muted">{{ $payment->paid_at?->format('M j, Y') }}</small>
                                @elseif($payment->status === 'pending')
                                    <span class="badge bg-warning text-dark">{{ $payment->status_label }}</span>
                                @else
                                    <span class="badge bg-danger">{{ $payment->status_label }}</span>
                                @endif
                            </td>
                            <td>{{ $payment->processedBy?->name ?? 'System' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-info" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.payments.edit', $payment) }}" class="btn btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if($payment->status !== 'paid')
                                    <form action="{{ route('admin.payments.mark-paid', $payment) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success" title="Mark as Paid" 
                                                onclick="return confirm('Mark this payment as paid?')">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                    @endif
                                    <form action="{{ route('admin.payments.destroy', $payment) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" title="Delete"
                                                onclick="return confirm('Are you sure?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Showing {{ $payments->firstItem() }} to {{ $payments->lastItem() }} of {{ $payments->total() }} payments
                </div>
                {{ $payments->links() }}
            </div>
        </div>
    </div>
</div>
@endsection