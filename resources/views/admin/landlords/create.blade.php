{{-- resources/views/admin/landlords/create.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Add Landlord')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-person-plus me-2"></i> Add Landlord</h3>
        <a href="{{ route('admin.landlords.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Landlords
        </a>
    </div>

    <!-- Validation Errors -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.landlords.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" 
                                   value="{{ old('name') }}" required placeholder="Enter landlord's full name">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" 
                                   value="{{ old('email') }}" required placeholder="landlord@example.com">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" 
                                   value="{{ old('phone') }}" placeholder="0700123456">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Commission Rate (%) <span class="text-danger">*</span></label>
                            <input type="number" name="commission_rate" class="form-control" 
                                   value="{{ old('commission_rate', 10) }}" min="0" max="100" step="0.1" required>
                            <small class="text-muted">Percentage of rent collected as commission</small>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea name="address" class="form-control" rows="3" 
                              placeholder="Enter landlord's address...">{{ old('address') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" 
                              placeholder="Any additional notes...">{{ old('notes') }}</textarea>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle me-1"></i> Save Landlord
                    </button>
                    <a href="{{ route('admin.landlords.index') }}" class="btn btn-secondary btn-lg">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection