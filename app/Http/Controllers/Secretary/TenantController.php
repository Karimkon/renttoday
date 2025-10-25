<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;


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

    // Create tenant
    $tenant = Tenant::create($request->all());

    // Generate a temporary password
    $plainPassword = Str::random(8);

    // Create user (hash password)
    $user = User::firstOrCreate(
        ['email' => $tenant->email],
        [
            'name' => $tenant->name,
            'password' => Hash::make($plainPassword),
            'role' => 'tenant'
        ]
    );

    // Link tenant to user
    $tenant->update(['user_id' => $user->id]);

    // Send email with the real password if newly created
    if ($user->wasRecentlyCreated) {
        Mail::to($tenant->email)->send(new \App\Mail\TenantWelcomeMail($tenant, $plainPassword));
    }

    return redirect()->route('secretary.tenants.index')
                     ->with('success','Tenant added with login access.');
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
