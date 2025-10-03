@extends('admin.layouts.app')
@section('title','Edit Payment')

@section('content')
<h3 class="mb-4">Edit Payment</h3>
<a href="{{ route('admin.payments.index') }}" class="btn btn-secondary mb-3">⬅ Back</a>

<form action="{{ route('admin.payments.update', $payment) }}" method="POST" class="card p-4 shadow-sm">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label">Tenant</label>
        <select name="tenant_id" class="form-select" required>
            <option value="">-- Select Tenant --</option>
            @foreach($tenants as $t)
            <option value="{{ $t->id }}" {{ old('tenant_id',$payment->tenant_id)==$t->id?'selected':'' }}>
                {{ $t->name }} @if($t->apartment) (Apt: {{ $t->apartment->number }}) @endif
            </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Month</label>
        <input type="month" name="month" class="form-control" value="{{ old('month', \Carbon\Carbon::parse($payment->month)->format('Y-m')) }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Amount (UGX)</label>
        <input type="number" name="amount" class="form-control" value="{{ old('amount',$payment->amount) }}" required>
    </div>

    <div class="mb-3 form-check">
        <input type="checkbox" name="includes_gym" value="1" class="form-check-input" {{ old('includes_gym',$payment->includes_gym)?'checked':'' }}>
        <label class="form-check-label">Includes Gym</label>
    </div>

    <button type="submit" class="btn btn-success">💾 Update Payment</button>
</form>
@endsection
