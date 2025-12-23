@extends('admin.layouts.app')

@section('title', 'Expenses for ' . $landlord->name)

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Expenses for {{ $landlord->name }}</h4>
                <small class="text-muted">Total: UGX {{ number_format($expenses->sum('amount')) }}</small>
            </div>
            <div class="d-flex gap-2">
                <!-- Month Filter -->
                <form method="GET" class="d-flex gap-2">
                    <input type="month" name="month" class="form-control" value="{{ $month }}" 
                           onchange="this.form.submit()">
                    @if(request('month'))
                        <a href="{{ route('admin.landlords.expenses.index', $landlord) }}" 
                           class="btn btn-outline-secondary">Clear</a>
                    @endif
                </form>
                
                <!-- Add Expense Button -->
                <a href="{{ route('admin.landlords.expenses.create', ['landlord' => $landlord->id, 'month' => $month]) }}"
                   class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Expense
                </a>
                <a href="{{ route('admin.landlords.monthly-report', ['landlord' => $landlord->id, 'month' => $month]) }}"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Report
                </a>
            </div>
        </div>
        <div class="card-body">
            @if($expenses->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Apartment</th>
                            <th>Amount</th>
                            <th>Month</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expenses as $expense)
                        <tr>
                            <td>{{ $expense->expense_date->format('M j, Y') }}</td>
                            <td>{{ $expense->description }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $expense->category_label }}</span>
                            </td>
                            <td>
                                {{ $expense->apartment ? $expense->apartment->number : 'General' }}
                            </td>
                            <td class="text-danger fw-bold">
                                UGX {{ number_format($expense->amount) }}
                            </td>
                            <td>
                                {{ \Carbon\Carbon::createFromFormat('Y-m', $expense->month)->format('F Y') }}
                            </td>
                            <td>
                                <a href="{{ route('admin.landlords.expenses.edit', ['landlord' => $landlord->id, 'expense' => $expense->id]) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.landlords.expenses.destroy', ['landlord' => $landlord->id, 'expense' => $expense->id]) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this expense?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-warning fw-bold">
                            <td colspan="4">TOTAL</td>
                            <td class="text-danger">UGX {{ number_format($expenses->sum('amount')) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="d-flex justify-content-center">
                {{ $expenses->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <i class="bi bi-receipt-cutoff display-1 text-muted"></i>
                <h5 class="mt-3">No expenses recorded</h5>
                <p class="text-muted">
                    @if(request('month'))
                        No expenses for {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}
                    @else
                        No expenses recorded yet
                    @endif
                </p>
                <a href="{{ route('admin.landlords.expenses.create', ['landlord' => $landlord->id, 'month' => $month]) }}"
                   class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Your First Expense
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection