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

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" 
                              placeholder="Any additional notes about this payment">{{ old('notes',$payment->notes) }}</textarea>
                    @error('notes')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Update Payment
                        </button>
                        <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </div>
                    
                    <div class="text-muted small">
                        Created: {{ $payment->created_at->format('M j, Y g:i A') }}
                        @if($payment->updated_at != $payment->created_at)
                        <br>Last Updated: {{ $payment->updated_at->format('M j, Y g:i A') }}
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection