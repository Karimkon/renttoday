{{-- resources/views/admin/landlords/show.blade.php --}}
@extends('admin.layouts.app')

@section('title', $landlord->name . ' - Landlord Details')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0"><i class="bi bi-person-badge me-2"></i> {{ $landlord->name }}</h3>
            <p class="text-muted mb-0">Landlord Details & Monthly Report</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.landlords.report.pdf', ['landlord' => $landlord->id, 'month' => $selectedMonth]) }}" 
               class="btn btn-primary">
                <i class="bi bi-download"></i> Export PDF
            </a>
            <a href="{{ route('admin.landlords.monthly-report', ['landlord' => $landlord->id, 'month' => $selectedMonth]) }}"  
            class="btn btn-info">
                <i class="bi bi-file-text"></i> View Detailed Report
            </a>
            <a href="{{ route('admin.landlords.edit', $landlord) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('admin.landlords.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Monthly Report Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Select Month</label>
                    <input type="month" name="month" class="form-control" value="{{ $selectedMonth }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group mt-2">
                        <a href="?month={{ \Carbon\Carbon::parse($selectedMonth)->subMonth()->format('Y-m') }}" 
                           class="btn btn-outline-secondary">← Previous Month</a>
                        <a href="?month={{ now()->format('Y-m') }}" 
                           class="btn btn-outline-primary">Current Month</a>
                        <a href="?month={{ \Carbon\Carbon::parse($selectedMonth)->addMonth()->format('Y-m') }}" 
                           class="btn btn-outline-secondary">Next Month →</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Landlord Information -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Landlord Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td width="30%"><strong>Name:</strong></td>
                            <td>{{ $landlord->name }}</td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td>{{ $landlord->email }}</td>
                        </tr>
                        <tr>
                            <td><strong>Phone:</strong></td>
                            <td>{{ $landlord->phone ?? 'Not provided' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Commission Rate:</strong></td>
                            <td><span class="badge bg-info">{{ $landlord->commission_rate }}%</span></td>
                        </tr>
                        <tr>
                            <td><strong>Total Apartments:</strong></td>
                            <td><span class="badge bg-primary">{{ $landlord->apartments->count() }}</span></td>
                        </tr>
                    </table>
                    @if($landlord->address)
                    <div class="mt-3">
                        <strong>Address:</strong>
                        <p class="mb-0 text-muted">{{ $landlord->address }}</p>
                    </div>
                    @endif
                    @if($landlord->notes)
                    <div class="mt-3">
                        <strong>Notes:</strong>
                        <p class="mb-0 text-muted">{{ $landlord->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Monthly Summary -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Monthly Summary - {{ \Carbon\Carbon::parse($selectedMonth)->format('F Y') }}</h5>
                </div>
                <div class="card-body">
                    @php
                        $startDate = \Carbon\Carbon::parse($selectedMonth)->startOfMonth()->format('Y-m-d');
                        $endDate = \Carbon\Carbon::parse($selectedMonth)->endOfMonth()->format('Y-m-d');
                        $totalRent = $landlord->totalRentCollected($startDate, $endDate);
                        $totalCommission = $landlord->calculateCommission($startDate, $endDate);
                        $amountDue = $landlord->amountDueToLandlord($startDate, $endDate);
                    @endphp
                    <div class="text-center">
                        <h3 class="text-success">UGX {{ number_format($totalRent) }}</h3>
                        <p class="text-muted">Total Rent Collected</p>
                        
                        <div class="row mt-4">
                            <div class="col-6">
                                <h5 class="text-danger">UGX {{ number_format($totalCommission) }}</h5>
                                <small class="text-muted">Commission ({{ $landlord->commission_rate }}%)</small>
                            </div>
                            <div class="col-6">
                                <h5 class="text-primary">UGX {{ number_format($amountDue) }}</h5>
                                <small class="text-muted">Amount Due to Landlord</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Apartments List -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-building"></i> Managed Apartments</h5>
        </div>
        <div class="card-body">
            @if($landlord->apartments->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Apartment No</th>
                            <th>Location</th>
                            <th>Rooms</th>
                            <th>Rent</th>
                            <th>Current Tenant</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($landlord->apartments as $apartment)
                        <tr>
                            <td><strong>{{ $apartment->number }}</strong></td>
                            <td>{{ $apartment->location }}</td>
                            <td>{{ $apartment->rooms }}</td>
                            <td>UGX {{ number_format($apartment->rent) }}</td>
                            <td>
                                @if($apartment->tenant)
                                    {{ $apartment->tenant->name }}
                                @else
                                    <span class="badge bg-secondary">Vacant</span>
                                @endif
                            </td>
                            <td>
                                @if($apartment->tenant)
                                    <span class="badge bg-success">Occupied</span>
                                @else
                                    <span class="badge bg-warning">Empty</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.apartments.edit', $apartment) }}" 
                                   class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center text-muted py-4">
                <i class="bi bi-building-x fs-1"></i>
                <p class="mt-2">No apartments assigned to this landlord</p>
                <a href="{{ route('admin.apartments.create') }}" class="btn btn-primary btn-sm">
                    Add Apartment
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection