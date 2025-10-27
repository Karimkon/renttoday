<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class AdminTenantController extends Controller
{
    public function index(Request $request) 
    {
        $query = Tenant::with('apartment');
        
        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Apartment status filter
        if ($request->has('apartment_status')) {
            if ($request->apartment_status == 'with_apartment') {
                $query->has('apartment');
            } elseif ($request->apartment_status == 'without_apartment') {
                $query->doesntHave('apartment');
            }
        }
        
        // Sorting
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'name_desc':
                    $query->orderBy('name', 'desc');
                    break;
                case 'credit_high':
                    $query->orderBy('credit_balance', 'desc');
                    break;
                case 'credit_low':
                    $query->orderBy('credit_balance', 'asc');
                    break;
                case 'recent':
                    $query->orderBy('created_at', 'desc');
                    break;
                default:
                    $query->orderBy('name', 'asc');
            }
        } else {
            $query->orderBy('name', 'asc');
        }
        
        $tenants = $query->get();
        
        return view('admin.tenants.index', compact('tenants'));
    }

    public function create() {
        return view('admin.tenants.create');
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:tenants',
            'phone' => 'nullable'
        ]);

        Tenant::create($request->all());
        return redirect()->route('admin.tenants.index')->with('success','Tenant added.');
    }

    public function edit(Tenant $tenant) {
        return view('admin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant) {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:tenants,email,'.$tenant->id,
        ]);

        $tenant->update($request->all());
        return redirect()->route('admin.tenants.index')->with('success','Tenant updated.');
    }

    public function destroy(Tenant $tenant) {
        $tenant->delete();
        return redirect()->route('admin.tenants.index')->with('success','Tenant removed.');
    }
}
