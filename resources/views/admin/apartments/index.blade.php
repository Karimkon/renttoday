@extends('admin.layouts.app')

@section('title','Apartments Dashboard')

@section('content')
<h3 class="mb-4">🏢 Apartments Dashboard</h3>

{{-- Summary Cards --}}
<div class="row mb-4 g-3">
    @php
        $statusCounts = ['paid'=>0,'partial'=>0,'unpaid'=>0,'empty'=>0];
        foreach($apartments as $apt){
            $statusCounts[$apt->status] = ($statusCounts[$apt->status] ?? 0) + 1;
        }
    @endphp

    <div class="col-md-3">
        <div class="card text-white bg-success shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">Paid</h5>
                    <p class="card-text fs-4">{{ $statusCounts['paid'] }}</p>
                </div>
                <i class="bi bi-check-circle fs-2"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-dark bg-warning shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">Partial</h5>
                    <p class="card-text fs-4">{{ $statusCounts['partial'] }}</p>
                </div>
                <i class="bi bi-hourglass-split fs-2"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-white bg-danger shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">Unpaid</h5>
                    <p class="card-text fs-4">{{ $statusCounts['unpaid'] }}</p>
                </div>
                <i class="bi bi-x-circle fs-2"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-white bg-secondary shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">Empty</h5>
                    <p class="card-text fs-4">{{ $statusCounts['empty'] }}</p>
                </div>
                <i class="bi bi-dash-circle fs-2"></i>
            </div>
        </div>
    </div>
</div>

{{-- Filter Form --}}
{{-- Advanced Filter Form --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-funnel"></i> Advanced Filters</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.apartments.index') }}" method="GET" class="row g-3 align-items-end">
            {{-- Basic Filters --}}
            <div class="col-md-3">
                <label class="form-label fw-semibold">Month</label>
                <input type="month" name="month" value="{{ $month }}" class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Landlord</label>
                <select name="landlord_id" class="form-select select2-landlord">
                    <option value="">All Landlords</option>
                    @foreach($landlords as $landlord)
                        <option value="{{ $landlord->id }}" {{ ($landlordFilter==$landlord->id)?'selected':'' }}>
                            {{ $landlord->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Location</label>
                <select name="location" class="form-select select2-location">
                    <option value="">All Locations</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc }}" {{ ($locationFilter==$loc)?'selected':'' }}>
                            {{ $loc }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    @foreach(['paid','partial','unpaid','empty'] as $status)
                        <option value="{{ $status }}" {{ ($statusFilter==$status)?'selected':'' }}>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Advanced Filters --}}
            <div class="col-md-3">
                <label class="form-label fw-semibold">Rent Range</label>
                <div class="input-group">
                    <input type="number" name="rent_min" class="form-control" placeholder="Min" 
                           value="{{ request('rent_min') }}">
                    <input type="number" name="rent_max" class="form-control" placeholder="Max" 
                           value="{{ request('rent_max') }}">
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Rooms</label>
                <select name="rooms" class="form-select">
                    <option value="">Any</option>
                    <option value="1" {{ request('rooms') == '1' ? 'selected' : '' }}>1 Room</option>
                    <option value="2" {{ request('rooms') == '2' ? 'selected' : '' }}>2 Rooms</option>
                    <option value="3" {{ request('rooms') == '3' ? 'selected' : '' }}>3+ Rooms</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Payment Progress</label>
                <select name="progress" class="form-select">
                    <option value="">Any</option>
                    <option value="full" {{ request('progress') == 'full' ? 'selected' : '' }}>Fully Paid (100%)</option>
                    <option value="partial" {{ request('progress') == 'partial' ? 'selected' : '' }}>Partially Paid (1-99%)</option>
                    <option value="none" {{ request('progress') == 'none' ? 'selected' : '' }}>Not Paid (0%)</option>
                    <option value="overdue" {{ request('progress') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Tenant Status</label>
                <select name="tenant_status" class="form-select">
                    <option value="">Any</option>
                    <option value="with_tenant" {{ request('tenant_status') == 'with_tenant' ? 'selected' : '' }}>With Tenant</option>
                    <option value="without_tenant" {{ request('tenant_status') == 'without_tenant' ? 'selected' : '' }}>Without Tenant</option>
                    <option value="with_credit" {{ request('tenant_status') == 'with_credit' ? 'selected' : '' }}>Tenant with Credit</option>
                </select>
            </div>

            {{-- Search --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Search</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by apartment number, tenant name..." 
                       value="{{ request('search') }}">
            </div>

            {{-- Actions --}}
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-funnel"></i> Apply Filters
                    </button>
                    <a href="{{ route('admin.apartments.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </div>
        </form>

        {{-- Active Filters Badges --}}
        @if(request()->anyFilled(['search', 'landlord_id', 'location', 'status', 'rent_min', 'rent_max', 'rooms', 'progress', 'tenant_status']))
        <div class="mt-3 pt-3 border-top">
            <small class="text-muted me-2">Active filters:</small>
            @if(request('search'))
                <span class="badge bg-primary">Search: "{{ request('search') }}"</span>
            @endif
            @if(request('landlord_id'))
                <span class="badge bg-info">Landlord: {{ $landlords->where('id', request('landlord_id'))->first()->name ?? 'N/A' }}</span>
            @endif
            @if(request('location'))
                <span class="badge bg-info">Location: {{ request('location') }}</span>
            @endif
            @if(request('status'))
                <span class="badge bg-warning text-dark">Status: {{ ucfirst(request('status')) }}</span>
            @endif
            @if(request('rent_min') || request('rent_max'))
                <span class="badge bg-success">Rent: {{ request('rent_min') ? 'UGX '.number_format(request('rent_min')) : 'Min' }} - {{ request('rent_max') ? 'UGX '.number_format(request('rent_max')) : 'Max' }}</span>
            @endif
            @if(request('rooms'))
                <span class="badge bg-secondary">Rooms: {{ request('rooms') }}+</span>
            @endif
            @if(request('progress'))
                <span class="badge bg-dark">Progress: {{ ucfirst(request('progress')) }}</span>
            @endif
            @if(request('tenant_status'))
                <span class="badge bg-primary">Tenant: {{ str_replace('_', ' ', ucfirst(request('tenant_status'))) }}</span>
            @endif
        </div>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
@endif

{{-- Apartments Table --}}
<div class="table-responsive shadow-sm">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Number</th>
                <th>Land Lord</th>
                <th>Rooms</th>
                <th>Rent</th>
                <th>Tenant</th>
                <th>Status</th>
                <th>Due Amount</th>
                <th>Total Paid</th>
                <th>Progress</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($apartments as $apartment)
            <tr>
                <td>{{ $apartment->id }}</td>
                <td>{{ $apartment->number }}</td>
                <td>
            @if($apartment->landlord)
                <span class="badge bg-primary">{{ $apartment->landlord->name }}</span>
                <small class="text-muted d-block">{{ $apartment->landlord->commission_rate }}% commission</small>
            @else
                <span class="badge bg-secondary">No Landlord</span>
            @endif
        </td>
                <td>{{ $apartment->rooms }}</td>
                <td>{{ number_format($apartment->rent) }}</td>
                <td>{{ $apartment->tenant?->name ?? 'Unassigned' }}</td>
                <td>
                    @php
                        $statusColors = ['paid'=>'success','partial'=>'warning','unpaid'=>'danger','empty'=>'secondary'];
                    @endphp
                    <span class="badge bg-{{ $statusColors[$apartment->status] ?? 'secondary' }}">
                        {{ $apartment->status=='empty'?'Empty':ucfirst($apartment->status) }}
                    </span>
                </td>
                <td>{{ $apartment->status!='empty' ? 'UGX '.number_format($apartment->dueAmount) : '-' }}</td>
                <td>
                    <span class="badge bg-info text-dark">
                        {{ number_format($apartment->totalPaid) }}/{{ number_format($apartment->rent) }}
                    </span>
                </td>
                <td>
                    @if($apartment->tenant && $apartment->tenant->credit_balance > 0)
                        <span class="badge bg-success mb-1 d-block">
                            + UGX {{ number_format($apartment->tenant->credit_balance) }} credit
                        </span>
                    @endif

                    @if($apartment->status!='empty')
                        <div class="progress">
                            <div class="progress-bar bg-{{ $statusColors[$apartment->status] ?? 'secondary' }}" 
                                 role="progressbar" 
                                 style="width: {{ min(100, ($apartment->totalPaid / max(1,$apartment->rent)) * 100) }}%">
                            </div>
                        </div>
                    @else
                        <span class="text-muted">No tenant</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('admin.apartments.edit', $apartment) }}" class="btn btn-sm btn-warning mb-1">✏️ Edit</a>
                    <a href="{{ route('admin.payments.create') }}?tenant={{ $apartment->tenant?->id }}" class="btn btn-sm btn-success mb-1">➕ Payment</a>
                    <button type="button" class="btn btn-sm btn-info mb-1" data-bs-toggle="modal" data-bs-target="#historyModal{{ $apartment->id }}">📄 History</button>
                    <form action="{{ route('admin.apartments.destroy', $apartment) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">🗑 Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Payment History Modals --}}
@foreach($apartments as $apartment)
<div class="modal fade" id="historyModal{{ $apartment->id }}" tabindex="-1" aria-labelledby="modalLabel{{ $apartment->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel{{ $apartment->id }}">Payment History: {{ $apartment->number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if($apartment->payments->isEmpty())
                    <p class="text-muted">No payments recorded.</p>
                @else
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Amount</th>
                            <th>Gym Included</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($apartment->payments->sortByDesc('month') as $payment)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($payment->month)->format('M Y') }}</td>
                            <td>{{ number_format($payment->amount) }}</td>
                            <td>{{ $payment->includes_gym ? 'Yes' : 'No' }}</td>
                            <td>{{ $payment->created_at->format('d M, Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endforeach

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 for filters
    $('.select2-landlord').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select landlord...',
        allowClear: true,
        width: '100%'
    });

    $('.select2-location').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select location...',
        allowClear: true,
        width: '100%'
    });
});
</script>
@endpush
@endsection
