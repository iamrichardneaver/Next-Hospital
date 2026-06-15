@extends('layouts.app')

@section('title', 'Lab Inventory')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;"><i class="bi bi-boxes"></i> Lab Inventory Catalog</h1>
            <p class="text-secondary mb-0">Reagents and consumables — distinct from pharmacy drug stock</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('lab.inventory.movements') }}" class="btn btn-outline-secondary"><i class="bi bi-clock-history"></i> Movement History</a>
            @can('create_lab_purchases')
            <a href="{{ route('lab.purchases.create') }}" class="btn btn-primary"><i class="bi bi-cart-plus"></i> New Purchase Order</a>
            @endcan
        </div>
    </div>

    @if(!empty($lowStockItems))
    <div class="alert alert-warning">
        <strong><i class="bi bi-exclamation-triangle"></i> Low stock at this branch:</strong>
        @foreach($lowStockItems as $alert)
        <span class="badge bg-warning text-dark me-1">{{ $alert['name'] }} ({{ $alert['available'] }} left)</span>
        @endforeach
    </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-4"><div class="stat-card primary"><div class="stat-label">Reagents</div><div class="stat-value">{{ $stats['reagents'] }}</div></div></div>
        <div class="col-md-4"><div class="stat-card info"><div class="stat-label">Consumables</div><div class="stat-value">{{ $stats['consumables'] }}</div></div></div>
        <div class="col-md-4"><div class="stat-card warning"><div class="stat-label">Low Stock Items</div><div class="stat-value">{{ $stats['low_stock'] }}</div></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Reagents</strong></div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light"><tr><th>Name</th><th>Catalog #</th><th>Stock</th>@if($branchId)<th>Branch Stock</th>@endif<th>Reorder</th><th>Expiry</th></tr></thead>
                <tbody>
                    @forelse($reagents as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->catalog_number ?? '—' }}</td>
                        <td>{{ $item->current_stock }} {{ $item->unit_of_measure }}</td>
                        @if($branchId)
                        <td>{{ $branchStock->get('reagent:'.$item->id)?->sum('quantity') ?? 0 }}</td>
                        @endif
                        <td>{{ $item->reorder_level }}</td>
                        <td>{{ $item->expiry_date?->format('M d, Y') ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted text-center">No reagents in catalog.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Consumables</strong></div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light"><tr><th>Name</th><th>Catalog #</th><th>Stock</th>@if($branchId)<th>Branch Stock</th>@endif<th>Reorder</th><th>Expiry</th></tr></thead>
                <tbody>
                    @forelse($consumables as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->catalog_number ?? '—' }}</td>
                        <td>{{ $item->current_stock }} {{ $item->unit_of_measure }}</td>
                        @if($branchId)
                        <td>{{ $branchStock->get('consumable:'.$item->id)?->sum('quantity') ?? 0 }}</td>
                        @endif
                        <td>{{ $item->reorder_level }}</td>
                        <td>{{ $item->expiry_date?->format('M d, Y') ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted text-center">No consumables in catalog.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
