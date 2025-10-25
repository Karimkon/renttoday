<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::latest();

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        $expenses = $query->paginate(20);
        $expenseTypes = Expense::getTypes();
        $expenseCategories = Expense::getCategories();

        return view('admin.expenses.index', compact('expenses', 'expenseTypes', 'expenseCategories'));
    }

    public function create()
    {
        $expenseTypes = Expense::getTypes();
        $expenseCategories = Expense::getCategories();
        
        return view('admin.expenses.create', compact('expenseTypes', 'expenseCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:operating,administrative,maintenance,other',
            'category' => 'required|in:utilities,salaries,office_supplies,maintenance,insurance,taxes,other',
            'date' => 'required|date',
            'payment_method' => 'required|string|max:100',
            'reference' => 'nullable|string|max:100',
            'status' => 'required|in:paid,unpaid',
            'notes' => 'nullable|string'
        ]);

        Expense::create($request->all());

        return redirect()->route('admin.expenses.index')
                         ->with('success', 'Expense added successfully.');
    }

    public function show(Expense $expense)
    {
        return view('admin.expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        $expenseTypes = Expense::getTypes();
        $expenseCategories = Expense::getCategories();
        
        return view('admin.expenses.edit', compact('expense', 'expenseTypes', 'expenseCategories'));
    }

    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:operating,administrative,maintenance,other',
            'category' => 'required|in:utilities,salaries,office_supplies,maintenance,insurance,taxes,other',
            'date' => 'required|date',
            'payment_method' => 'required|string|max:100',
            'reference' => 'nullable|string|max:100',
            'status' => 'required|in:paid,unpaid',
            'notes' => 'nullable|string'
        ]);

        $expense->update($request->all());

        return redirect()->route('admin.expenses.index')
                         ->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('admin.expenses.index')
                         ->with('success', 'Expense deleted successfully.');
    }

    public function export(Request $request)
    {
        $expenses = Expense::when($request->type, function($query, $type) {
            return $query->where('type', $type);
        })->when($request->start_date, function($query, $startDate) {
            return $query->where('date', '>=', $startDate);
        })->when($request->end_date, function($query, $endDate) {
            return $query->where('date', '<=', $endDate);
        })->get();

        // You can implement CSV or PDF export here
        return response()->json($expenses);
    }
}