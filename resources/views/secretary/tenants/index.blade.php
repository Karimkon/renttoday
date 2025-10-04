@extends('secretary.layouts.app')

@section('content')
<h3 class="mb-3">👥 Tenants Overview</h3>

<a href="{{ route('secretary.tenants.create') }}" class="btn btn-primary mb-3">➕ Add Tenant</a>

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
                <th>Phone</th>
                <th>Apartment</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tenants as $tenant)
            <tr>
                <td>{{ $tenant->id }}</td>
                <td>{{ $tenant->name }} 
                    @if($tenant->credit_balance > 0)
                        <small class="text-success d-block">
                            💰 Credit: UGX {{ number_format($tenant->credit_balance) }}
                        </small>
                    @endif
                </td>
                <td>{{ $tenant->email }}</td>
                <td>{{ $tenant->phone }}</td>

                <td>
                    @if($tenant->apartment)
                        <span class="badge bg-info text-dark">
                            Apt #{{ $tenant->apartment->number }}
                        </span><br>
                        <small class="text-muted">
                            Rent: UGX {{ number_format($tenant->apartment->rent) }}
                        </small>
                    @else
                        <span class="text-muted">— Not Assigned —</span>
                    @endif
                </td>

                <td>
                    @if($tenant->apartment)
                        <span class="badge bg-success">Has Apartment</span>
                    @else
                        <span class="badge bg-secondary">No Apartment</span>
                    @endif
                </td>

                <td>
                    @if($tenant->apartment)
                        <a href="{{ route('secretary.apartments.edit', $tenant->apartment) }}" 
                           class="btn btn-sm btn-info mb-1">🏠 View Apartment</a>
                    @endif

                    <a href="{{ route('secretary.tenants.edit', $tenant) }}" 
                       class="btn btn-sm btn-warning mb-1">✏️ Edit</a>

                    <form action="{{ route('secretary.tenants.destroy', $tenant) }}" 
                          method="POST" 
                          style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="btn btn-sm btn-danger" 
                                onclick="return confirm('Are you sure you want to delete this tenant?')">
                            🗑 Delete
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
