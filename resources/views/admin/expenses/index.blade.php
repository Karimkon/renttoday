@extends('admin.layouts.app')

@section('title', 'Manage Expenses')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-cash-coin me-2"></i> Expense Management</h3>
        <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Expense
        </a>
    </div>

    <!-- Filters Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        @foreach($expenseTypes as $key => $label)
                            <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($expenseCategories as $key => $label)
                            <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-12 mt-3">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-filter"></i> Apply Filters
                    </button>
                    <a href="{{ route('admin.expenses.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Expenses Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $expense)
                        <tr>
                            <td>{{ $expense->id }}</td>
                            <td>
                                <strong>{{ $expense->description }}</strong>
                                @if($expense->notes)
                                    <br><small class="text-muted">{{ Str::limit($expense->notes, 50) }}</small>
                                @endif
                            </td>
                            <td class="fw-bold text-danger">UGX {{ number_format($expense->amount, 2) }}</td>
                            <td>
                                <span class="badge bg-info">{{ $expense->type_label }}</span>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $expense->category_label }}</span>
                            </td>
                            <td>{{ $expense->date->format('M j, Y') }}</td>
                            <td>{{ $expense->payment_method }}</td>
                            <td>
                                <span class="badge bg-{{ $expense->status == 'paid' ? 'success' : 'warning' }}">
                                    {{ ucfirst($expense->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.expenses.show', $expense) }}" 
                                       class="btn btn-info" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.expenses.edit', $expense) }}" 
                                       class="btn btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.expenses.destroy', $expense) }}" 
                                          method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" title="Delete"
                                                onclick="return confirm('Are you sure you want to delete this expense?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-cash-coin fs-1"></i>
                                <p class="mt-2">No expenses found</p>
                                <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary btn-sm">
                                    Add First Expense
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Summary -->
            @if($expenses->count() > 0)
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6>Total Expenses</h6>
                            <h3 class="text-danger">UGX {{ number_format($expenses->sum('amount'), 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6>Paid Expenses</h6>
                            <h3 class="text-success">UGX {{ number_format($expenses->where('status', 'paid')->sum('amount'), 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6>Unpaid Expenses</h6>
                            <h3 class="text-warning">UGX {{ number_format($expenses->where('status', 'unpaid')->sum('amount'), 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Showing {{ $expenses->firstItem() }} to {{ $expenses->lastItem() }} of {{ $expenses->total() }} expenses
                </div>
                {{ $expenses->links() }}
            </div>
        </div>
    </div>
</div>
@endsection