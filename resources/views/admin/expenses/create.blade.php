@extends('admin.layouts.app')

@section('title', 'Add Expense')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-cash-coin me-2"></i> Add New Expense</h3>
        <a href="{{ route('admin.expenses.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Expenses
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.expenses.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                            <input type="text" name="description" class="form-control" 
                                   value="{{ old('description') }}" required 
                                   placeholder="Enter expense description">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Amount (UGX) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" 
                                   value="{{ old('amount') }}" min="0" step="0.01" required 
                                   placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="">Select Type</option>
                                @foreach($expenseTypes as $key => $label)
                                    <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                @foreach($expenseCategories as $key => $label)
                                    <option value="{{ $key }}" {{ old('category') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" 
                                   value="{{ old('date', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                            <input type="text" name="payment_method" class="form-control" 
                                   value="{{ old('payment_method') }}" required 
                                   placeholder="e.g., Cash, Bank Transfer, Mobile Money">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reference Number</label>
                            <input type="text" name="reference" class="form-control" 
                                   value="{{ old('reference') }}" 
                                   placeholder="Optional reference number">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="unpaid" {{ old('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" 
                              placeholder="Any additional notes about this expense...">{{ old('notes') }}</textarea>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle me-1"></i> Save Expense
                    </button>
                    <a href="{{ route('admin.expenses.index') }}" class="btn btn-secondary btn-lg">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection