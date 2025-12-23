@extends('admin.layouts.app')

@section('title', 'Edit Expense')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">Edit Expense</h4>
                    <small class="text-muted">For: {{ $landlord->name }} - {{ \Carbon\Carbon::createFromFormat('Y-m', $expense->month)->format('F Y') }}</small>
                </div>
                <a href="{{ route('admin.landlords.monthly-report', ['landlord' => $landlord->id, 'month' => $expense->month]) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Report
                </a>
            </div>
        </div>
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.landlords.expenses.update', ['landlord' => $landlord->id, 'expense' => $expense->id]) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="month" value="{{ $expense->month }}">

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="description" name="description"
                                   value="{{ old('description', $expense->description) }}" required
                                   placeholder="e.g., Painting work for apartment">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (UGX) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="amount" name="amount"
                                   value="{{ old('amount', $expense->amount) }}" required min="1" step="1"
                                   placeholder="e.g., 150000">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $value => $label)
                                    <option value="{{ $value }}" {{ old('category', $expense->category) == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="apartment_id" class="form-label">Apartment (Optional)</label>
                            <select class="form-select" id="apartment_id" name="apartment_id">
                                <option value="">General / All Properties</option>
                                @foreach($apartments as $apartment)
                                    <option value="{{ $apartment->id }}" {{ old('apartment_id', $expense->apartment_id) == $apartment->id ? 'selected' : '' }}>
                                        {{ $apartment->number }} - {{ $apartment->location }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="expense_date" class="form-label">Expense Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="expense_date" name="expense_date"
                                   value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="reference" class="form-label">Reference / Receipt Number</label>
                            <input type="text" class="form-control" id="reference" name="reference"
                                   value="{{ old('reference', $expense->reference) }}"
                                   placeholder="e.g., INV-2025-001">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"
                                      placeholder="Additional notes about this expense">{{ old('notes', $expense->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.landlords.monthly-report', ['landlord' => $landlord->id, 'month' => $expense->month]) }}"
                       class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Expense
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
