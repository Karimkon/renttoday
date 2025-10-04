@extends('admin.layouts.app')
@section('title','Edit Tenant')

@section('content')
<h3 class="mb-4">✏️ Edit Tenant</h3>

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('admin.tenants.update', $tenant) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $tenant->name) }}" required>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $tenant->email) }}" required>
    </div>

    <div class="mb-3">
        <label for="phone" class="form-label">Phone</label>
        <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $tenant->phone) }}">
    </div>

    <button type="submit" class="btn btn-success">Update Tenant</button>
    <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
