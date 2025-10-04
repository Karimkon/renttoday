@extends('admin.layouts.app')
@section('title','Add Tenant')

@section('content')
<h3 class="mb-4">➕ Add Tenant</h3>

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('admin.tenants.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
    </div>

    <div class="mb-3">
        <label for="phone" class="form-label">Phone</label>
        <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}">
    </div>

    <button type="submit" class="btn btn-success">Add Tenant</button>
    <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
