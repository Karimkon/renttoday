<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class AdminTenantController extends Controller
{
    public function index() {
        $tenants = Tenant::with('apartment')->get();
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
