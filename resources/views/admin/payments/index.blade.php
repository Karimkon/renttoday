{{-- resources/views/admin/payments/index.blade.php --}}
@extends('admin.layouts.app')
@section('title','Payments Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-cash-stack me-2"></i> Payments Dashboard</h3>
        <div class="fw-bold fs-5 text-success">Total Collected: UGX {{ number_format($totalCollected) }}</div>
    </div>

    <!-- Extended Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <!-- Basic Filters -->
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
                        <option value="refunded" {{ request('status')=='refunded'?'selected':'' }}>Refunded</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Month</label>
                    <input type="month" name="month" class="form-control" value="{{ request('month') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>

                <!-- Extended Search Filters -->
                <div class="col-md-3">
                    <label class="form-label">Tenant Name</label>
                    <input type="text" name="tenant_search" class="form-control" placeholder="Search tenant..." value="{{ request('tenant_search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Apartment No</label>
                    <input type="text" name="apartment_search" class="form-control" placeholder="Search apartment..." value="{{ request('apartment_search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Landlord Name</label>
                    <input type="text" name="landlord_search" class="form-control" placeholder="Search landlord..." value="{{ request('landlord_search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>

                <!-- Amount Range Filters -->
                <div class="col-md-3">
                    <label class="form-label">Min Amount</label>
                    <input type="number" name="amount_min" class="form-control" placeholder="Min amount" value="{{ request('amount_min') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Max Amount</label>
                    <input type="number" name="amount_max" class="form-control" placeholder="Max amount" value="{{ request('amount_max') }}">
                </div>

                <!-- Action Buttons -->
                <div class="col-md-6">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-funnel"></i> Apply Filters
                        </button>
                        <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </div>
            </form>

            <!-- Active Filters Badges -->
            @if(request()->anyFilled(['payment_method', 'status', 'month', 'tenant_search', 'apartment_search', 'landlord_search', 'date_from', 'date_to', 'amount_min', 'amount_max']))
            <div class="mt-3">
                <small class="text-muted">Active filters:</small>
                @foreach(request()->all() as $key => $value)
                    @if($value && in_array($key, ['payment_method', 'status', 'month', 'tenant_search', 'apartment_search', 'landlord_search', 'date_from', 'date_to', 'amount_min', 'amount_max']))
                    <span class="badge bg-info me-1">
                        {{ str_replace('_', ' ', $key) }}: {{ $value }}
                        <a href="{{ request()->fullUrlWithQuery([$key => null]) }}" class="text-white ms-1">×</a>
                    </span>
                    @endif
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('admin.payments.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Payment
        </a>
        <a href="{{ route('admin.sms.bulk-sms', ['month' => request('month', date('Y-m'))]) }}" 
        class="btn btn-warning">
            <i class="bi bi-chat-text"></i> Send SMS to Unpaid
        </a>
        
        <div class="btn-group">
            <a href="{{ route('admin.payments.index', ['status' => 'pending']) }}" class="btn btn-outline-warning">
                <i class="bi bi-clock"></i> Pending Payments
            </a>
            <a href="{{ route('admin.payments.index', ['payment_method' => 'pesapal']) }}" class="btn btn-outline-info">
                <i class="bi bi-credit-card"></i> Pesapal Payments
            </a>
            <a href="{{ route('admin.payments.index', ['status' => 'paid', 'month' => now()->format('Y-m')]) }}" class="btn btn-outline-success">
                <i class="bi bi-check-circle"></i> This Month Paid
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
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
                            <th>Actual Paid Date</th>
                            <th>Processed By</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                        <tr>
                            <td>{{ $payment->id }}</td>
                            <td>
                                <strong>{{ $payment->tenant->name }}</strong>
                                @if($payment->tenant->phone)
                                <br><small class="text-muted">{{ $payment->tenant->phone }}</small>
                                @endif
                            </td>
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
                                @elseif($payment->status === 'pending')
                                    <span class="badge bg-warning text-dark">{{ $payment->status_label }}</span>
                                @elseif($payment->status === 'failed')
                                    <span class="badge bg-danger">{{ $payment->status_label }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ $payment->status_label }}</span>
                                @endif
                            </td>
                            <td>
    @if($payment->actual_payment_date)
        {{ $payment->actual_payment_date->format('M j, Y') }}
        @if($payment->actual_payment_date->format('Y-m-d') != $payment->created_at->format('Y-m-d'))
            <br><small class="text-muted">(recorded: {{ $payment->created_at->format('M j, Y') }})</small>
        @endif
    @elseif($payment->paid_at)
        {{ $payment->paid_at->format('M j, Y') }}
    @else
        <span class="text-muted">-</span>
    @endif
</td>
                            <td>{{ $payment->processedBy?->name ?? 'System' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <!-- View Button -->
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-info" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    <!-- Edit Button -->
                                    <a href="{{ route('admin.payments.edit', $payment) }}" class="btn btn-warning" title="Edit Payment">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    
                                    <!-- Delete Button -->
                                    <form action="{{ route('admin.payments.destroy', $payment) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" title="Delete Payment"
                                                onclick="return confirm('Are you sure you want to delete this payment?\\n\\nTenant: {{ $payment->tenant->name }}\\nAmount: UGX {{ number_format($payment->amount) }}\\nMonth: {{ \Carbon\Carbon::parse($payment->month)->format('F Y') }}\\n\\nThis action cannot be undone!')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>

                                    <!-- Mark as Paid Button (only for non-paid payments) -->
                                    @if($payment->status !== 'paid')
                                    <form action="{{ route('admin.payments.mark-paid', $payment) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success" title="Mark as Paid" 
                                                onclick="return confirm('Mark this payment as paid?')">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                    @endif
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

@push('scripts')
<script>
$(document).ready(function() {
    // Add search enhancement for better user experience
    $('input[name="tenant_search"], input[name="apartment_search"], input[name="landlord_search"]').on('keyup', function(e) {
        if (e.key === 'Enter') {
            $(this).closest('form').submit();
        }
    });

    // Auto-submit form when month changes for quick filtering
    $('input[name="month"]').on('change', function() {
        if ($(this).val()) {
            $(this).closest('form').submit();
        }
    });

    // Confirmation for delete actions with more details
    $('form[action*="destroy"]').on('submit', function(e) {
        const form = $(this);
        const paymentRow = form.closest('tr');
        const tenantName = paymentRow.find('td:nth-child(2) strong').text();
        const amount = paymentRow.find('td:nth-child(6)').text();
        const month = paymentRow.find('td:nth-child(5)').text();
        
        if (!confirm(`Are you sure you want to delete this payment?\n\nTenant: ${tenantName}\nAmount: ${amount}\nMonth: ${month}\n\nThis action cannot be undone!`)) {
            e.preventDefault();
        }
    });
});
</script>
@endpush