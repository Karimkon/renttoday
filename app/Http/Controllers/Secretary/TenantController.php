<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index() {
        $tenants = Tenant::with('apartment')->get();
        return view('secretary.tenants.index', compact('tenants'));
    }

    public function create() {
        return view('secretary.tenants.create');
    }

    public function store(Request $request) {
        $request->validate([
            'name'=>'required',
            'email'=>'required|email|unique:tenants',
            'phone'=>'nullable'
        ]);
        Tenant::create($request->all());
        return redirect()->route('secretary.tenants.index')->with('success','Tenant added.');
    }

    public function edit(Tenant $tenant) {
        return view('secretary.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant) {
        $request->validate([
            'name'=>'required',
            'email'=>'required|email|unique:tenants,email,'.$tenant->id,
        ]);
        $tenant->update($request->all());
        return redirect()->route('secretary.tenants.index')->with('success','Tenant updated.');
    }

    public function destroy(Tenant $tenant) {
        $tenant->delete();
        return redirect()->route('secretary.tenants.index')->with('success','Tenant removed.');
    }
}
