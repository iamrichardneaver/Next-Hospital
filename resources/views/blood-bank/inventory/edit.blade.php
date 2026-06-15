@extends('layouts.app')

@section('title', 'Edit Inventory Levels')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Edit Inventory Thresholds</h1>
    <div class="col-lg-6"><div class="card"><div class="card-body">
        <form action="{{ route('blood-bank.inventory.update', $inventory) }}" method="POST">@csrf @method('PUT')
            <div class="mb-3"><label class="form-label">Minimum Stock Level</label><input type="number" step="0.01" name="minimum_stock_level" class="form-control" value="{{ $inventory->minimum_stock_level }}"></div>
            <div class="mb-3"><label class="form-label">Optimal Stock Level</label><input type="number" step="0.01" name="optimal_stock_level" class="form-control" value="{{ $inventory->optimal_stock_level }}"></div>
            <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3">{{ $inventory->notes }}</textarea></div>
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('blood-bank.inventory.show', $inventory) }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div></div></div>
</div>
@endsection
