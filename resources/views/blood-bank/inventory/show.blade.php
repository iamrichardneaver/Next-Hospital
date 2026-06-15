@extends('layouts.app')

@section('title', 'Inventory Details')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between mb-4">
        <h1 class="h3 mb-0">{{ $inventory->blood_group }} — {{ str_replace('_',' ', $inventory->blood_component ?? 'whole blood') }}</h1>
        <div><a href="{{ route('blood-bank.index', ['tab'=>'inventory']) }}" class="btn btn-secondary">Back</a>
        <a href="{{ route('blood-bank.inventory.edit', $inventory) }}" class="btn btn-warning">Edit Levels</a></div>
    </div>
    <div class="card"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Available Units</dt><dd class="col-sm-9">{{ $inventory->available_units }}</dd>
            <dt class="col-sm-3">Reserved</dt><dd class="col-sm-9">{{ $inventory->reserved_units }}</dd>
            <dt class="col-sm-3">Minimum Level</dt><dd class="col-sm-9">{{ $inventory->minimum_stock_level }}</dd>
            <dt class="col-sm-3">Optimal Level</dt><dd class="col-sm-9">{{ $inventory->optimal_stock_level ?? '—' }}</dd>
            <dt class="col-sm-3">Branch</dt><dd class="col-sm-9">{{ $inventory->branch?->name ?? '—' }}</dd>
        </dl>
    </div></div>
</div>
@endsection
