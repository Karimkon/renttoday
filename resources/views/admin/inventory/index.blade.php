@extends('admin.layouts.app')

@section('title','Hotel Inventory')

@section('content')
<h3 class="mb-4">🏨 Hotel Inventory</h3>

<a href="{{ route('admin.inventory.create') }}" class="btn btn-primary mb-3">➕ Add Inventory Item</a>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="table-responsive shadow-sm">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Item</th>
                <th>Quantity</th>
                <th>Location</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inventories as $inv)
            <tr>
                <td>{{ $inv->id }}</td>
                <td>{{ $inv->item }}</td>
                <td>{{ $inv->quantity }}</td>
                <td>{{ $inv->location }}</td>
                <td>
                    <a href="{{ route('admin.inventory.edit', $inv) }}" class="btn btn-sm btn-warning">✏️ Edit</a>
                    <form action="{{ route('admin.inventory.destroy', $inv) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">🗑 Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
