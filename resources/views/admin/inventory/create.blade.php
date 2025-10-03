@extends('admin.layouts.app')

@section('title','Add Inventory Item')

@section('content')
<h3>Add Inventory Item</h3>
<a href="{{ route('admin.inventory.index') }}" class="btn btn-secondary mb-3">⬅ Back</a>

<form action="{{ route('admin.inventory.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label class="form-label">Item Name</label>
        <input type="text" name="item" class="form-control" value="{{ old('item') }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Quantity</label>
        <input type="number" name="quantity" class="form-control" value="{{ old('quantity') }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Location</label>
        <input type="text" name="location" class="form-control" value="{{ old('location') }}" required>
    </div>
    <button type="submit" class="btn btn-success">Add Item</button>
</form>
@endsection
