@extends('secretary.layouts.app')

@section('content')
<style>
    :root {
        --primary-blue: #2563eb;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --bg-subtle: #f8fafc;
        --border-color: #e2e8f0;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 600;
        color: var(--text-primary);
        letter-spacing: -0.025em;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
    }

    .page-title i {
        color: var(--primary-blue);
        font-size: 1.5rem;
    }

    .btn-add {
        background: var(--primary-blue);
        border: none;
        color: white;
        padding: 0.625rem 1.25rem;
        border-radius: 0.5rem;
        font-weight: 500;
        font-size: 0.9375rem;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .btn-add:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
        color: white;
    }

    .btn-add i {
        font-size: 1rem;
    }

    .alert-success {
        background: #d1fae5;
        border: 1px solid #a7f3d0;
        color: #065f46;
        border-radius: 0.5rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        font-weight: 500;
    }

    .table-card {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 0.75rem;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .table-responsive {
        border-radius: 0.75rem;
    }

    .table {
        margin: 0;
        border: none;
    }

    .table thead {
        background-color: #f3f4f6; /* light gray */
    }

    .table thead th {
       color: var(--text-primary); /* dark text */
    }

    .table tbody td {
        padding: 1rem 1.25rem;
        vertical-align: middle;
        color: var(--text-primary);
        font-size: 0.9375rem;
        border-bottom: 1px solid var(--border-color);
    }

    .table tbody tr {
        transition: background-color 0.2s ease;
    }

    .table tbody tr:hover {
        background-color: var(--bg-subtle);
    }

    .table tbody tr:last-child td {
        border-bottom: none;
    }

    .tenant-name {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
    }

    .credit-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        background: #d1fae5;
        color: #065f46;
        padding: 0.25rem 0.625rem;
        border-radius: 0.375rem;
        font-size: 0.8125rem;
        font-weight: 500;
        margin-top: 0.25rem;
    }

    .apartment-badge {
        display: inline-flex;
        align-items: center;
        background: #dbeafe;
        color: #1e40af;
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        font-weight: 600;
        font-size: 0.875rem;
        margin-bottom: 0.375rem;
        gap: 0.5rem;
    }

    .rent-amount {
        color: var(--text-secondary);
        font-size: 0.8125rem;
        font-weight: 500;
    }

    .status-badge {
        padding: 0.375rem 0.875rem;
        border-radius: 0.375rem;
        font-size: 0.8125rem;
        font-weight: 600;
        display: inline-block;
    }

    .status-assigned {
        background: #d1fae5;
        color: #065f46;
    }

    .status-unassigned {
        background: #f1f5f9;
        color: #475569;
    }

    .action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .btn-action {
        padding: 0.5rem 0.875rem;
        border-radius: 0.375rem;
        font-size: 0.8125rem;
        font-weight: 500;
        border: 1px solid;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        white-space: nowrap;
    }

    .btn-view {
        background: white;
        border-color: #3b82f6;
        color: #2563eb;
    }

    .btn-view:hover {
        background: #3b82f6;
        color: white;
        transform: translateY(-1px);
    }

    .btn-edit {
        background: white;
        border-color: #f59e0b;
        color: #d97706;
    }

    .btn-edit:hover {
        background: #f59e0b;
        color: white;
        transform: translateY(-1px);
    }

    .btn-delete {
        background: white;
        border-color: #ef4444;
        color: #dc2626;
    }

    .btn-delete:hover {
        background: #ef4444;
        color: white;
        transform: translateY(-1px);
    }

    .text-muted-custom {
        color: var(--text-secondary);
        font-style: italic;
    }

    .id-cell {
        color: var(--text-secondary);
        font-weight: 600;
        font-size: 0.875rem;
    }

    @media (max-width: 768px) {
        .page-title {
            font-size: 1.5rem;
        }

        .table thead th,
        .table tbody td {
            padding: 0.75rem;
            font-size: 0.875rem;
        }

        .action-buttons {
            flex-direction: column;
        }

        .btn-action {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="page-header">
        <h1 class="page-title">
            <i class="bi bi-people"></i>
            <span>Tenants Overview</span>
        </h1>
        <a href="{{ route('secretary.tenants.create') }}" class="btn-add">
            <i class="bi bi-plus-circle"></i>
            <span>Add Tenant</span>
        </a>
    </div>

    @if(session('success'))
        <div class="alert-success">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="table-card">
        <div class="table-responsive">
            <table class="table">
               <thead>
    <tr>
        <th>ID</th>
        <th>Tenant</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Next Due</th>
        <th>Apartment</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>
</thead>
<tbody>
    @foreach($tenants as $tenant)
    <tr>
        <td class="id-cell">#{{ $tenant->id }}</td>
        <td>
            <div class="tenant-name">{{ $tenant->name }}</div>
            @if($tenant->credit_balance > 0)
                <div class="credit-badge">
                    <i class="bi bi-cash-coin"></i>
                    <span>Credit: UGX {{ number_format($tenant->credit_balance) }}</span>
                </div>
            @endif
        </td>
        <td>{{ $tenant->email }}</td>
        <td>{{ $tenant->phone }}</td>
        <td>
            @if($tenant->next_due)
                {{ \Carbon\Carbon::parse($tenant->next_due)->format('F j, Y') }}
            @else
                —
            @endif
        </td>
        <td>
            @if($tenant->apartment)
                <div class="apartment-badge">
                    <i class="bi bi-building"></i>
                    <span>Apt #{{ $tenant->apartment->number }}</span>
                </div>
                <div class="rent-amount">
                    Rent: UGX {{ number_format($tenant->apartment->rent) }}
                </div>
            @else
                <span class="text-muted-custom">Not Assigned</span>
            @endif
        </td>
        <td>
            @if($tenant->apartment)
                <span class="status-badge status-assigned">
                    <i class="bi bi-check-circle me-1"></i>Assigned
                </span>
            @else
                <span class="status-badge status-unassigned">
                    <i class="bi bi-dash-circle me-1"></i>Unassigned
                </span>
            @endif
        </td>
        <td>
            <div class="action-buttons">
                @if($tenant->apartment)
                    <a href="{{ route('secretary.apartments.edit', $tenant->apartment) }}" 
                       class="btn-action btn-view">
                        <i class="bi bi-house-door"></i>
                        <span>View Apartment</span>
                    </a>
                @endif
                <a href="{{ route('secretary.tenants.edit', $tenant) }}" 
                   class="btn-action btn-edit">
                    <i class="bi bi-pencil"></i>
                    <span>Edit</span>
                </a>
                <form action="{{ route('secretary.tenants.destroy', $tenant) }}" 
                      method="POST" 
                      style="display: inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="btn-action btn-delete" 
                            onclick="return confirm('Are you sure you want to delete this tenant?')">
                        <i class="bi bi-trash"></i>
                        <span>Delete</span>
                    </button>
                </form>
            </div>
        </td>
    </tr>
    @endforeach
</tbody>

            </table>
        </div>
    </div>
</div>
@endsection