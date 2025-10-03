@extends('secretary.layouts.app')

@section('content')
<h3>Edit Tenant</h3>

<a href="{{ route('secretary.tenants.index') }}" class="btn btn-secondary mb-3">⬅ Back</a>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('secretary.tenants.update', $tenant) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $tenant->name) }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $tenant->email) }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $tenant->phone) }}">
    </div>

    <button type="submit" class="btn btn-primary">Update Tenant</button>
</form>
@endsection
