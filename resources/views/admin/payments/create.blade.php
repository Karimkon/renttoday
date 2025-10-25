{{-- resources/views/admin/payments/create.blade.php --}}
@extends('admin.layouts.app')
@section('title','Add Payment')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-cash-coin me-2"></i> Add Payment</h3>
        <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Payments
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.payments.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tenant <span class="text-danger">*</span></label>
                            <select name="tenant_id" class="form-select" required>
                                <option value="">-- Select Tenant --</option>
                                @foreach($tenants as $t)
                                <option value="{{ $t->id }}" {{ old('tenant_id')==$t->id?'selected':'' }}>
                                    {{ $t->name }} 
                                    @if($t->apartment)
                                        (Apt: {{ $t->apartment->number }} - UGX {{ number_format($t->apartment->rent) }}/month)
                                        @if($t->apartment->landlord)
                                            - Landlord: {{ $t->apartment->landlord->name }}
                                        @endif
                                    @endif
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required id="paymentMethod">
                                <option value="">-- Select Method --</option>
                                <option value="cash" {{ old('payment_method')=='cash'?'selected':'' }}>Cash</option>
                                <option value="pesapal" {{ old('payment_method')=='pesapal'?'selected':'' }}>Pesapal Online</option>
                                <option value="bank_transfer" {{ old('payment_method')=='bank_transfer'?'selected':'' }}>Bank Transfer</option>
                                <option value="mobile_money" {{ old('payment_method')=='mobile_money'?'selected':'' }}>Mobile Money</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Month <span class="text-danger">*</span></label>
                            <input type="month" name="month" class="form-control" value="{{ old('month', date('Y-m')) }}" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Amount (UGX) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" value="{{ old('amount') }}" required min="1">
                        </div>
                    </div>
                </div>

                <!-- Reference Number (shown for non-cash methods) -->
                <div class="row" id="referenceField" style="display: none;">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reference Number</label>
                            <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}" 
                                   placeholder="Transaction ID, MOMO number, etc.">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="includes_gym" value="1" class="form-check-input" {{ old('includes_gym')?'checked':'' }}>
                                <label class="form-check-label">Includes Gym Access</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Any additional notes...">{{ old('notes') }}</textarea>
                </div>

                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle"></i> Payment Instructions:</h6>
                    <ul class="mb-0">
                        <li><strong>Cash:</strong> Payment will be marked as paid immediately</li>
                        <li><strong>Pesapal:</strong> Tenant will receive payment link to complete transaction</li>
                        <li><strong>Bank Transfer/Mobile Money:</strong> Mark as paid after confirming receipt</li>
                    </ul>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle me-1"></i> Process Payment
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('paymentMethod').addEventListener('change', function() {
    const referenceField = document.getElementById('referenceField');
    if (this.value !== 'cash' && this.value !== 'pesapal') {
        referenceField.style.display = 'block';
    } else {
        referenceField.style.display = 'none';
    }
});

// Trigger change on page load
document.getElementById('paymentMethod').dispatchEvent(new Event('change'));
</script>
@endsection