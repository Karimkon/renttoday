@extends('secretary.layouts.app')

@section('title', 'Add Tenant')

@section('content')
<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i> Add Tenant</h3>
        <a href="{{ route('secretary.tenants.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Tenants
        </a>
    </div>

    <!-- Validation Errors -->
    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Card -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="{{ route('secretary.tenants.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control form-control-lg" value="{{ old('name') }}" placeholder="John Doe" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="email" class="form-control form-control-lg" value="{{ old('email') }}" placeholder="johndoe@example.com" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label fw-semibold">Phone Number</label>
                    <input type="text" name="phone" id="phone" class="form-control form-control-lg" value="{{ old('phone') }}" placeholder="0700123456">
                </div>

                <p class="text-muted small mb-3">
                    A login account will be created automatically for this tenant, and credentials will be sent via email.
                </p>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle me-1"></i> Save Tenant
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
