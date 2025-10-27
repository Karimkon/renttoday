@extends('admin.layouts.app')

@section('title','Add Apartment')

@section('content')
<h3>Add Apartment</h3>
<a href="{{ route('admin.apartments.index') }}" class="btn btn-secondary mb-3">⬅ Back</a>

<form action="{{ route('admin.apartments.store') }}" method="POST">
    @csrf

    {{-- Add landlord and location fields --}}
<div class="mb-3">
    <label class="form-label">Landlord <span class="text-danger">*</span></label>
    <select name="landlord_id" class="form-select select2-landlord" required id="landlordSelect">
        <option value="">-- Search Landlord --</option>
        @foreach($landlords as $landlord)
            <option value="{{ $landlord->id }}" {{ old('landlord_id')==$landlord->id?'selected':'' }}
                    data-commission="{{ $landlord->commission_rate }}"
                    data-phone="{{ $landlord->phone }}"
                    data-email="{{ $landlord->email }}">
                {{ $landlord->name }} 
                @if($landlord->phone)
                    - {{ $landlord->phone }}
                @endif
                ({{ $landlord->commission_rate }}% commission)
            </option>
        @endforeach
    </select>
    <small class="text-muted">Type to search landlords by name or phone</small>
</div>

<div class="mb-3">
    <label class="form-label">Location <span class="text-danger">*</span></label>
    <input type="text" name="location" class="form-control" value="{{ old('location') }}" 
           placeholder="e.g., Mukono, Bweyogerere, etc." required>
</div>

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
    <select name="tenant_id" class="form-select select2-tenant">
        <option value="">-- Search Tenant --</option>
        @foreach($tenants as $tenant)
            <option value="{{ $tenant->id }}" {{ old('tenant_id')==$tenant->id?'selected':'' }}>
                {{ $tenant->name }} 
                @if($tenant->phone)
                    - {{ $tenant->phone }}
                @endif
            </option>
        @endforeach
    </select>
    <small class="text-muted">Type to search tenants</small>
</div>
    <button type="submit" class="btn btn-success">Save Apartment</button>
</form>

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 for landlord search
    $('.select2-landlord').select2({
        theme: 'bootstrap-5',
        placeholder: 'Search by landlord name or phone...',
        allowClear: true,
        width: '100%'
    });

    // Auto-show landlord details when selected
    $('#landlordSelect').on('change', function() {
        const selected = this.options[this.selectedIndex];
        const landlordDetails = $('#landlordDetails');
        
        if (this.value) {
            // Show and fill landlord details
            landlordDetails.show();
            $('#landlordPhone').text(selected.getAttribute('data-phone') || 'Not provided');
            $('#landlordEmail').text(selected.getAttribute('data-email') || 'Not provided');
            $('#landlordCommission').text(selected.getAttribute('data-commission') || '0');
        } else {
            // Hide details if no landlord selected
            landlordDetails.hide();
        }
    });

    // Trigger change on page load if there's a selected value
    $('#landlordSelect').trigger('change');

    // Initialize Select2 for tenant search
$('.select2-tenant').select2({
    theme: 'bootstrap-5',
    placeholder: 'Search by tenant name or phone...',
    allowClear: true,
    width: '100%'
});

});
</script>
@endpush
@endsection
