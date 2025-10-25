@extends('admin.layouts.app')

@section('title','Edit Apartment')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-building-gear me-2"></i> Edit Apartment</h3>
        <a href="{{ route('admin.apartments.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Apartments
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.apartments.update', $apartment) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Apartment Number <span class="text-danger">*</span></label>
                            <input type="text" name="number" class="form-control" 
                                   value="{{ old('number', $apartment->number) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Number of Rooms <span class="text-danger">*</span></label>
                            <input type="number" name="rooms" class="form-control" 
                                   value="{{ old('rooms', $apartment->rooms) }}" min="1" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Monthly Rent (UGX) <span class="text-danger">*</span></label>
                            <input type="number" name="rent" class="form-control" 
                                   value="{{ old('rent', $apartment->rent) }}" min="1" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
                            <input type="text" name="location" class="form-control" 
                                   value="{{ old('location', $apartment->location) }}" 
                                   placeholder="e.g., Mukono, Bweyogerere" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Landlord <span class="text-danger">*</span></label>
                            <select name="landlord_id" class="form-select" required>
                                <option value="">-- Select Landlord --</option>
                                @foreach($landlords as $landlord)
                                    <option value="{{ $landlord->id }}" 
                                        {{ (old('landlord_id', $apartment->landlord_id) == $landlord->id) ? 'selected' : '' }}>
                                        {{ $landlord->name }} ({{ $landlord->commission_rate }}% commission)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tenant (Optional)</label>
                            <select name="tenant_id" class="form-select">
                                <option value="">-- Unassigned --</option>
                                @foreach($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" 
                                        {{ (old('tenant_id', $apartment->tenant_id) == $tenant->id) ? 'selected' : '' }}>
                                        {{ $tenant->name }}
                                        @if($tenant->apartment)
                                            (Currently in Apt: {{ $tenant->apartment->number }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Leave unassigned to mark as vacant</small>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle me-1"></i> Update Apartment
                    </button>
                    <a href="{{ route('admin.apartments.index') }}" class="btn btn-secondary btn-lg">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Current Apartment Info -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i> Current Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>Current Landlord:</strong>
                    <p>{{ $apartment->landlord->name ?? 'Not assigned' }}</p>
                </div>
                <div class="col-md-4">
                    <strong>Current Location:</strong>
                    <p>{{ $apartment->location ?? 'Not set' }}</p>
                </div>
                <div class="col-md-4">
                    <strong>Current Tenant:</strong>
                    <p>{{ $apartment->tenant->name ?? 'Vacant' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection