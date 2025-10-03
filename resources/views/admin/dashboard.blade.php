@extends('admin.layouts.app')
@section('title','Admin Dashboard')

@section('content')
<h3 class="mb-4">🏠 Admin Dashboard</h3>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card shadow-sm p-3">
            <h6>Total Users</h6>
            <h3>{{ \App\Models\User::count() }}</h3>
            <i class="bi bi-people fs-3 text-primary"></i>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm p-3">
            <h6>Total Admins</h6>
            <h3>{{ \App\Models\User::where('role','admin')->count() }}</h3>
            <i class="bi bi-shield-lock fs-3 text-success"></i>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm p-3">
            <h6>Total Finance & Secretary</h6>
            <h3>{{ \App\Models\User::whereIn('role',['finance','secretary'])->count() }}</h3>
            <i class="bi bi-briefcase fs-3 text-warning"></i>
        </div>
    </div>
</div>
@endsection
