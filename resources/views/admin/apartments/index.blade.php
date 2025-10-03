@extends('admin.layouts.app')

@section('title','Apartments Dashboard')

@section('content')
<h3 class="mb-4">🏢 Apartments Dashboard</h3>

{{-- Summary Cards --}}
<div class="row mb-4 g-3">
    @php
        $statusCounts = ['paid'=>0,'partial'=>0,'unpaid'=>0,'empty'=>0];
        foreach ($apartments as $apt) {
            $statusCounts[$apt->status] = ($statusCounts[$apt->status] ?? 0)+1;
        }
    @endphp

    @foreach($statusCounts as $status => $count)
        @php
            $colors = ['paid'=>'success','partial'=>'warning','unpaid'=>'danger','empty'=>'secondary'];
        @endphp
        <div class="col-md-3">
            <div class="card text-white bg-{{ $colors[$status] }} shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">{{ ucfirst($status) }}</h5>
                        <p class="card-text fs-4">{{ $count }}</p>
                    </div>
                    <i class="bi bi-circle fs-2"></i>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.apartments.create') }}" class="btn btn-primary">➕ Add Apartment</a>

    <form action="{{ route('admin.apartments.index') }}" method="GET" class="d-flex gap-2">
        <input type="month" name="month" value="{{ $month }}" class="form-control" />
        <select name="status" class="form-select">
            <option value="">All Status</option>
            @foreach(['paid','partial','unpaid','empty'] as $st)
                <option value="{{ $st }}" {{ $statusFilter==$st?'selected':'' }}>{{ ucfirst($st) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-success">Filter</button>
    </form>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="table-responsive shadow-sm">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Number</th>
                <th>Rooms</th>
                <th>Rent</th>
                <th>Tenant</th>
                <th>Status</th>
                <th>Due</th>
                <th>Total Paid</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($apartments as $apt)
            <tr>
                <td>{{ $apt->id }}</td>
                <td>{{ $apt->number }}</td>
                <td>{{ $apt->rooms }}</td>
                <td>{{ number_format($apt->rent) }}</td>
                <td>{{ $apt->tenant?->name ?? 'Unassigned' }}</td>
                <td><span class="badge bg-{{ $apt->status=='paid'?'success':($apt->status=='partial'?'warning':($apt->status=='unpaid'?'danger':'secondary')) }}">
                    {{ ucfirst($apt->status) }}
                </span></td>
                <td>{{ $apt->status != 'empty' ? number_format($apt->dueAmount) : '-' }}</td>
                <td>{{ number_format($apt->totalPaid) }}</td>
                <td>
                    <a href="{{ route('admin.apartments.edit',$apt) }}" class="btn btn-sm btn-warning">✏️ Edit</a>
                    <form action="{{ route('admin.apartments.destroy',$apt) }}" method="POST" style="display:inline-block;">
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
