@extends('admin.layouts.app')
@section('title','Tenants')

@section('content')
<h3 class="mb-4">👥 Tenants Overview</h3>

{{-- Summary Cards --}}
@php
    $totalTenants = $tenants->count();
    $withApartment = $tenants->whereNotNull('apartment')->count();
    $withoutApartment = $tenants->whereNull('apartment')->count();
    $totalCredit = $tenants->sum('credit_balance');
@endphp

<div class="row mb-4 g-3">
    <div class="col-md-3">
        <div class="card text-white bg-primary shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6>Total Tenants</h6>
                    <h4>{{ $totalTenants }}</h4>
                </div>
                <i class="bi bi-people fs-2"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6>With Apartment</h6>
                    <h4>{{ $withApartment }}</h4>
                </div>
                <i class="bi bi-house-door fs-2"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-secondary shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6>No Apartment</h6>
                    <h4>{{ $withoutApartment }}</h4>
                </div>
                <i class="bi bi-x-circle fs-2"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6>Total Credit</h6>
                    <h4>UGX {{ number_format($totalCredit) }}</h4>
                </div>
                <i class="bi bi-cash fs-2"></i>
            </div>
        </div>
    </div>
</div>

<a href="{{ route('admin.tenants.create') }}" class="btn btn-primary mb-3">
    <i class="bi bi-plus-circle"></i> Add Tenant
</a>

@if(session('success'))
<div class="alert alert-success shadow-sm">{{ session('success') }}</div>
@endif

{{-- Search Form --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('admin.tenants.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Search Tenants</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by name, email, phone..." 
                           value="{{ request('search') }}">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Apartment Status</label>
                <select name="apartment_status" class="form-select">
                    <option value="">All Status</option>
                    <option value="with_apartment" {{ request('apartment_status') == 'with_apartment' ? 'selected' : '' }}>
                        With Apartment
                    </option>
                    <option value="without_apartment" {{ request('apartment_status') == 'without_apartment' ? 'selected' : '' }}>
                        Without Apartment
                    </option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Sort By</label>
                <select name="sort" class="form-select">
                    <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name A-Z</option>
                    <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Name Z-A</option>
                    <option value="credit_high" {{ request('sort') == 'credit_high' ? 'selected' : '' }}>Credit (High to Low)</option>
                    <option value="credit_low" {{ request('sort') == 'credit_low' ? 'selected' : '' }}>Credit (Low to High)</option>
                    <option value="recent" {{ request('sort') == 'recent' ? 'selected' : '' }}>Most Recent</option>
                </select>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.tenants.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Search Results Info --}}
@if(request()->has('search') || request()->has('apartment_status') || request()->has('sort'))
<div class="alert alert-info mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-info-circle me-2"></i>
            Showing filtered results 
            @if(request('search'))
                for "<strong>{{ request('search') }}</strong>"
            @endif
            @if(request('apartment_status'))
                - Status: <strong>{{ ucfirst(str_replace('_', ' ', request('apartment_status'))) }}</strong>
            @endif
            ({{ $tenants->count() }} found)
        </div>
        <a href="{{ route('admin.tenants.index') }}" class="btn btn-sm btn-outline-info">
            Show All Tenants
        </a>
    </div>
</div>
@endif

<div class="table-responsive shadow-sm">
    <table class="table table-bordered table-hover align-middle table-striped">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Apartment</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tenants as $tenant)
            <tr>
                <td>{{ $tenant->id }}</td>
                <td>
                    {{ $tenant->name }}
                    @if($tenant->credit_balance > 0)
                        <span class="badge bg-success d-block mt-1">💰 UGX {{ number_format($tenant->credit_balance) }}</span>
                    @endif
                </td>
                <td>{{ $tenant->email }}</td>
                <td>{{ $tenant->phone ?? '-' }}</td>
                <td>
                    @if($tenant->apartment)
                        <span class="badge bg-info text-dark">Apt #{{ $tenant->apartment->number }}</span>
                        <small class="text-muted d-block">Rent: UGX {{ number_format($tenant->apartment->rent) }}</small>
                    @else
                        <span class="badge bg-secondary">— Not Assigned —</span>
                    @endif
                </td>
                <td>
                    @if($tenant->apartment)
                        <span class="badge bg-success">Has Apartment</span>
                    @else
                        <span class="badge bg-secondary">No Apartment</span>
                    @endif
                </td>
                <td>
                    @if($tenant->apartment)
                        <a href="{{ route('admin.apartments.edit', $tenant->apartment) }}" class="btn btn-sm btn-info mb-1">
                            🏠 View Apartment
                        </a>
                    @endif
                    <a href="{{ route('admin.tenants.edit', $tenant) }}" class="btn btn-sm btn-warning mb-1">
                        ✏️ Edit
                    </a>
                    <form action="{{ route('admin.tenants.destroy', $tenant) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger mb-1">
                            🗑 Delete
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
