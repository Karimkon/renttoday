@extends('admin.layouts.app')
@section('title','Users Management')

@section('content')
<h3 class="mb-4">👥 Users</h3>

<a href="{{ route('admin.users.create') }}" class="btn btn-primary mb-3"><i class="bi bi-person-plus-fill"></i> Add User</a>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="table-responsive shadow-sm">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Back Debt</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ ucfirst($user->role) }}</td>
                <td>{{ $user->back_debt ?? '-' }}</td>
                <td>{{ $user->created_at->format('d M, Y') }}</td>
                <td>
                    <a href="{{ route('admin.users.edit',$user) }}" class="btn btn-sm btn-warning mb-1"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="{{ route('admin.users.show',$user) }}" class="btn btn-sm btn-info mb-1"><i class="bi bi-eye"></i> View</a>
                    <form action="{{ route('admin.users.destroy',$user) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $users->links() }}
</div>
@endsection
