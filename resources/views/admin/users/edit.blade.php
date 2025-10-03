@extends('admin.layouts.app')
@section('title','Edit User')

@section('content')
<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning text-dark"><h4><i class="bi bi-pencil"></i> Edit User</h4></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.update',$user) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label>Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name',$user->name) }}" required>
            </div>
            <div class="mb-3">
                <label>Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ old('email',$user->email) }}" required>
            </div>
            <div class="mb-3">
                <label>Role <span class="text-danger">*</span></label>
                <select name="role" class="form-select" required>
                    @foreach(['admin','finance','secretary','chef','sales','driver','manager'] as $role)
                        <option value="{{ $role }}" @selected(old('role',$user->role)==$role)>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label>Password (leave blank to keep current)</label>
                <input type="password" name="password" class="form-control">
            </div>
            <div class="mb-3">
                <label>Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>
            @if($user->role=='driver')
            <div class="mb-3">
                <label>Back Debt</label>
                <input type="number" name="back_debt" value="{{ old('back_debt',$user->back_debt) }}" class="form-control" min="0">
            </div>
            @endif
            <button class="btn btn-success"><i class="bi bi-check-circle"></i> Update</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        </form>
    </div>
</div>
@endsection
