@extends('secretary.layouts.app')

@section('title','Apartments Dashboard')

@section('content')
<h3 class="mb-4">🏢 Apartments Dashboard</h3>

{{-- Summary Cards --}}
<div class="row mb-4 g-3">
    @php
        $statusCounts = [
            'paid' => 0,
            'partial' => 0,
            'unpaid' => 0,
            'empty' => 0
        ];
        foreach ($apartments as $apt) {
            $statusCounts[$apt->status] = ($statusCounts[$apt->status] ?? 0) + 1;
        }
    @endphp

    <div class="col-md-3 col-lg-3 col-xl-3">
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

    <div class="col-md-3 col-lg-3 col-xl-3">
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

    <div class="col-md-3 col-lg-3 col-xl-3">
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

    <div class="col-md-3 col-lg-3 col-xl-3">
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
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('secretary.apartments.create') }}" class="btn btn-primary">➕ Add Apartment</a>

    <form action="{{ route('secretary.apartments.index') }}" method="GET" class="d-flex gap-2">
        <input type="month" name="month" value="{{ $month }}" class="form-control" />
        <select name="status" class="form-select">
            <option value="">All Status</option>
            <option value="paid" {{ ($statusFilter=='paid')?'selected':'' }}>Paid</option>
            <option value="partial" {{ ($statusFilter=='partial')?'selected':'' }}>Partial</option>
            <option value="unpaid" {{ ($statusFilter=='unpaid')?'selected':'' }}>Unpaid</option>
            <option value="empty" {{ ($statusFilter=='empty')?'selected':'' }}>Empty</option>
        </select>
        <button type="submit" class="btn btn-success">Filter</button>
    </form>
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
                <td>{{ $apartment->rooms }}</td>
                <td>{{ number_format($apartment->rent) }}</td>
                <td>{{ $apartment->tenant?->name ?? 'Unassigned' }}</td>
                <td>
                    @php
                        $statusColors = [
                            'paid' => 'success',
                            'partial' => 'warning',
                            'unpaid' => 'danger',
                            'empty' => 'secondary'
                        ];
                    @endphp
                    <span class="badge bg-{{ $statusColors[$apartment->status] ?? 'secondary' }}">
                        {{ $apartment->status == 'empty' ? 'Empty' : ucfirst($apartment->status) }}
                    </span>
                </td>
                <td>
                    {{ $apartment->status != 'empty' ? 'UGX '.number_format($apartment->dueAmount) : '-' }}
                </td>
                <td>
                    <span class="badge bg-info text-dark">
                        {{ number_format($apartment->totalPaid) }}/{{ number_format($apartment->rent) }}
                    </span>
                </td>
         <td>
    {{-- Show credit if exists --}}
    @if($apartment->tenant && $apartment->tenant->credit_balance > 0)
        <span class="badge bg-success mb-1 d-block">
            + UGX {{ number_format($apartment->tenant->credit_balance) }} credit
        </span>
    @endif

    {{-- Show progress bar if apartment is not empty --}}
    @if($apartment->status != 'empty')
        <div class="progress">
            <div class="progress-bar bg-{{ $statusColors[$apartment->status] ?? 'secondary' }}" 
                role="progressbar" 
                style="width: {{ min(100, ($apartment->totalPaid / max(1,$apartment->rent)) * 100) }}%">
            </div>
        </div>
    @elseif(!$apartment->tenant)
        <span class="text-muted">No tenant</span>
    @endif
</td>


                <td>
                    <a href="{{ route('secretary.payments.create') }}?tenant={{ $apartment->tenant?->id }}" class="btn btn-sm btn-success mb-1">➕ Payment</a>
                    <a href="{{ route('secretary.apartments.edit', $apartment) }}" class="btn btn-sm btn-warning mb-1">✏️ Edit</a>
                    <button type="button" class="btn btn-sm btn-info mb-1" data-bs-toggle="modal" data-bs-target="#historyModal{{ $apartment->id }}">📄 History</button>
                    <form action="{{ route('secretary.apartments.destroy', $apartment) }}" method="POST" style="display:inline-block;">
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

{{-- Payment History Modals (outside table) --}}
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

@endsection
