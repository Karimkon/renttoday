{{-- resources/views/admin/landlords/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Manage Landlords')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-person-badge me-2"></i> Manage Landlords</h3>
        <a href="{{ route('admin.landlords.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Landlord
        </a>
    </div>

    <!-- Search & Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by name or email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Landlords Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Commission</th>
                            <th>Apartments</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($landlords as $landlord)
                        <tr>
                            <td>{{ $landlord->id }}</td>
                            <td>
                                <strong>{{ $landlord->name }}</strong>
                                @if($landlord->address)
                                    <br><small class="text-muted">{{ Str::limit($landlord->address, 30) }}</small>
                                @endif
                            </td>
                            <td>
                                <div>{{ $landlord->email }}</div>
                                @if($landlord->phone)
                                    <small class="text-muted">{{ $landlord->phone }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $landlord->commission_rate }}%</span>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $landlord->apartments_count }}</span> apartments
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.landlords.show', $landlord) }}" 
                                       class="btn btn-info" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.landlords.edit', $landlord) }}" 
                                       class="btn btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.landlords.destroy', $landlord) }}" 
                                          method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" title="Delete"
                                                onclick="return confirm('Are you sure you want to delete this landlord?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-person-x fs-1"></i>
                                <p class="mt-2">No landlords found</p>
                                <a href="{{ route('admin.landlords.create') }}" class="btn btn-primary btn-sm">
                                    Add First Landlord
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Showing {{ $landlords->firstItem() }} to {{ $landlords->lastItem() }} of {{ $landlords->total() }} landlords
                </div>
                {{ $landlords->links() }}
            </div>
        </div>
    </div>
</div>
@endsection