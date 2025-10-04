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
