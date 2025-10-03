@extends('secretary.layouts.app')

@section('content')
<h3>Tenants</h3>

<a href="{{ route('secretary.tenants.create') }}" class="btn btn-primary mb-3">➕ Add Tenant</a>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tenants as $tenant)
        <tr>
            <td>{{ $tenant->id }}</td>
            <td>{{ $tenant->name }}</td>
            <td>{{ $tenant->email }}</td>
            <td>{{ $tenant->phone }}</td>
            <td>
                <a href="{{ route('secretary.tenants.edit', $tenant) }}" class="btn btn-sm btn-warning">✏️ Edit</a>

                <form action="{{ route('secretary.tenants.destroy', $tenant) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">🗑 Delete</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
