@extends('admin.layouts.app')
@section('title','Edit Payment')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-pencil-square me-2"></i> Edit Payment</h3>
        <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Payments
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.payments.update', $payment) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tenant *</label>
                            <select name="tenant_id" class="form-select" required>
                                <option value="">-- Select Tenant --</option>
                                @foreach($tenants as $t)
                                <option value="{{ $t->id }}" {{ old('tenant_id',$payment->tenant_id)==$t->id?'selected':'' }}>
                                    {{ $t->name }} @if($t->apartment) (Apt: {{ $t->apartment->number }}) @endif
                                </option>
                                @endforeach
                            </select>
                            @error('tenant_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Month *</label>
                            <input type="month" name="month" class="form-control" 
                                   value="{{ old('month', \Carbon\Carbon::parse($payment->month)->format('Y-m')) }}" required>
                            @error('month')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Amount (UGX) *</label>
                            <input type="number" name="amount" class="form-control" 
                                   value="{{ old('amount',$payment->amount) }}" required min="1">
                            @error('amount')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Payment Method *</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cash" {{ old('payment_method',$payment->payment_method)=='cash'?'selected':'' }}>Cash</option>
                                <option value="pesapal" {{ old('payment_method',$payment->payment_method)=='pesapal'?'selected':'' }}>Pesapal</option>
                                <option value="bank_transfer" {{ old('payment_method',$payment->payment_method)=='bank_transfer'?'selected':'' }}>Bank Transfer</option>
                                <option value="mobile_money" {{ old('payment_method',$payment->payment_method)=='mobile_money'?'selected':'' }}>Mobile Money</option>
                            </select>
                            @error('payment_method')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" {{ old('status',$payment->status)=='pending'?'selected':'' }}>Pending</option>
                                <option value="paid" {{ old('status',$payment->status)=='paid'?'selected':'' }}>Paid</option>
                                <option value="failed" {{ old('status',$payment->status)=='failed'?'selected':'' }}>Failed</option>
                                <option value="refunded" {{ old('status',$payment->status)=='refunded'?'selected':'' }}>Refunded</option>
                            </select>
                            @error('status')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Reference Number</label>
                            <input type="text" name="reference_number" class="form-control" 
                                   value="{{ old('reference_number',$payment->reference_number) }}" 
                                   placeholder="Optional reference number">
                            @error('reference_number')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    
                </div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label fw-semibold">Actual Payment Date</label>
            <input type="date" name="actual_payment_date" class="form-control" 
                   value="{{ old('actual_payment_date', $payment->actual_payment_date ? $payment->actual_payment_date->format('Y-m-d') : '') }}">
            <small class="text-muted">The date when tenant actually made the payment</small>
        </div>
    </div>
</div>

              {{-- In resources/views/admin/payments/edit.blade.php --}}
<!-- Add these fields for advance payments -->
<div class="row mb-3">
    <div class="col-md-6">
        <div class="form-check">
            <input type="checkbox" name="is_advance_payment" value="1" 
                   class="form-check-input" id="isAdvancePayment"
                   {{ $payment->is_advance_payment ? 'checked' : '' }}>
            <label class="form-check-label" for="isAdvancePayment">
                <strong>This is an advance payment</strong>
            </label>
        </div>
    </div>
</div>

<!-- Original Amount (for advance payments) -->
<div class="row mb-3" id="originalAmountField" style="display: {{ $payment->is_advance_payment ? 'block' : 'none' }};">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Original Amount Paid</label>
        <input type="number" name="original_amount" class="form-control" 
               value="{{ $payment->original_amount_display ?? $payment->original_amount ?? $payment->amount }}">
        <small class="text-muted">The full amount the tenant actually paid (e.g., 650,000 for 2 months + partial)</small>
    </div>
</div>

<!-- Allocated Months (for advance payments) -->
<div class="row mb-3" id="allocatedMonthsField" style="display: {{ $payment->is_advance_payment ? 'block' : 'none' }};">
    <div class="col-md-12">
        <label class="form-label fw-semibold">Months Covered by this Advance</label>
        @if($payment->allocated_months)
            <div class="alert alert-info">
                <strong>Currently allocated to:</strong><br>
                @foreach($payment->allocated_months as $month)
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}<br>
                @endforeach
            </div>
        @endif
        <small class="text-muted">System will automatically allocate based on rent amount</small>
    </div>
</div>

@push('scripts')
<script>
// Toggle advance payment fields
document.getElementById('isAdvancePayment').addEventListener('change', function() {
    const originalAmountField = document.getElementById('originalAmountField');
    const allocatedMonthsField = document.getElementById('allocatedMonthsField');
    
    if (this.checked) {
        originalAmountField.style.display = 'block';
        allocatedMonthsField.style.display = 'block';
    } else {
        originalAmountField.style.display = 'none';
        allocatedMonthsField.style.display = 'none';
    }
});
</script>
@endpush