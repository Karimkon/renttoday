@extends('admin.layouts.app')

@section('title', 'Expense Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-cash-coin me-2"></i> Expense Details</h3>
        <a href="{{ route('admin.expenses.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Expenses
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Description:</strong>
                            <p class="fs-5">{{ $expense->description }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Amount:</strong>
                            <p class="fs-5 text-danger">UGX {{ number_format($expense->amount, 2) }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Type:</strong>
                            <p><span class="badge bg-info">{{ $expense->type_label }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Category:</strong>
                            <p><span class="badge bg-secondary">{{ $expense->category_label }}</span></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Date:</strong>
                            <p>{{ $expense->date->format('F j, Y') }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong>
                            <p>
                                <span class="badge bg-{{ $expense->status == 'paid' ? 'success' : 'warning' }}">
                                    {{ ucfirst($expense->status) }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Payment Method:</strong>
                            <p>{{ $expense->payment_method }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Reference:</strong>
                            <p>{{ $expense->reference ?: 'N/A' }}</p>
                        </div>
                    </div>

                    @if($expense->notes)
                    <div class="mb-3">
                        <strong>Notes:</strong>
                        <p class="border p-3 rounded bg-light">{{ $expense->notes }}</p>
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <strong>Created:</strong>
                            <p class="text-muted">{{ $expense->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Last Updated:</strong>
                            <p class="text-muted">{{ $expense->updated_at->format('M j, Y g:i A') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.expenses.edit', $expense) }}" class="btn btn-warning">
                            <i class="bi bi-pencil me-1"></i> Edit Expense
                        </a>
                        <form action="{{ route('admin.expenses.destroy', $expense) }}" method="POST" class="d-grid">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" 
                                    onclick="return confirm('Are you sure you want to delete this expense?')">
                                <i class="bi bi-trash me-1"></i> Delete Expense
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection