@extends('admin.layouts.app')

@section('title','Edit Apartment')

@section('content')
<h3>Edit Apartment</h3>
<a href="{{ route('admin.apartments.index') }}" class="btn btn-secondary mb-3">⬅ Back</a>

<form action="{{ route('admin.apartments.update',$apartment) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label">Number</label>
        <input type="text" name="number" class="form-control" value="{{ old('number',$apartment->number) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Rooms</label>
        <input type="number" name="rooms" class="form-control" value="{{ old('rooms',$apartment->rooms) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Rent</label>
        <input type="number" name="rent" class="form-control" value="{{ old('rent',$apartment->rent) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Tenant (optional)</label>
        <select name="tenant_id" class="form-select">
            <option value="">-- Unassigned --</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" {{ (old('tenant_id',$apartment->tenant_id)==$tenant->id)?'selected':'' }}>{{ $tenant->name }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-success">Update Apartment</button>
</form>
@endsection
