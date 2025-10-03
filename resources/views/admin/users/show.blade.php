@extends('admin.layouts.app')
@section('title','View User')

@section('content')
<div class="card shadow-sm mb-4">
    <div class="card-header bg-info text-white"><h4><i class="bi bi-eye"></i> User Details</h4></div>
    <div class="card-body">
        <table class="table table-bordered">
            <tr><th>ID</th><td>{{ $user->id }}</td></tr>
            <tr><th>Name</th><td>{{ $user->name }}</td></tr>
            <tr><th>Email</th><td>{{ $user->email }}</td></tr>
            <tr><th>Role</th><td>{{ ucfirst($user->role) }}</td></tr>
            <tr><th>Back Debt</th><td>{{ $user->back_debt ?? '-' }}</td></tr>
            <tr><th>Created At</th><td>{{ $user->created_at->format('d M, Y H:i') }}</td></tr>
            <tr><th>Updated At</th><td>{{ $user->updated_at->format('d M, Y H:i') }}</td></tr>
        </table>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>
@endsection
