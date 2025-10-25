@extends('secretary.layouts.app')

@section('title', 'Apartments Dashboard')

@section('content')
<style>
    :root {
        --primary: #2563eb;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --secondary: #64748b;
        --bg-light: #f8fafc;
        --text-dark: #1e293b;
        --border: #e2e8f0;
    }

    body {
        font-family: 'Inter', sans-serif;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
        gap: 1rem;
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 600;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .page-title i {
        color: var(--primary);
    }

    /* Summary cards */
    .summary-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        padding: 1rem 1.25rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        transition: all 0.25s ease;
    }

    .summary-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.07);
    }

    .summary-card .icon {
        font-size: 2rem;
        opacity: 0.8;
    }

    .summary-title {
        font-size: 0.875rem;
        color: var(--secondary);
        margin-bottom: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .summary-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-dark);
    }

    /* Filter form */
    .filter-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .filter-bar .btn-primary {
        background: var(--primary);
        border: none;
        font-weight: 500;
    }

    .filter-bar .btn-primary:hover {
        background: #1d4ed8;
    }

    /* Table */
    .table-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    table th {
        background: var(--text-dark);
        color: white;
        text-transform: uppercase;
        font-size: 0.8125rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        padding: 0.875rem 1rem;
        border: none;
    }

    table td {
        vertical-align: middle;
        color: var(--text-dark);
        font-size: 0.9375rem;
        border-top: 1px solid var(--border);
        padding: 0.875rem 1rem;
    }

    .table-hover tbody tr:hover {
        background-color: var(--bg-light);
    }

    /* Progress bar */
    .progress {
        height: 8px;
        border-radius: 1rem;
        background: #e5e7eb;
        overflow: hidden;
    }

    .progress-bar {
        transition: width 0.4s ease;
    }

    /* Buttons */
    .btn-sm {
        font-size: 0.8125rem;
        font-weight: 500;
        padding: 0.4rem 0.75rem;
        border-radius: 0.375rem;
    }

    /* Modal tweaks */
    .modal-content {
        border-radius: 0.75rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
</style>

<div class="container-fluid py-4">
    <div class="page-header">
        <h1 class="page-title"><i class="bi bi-building"></i> Apartments Dashboard</h1>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
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

        <div class="col-6 col-md-3">
            <div class="summary-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="summary-title">Paid</div>
                        <div class="summary-value">{{ $statusCounts['paid'] }}</div>
                    </div>
                    <i class="bi bi-check-circle text-success icon"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="summary-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="summary-title">Partial</div>
                        <div class="summary-value">{{ $statusCounts['partial'] }}</div>
                    </div>
                    <i class="bi bi-hourglass-split text-warning icon"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="summary-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="summary-title">Unpaid</div>
                        <div class="summary-value">{{ $statusCounts['unpaid'] }}</div>
                    </div>
                    <i class="bi bi-x-circle text-danger icon"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="summary-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="summary-title">Empty</div>
                        <div class="summary-value">{{ $statusCounts['empty'] }}</div>
                    </div>
                    <i class="bi bi-dash-circle text-secondary icon"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar">
        <a href="{{ route('secretary.apartments.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Apartment
        </a>
        <form action="{{ route('secretary.apartments.index') }}" method="GET" class="d-flex flex-wrap gap-2">
            <input type="month" name="month" value="{{ $month }}" class="form-control" />
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="paid" {{ $statusFilter=='paid'?'selected':'' }}>Paid</option>
                <option value="partial" {{ $statusFilter=='partial'?'selected':'' }}>Partial</option>
                <option value="unpaid" {{ $statusFilter=='unpaid'?'selected':'' }}>Unpaid</option>
                <option value="empty" {{ $statusFilter=='empty'?'selected':'' }}>Empty</option>
            </select>
            <button type="submit" class="btn btn-success">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
    @endif

    {{-- Apartments Table --}}
    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Number</th>
                        <th>Rooms</th>
                        <th>Rent</th>
                        <th>Tenant</th>
                        <th>Status</th>
                        <th>Due</th>
                        <th>Paid</th>
                        <th>Progress</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($apartments as $apartment)
                    <tr>
                        <td>{{ $apartment->id }}</td>
                        <td><strong>{{ $apartment->number }}</strong></td>
                        <td>{{ $apartment->rooms }}</td>
                        <td>UGX {{ number_format($apartment->rent) }}</td>
                        <td>{{ $apartment->tenant?->name ?? '—' }}</td>
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
                                {{ ucfirst($apartment->status) }}
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
                        <td style="min-width:120px;">
                            @if($apartment->tenant && $apartment->tenant->credit_balance > 0)
                                <span class="badge bg-success mb-1 d-block">
                                    + UGX {{ number_format($apartment->tenant->credit_balance) }} credit
                                </span>
                            @endif
                            @if($apartment->status != 'empty')
                                <div class="progress">
                                    <div class="progress-bar bg-{{ $statusColors[$apartment->status] ?? 'secondary' }}"
                                         style="width: {{ min(100, ($apartment->totalPaid / max(1,$apartment->rent)) * 100) }}%">
                                    </div>
                                </div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('secretary.payments.create') }}?tenant={{ $apartment->tenant?->id }}" class="btn btn-sm btn-success mb-1"><i class="bi bi-plus"></i></a>
                            <a href="{{ route('secretary.apartments.edit', $apartment) }}" class="btn btn-sm btn-warning mb-1"><i class="bi bi-pencil"></i></a>
                            <button class="btn btn-sm btn-info mb-1" data-bs-toggle="modal" data-bs-target="#historyModal{{ $apartment->id }}"><i class="bi bi-clock-history"></i></button>
                            <form action="{{ route('secretary.apartments.destroy', $apartment) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Payment History Modals --}}
    @foreach($apartments as $apartment)
    <div class="modal fade" id="historyModal{{ $apartment->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Payment History — {{ $apartment->number }}</h5>
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
                                <th>Gym</th>
                                <th>Date</th>
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
</div>
@endsection
