@extends('secretary.layouts.app')
@section('title','Create Invoice')

@section('content')
<h3 class="mb-4">➕ Create Invoice</h3>

<form action="{{ route('secretary.invoices.store') }}" method="POST">
    @csrf

    <div class="mb-3">
        <label for="tenant_id" class="form-label">Tenant</label>
        <select name="tenant_id" id="tenant_id" class="form-select" required>
            <option value="">Select Tenant</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}">{{ $tenant->name }} - {{ $tenant->email }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="apartment_id" class="form-label">Apartment (optional)</label>
        <select name="apartment_id" id="apartment_id" class="form-select">
            <option value="">Unassigned</option>
            @foreach(App\Models\Apartment::all() as $apartment)
                <option value="{{ $apartment->id }}">#{{ $apartment->number }} - UGX {{ number_format($apartment->rent) }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="amount" class="form-label">Amount (UGX)</label>
        <input type="number" name="amount" id="amount" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="month" class="form-label">Month</label>
        <input type="month" name="month" id="month" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary">Generate Invoice PDF</button>
</form>
@endsection
