@extends('secretary.layouts.app')

@section('content')
<h3>Add Apartment</h3>
<a href="{{ route('secretary.apartments.index') }}" class="btn btn-secondary mb-3">⬅ Back</a>

<form action="{{ route('secretary.apartments.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label class="form-label">Number</label>
        <input type="text" name="number" class="form-control" value="{{ old('number') }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Rooms</label>
        <input type="number" name="rooms" class="form-control" value="{{ old('rooms') }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Rent</label>
        <input type="number" name="rent" class="form-control" value="{{ old('rent') }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Tenant (optional)</label>
        <select name="tenant_id" class="form-select">
            <option value="">-- Unassigned --</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" {{ old('tenant_id')==$tenant->id ? 'selected':'' }}>
                    {{ $tenant->name }}
                </option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="btn btn-success">Save Apartment</button>
</form>
@endsection
