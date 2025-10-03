@extends('admin.layouts.app')

@section('title','Edit Inventory Item')

@section('content')
<h3>Edit Inventory Item</h3>
<a href="{{ route('admin.inventory.index') }}" class="btn btn-secondary mb-3">⬅ Back</a>

<form action="{{ route('admin.inventory.update', $inventory) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label">Item Name</label>
        <input type="text" name="item" class="form-control" value="{{ old('item',$inventory->item) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Quantity</label>
        <input type="number" name="quantity" class="form-control" value="{{ old('quantity',$inventory->quantity) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Location</label>
        <input type="text" name="location" class="form-control" value="{{ old('location',$inventory->location) }}" required>
    </div>
    <button type="submit" class="btn btn-success">Update Item</button>
</form>
@endsection
