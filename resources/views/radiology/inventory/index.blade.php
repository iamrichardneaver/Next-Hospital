@extends('layouts.app')

@section('title', 'Radiology Inventory')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;"><i class="bi bi-boxes"></i> Radiology Inventory</h1>
            <p class="text-secondary mb-0">Contrast, films, consumables, and supplies — distinct from pharmacy and lab</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('radiology.inventory.movements') }}" class="btn btn-outline-secondary"><i class="bi bi-clock-history"></i> Movement History</a>
            @can('create_radiology_purchases')
            <a href="{{ route('radiology.purchases.create') }}" class="btn btn-primary"><i class="bi bi-cart-plus"></i> New Purchase Order</a>
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
        <div class="col-md-2"><div class="stat-card primary"><div class="stat-label">Total Items</div><div class="stat-value">{{ $stats['total_items'] }}</div></div></div>
        <div class="col-md-2"><div class="stat-card info"><div class="stat-label">Contrast</div><div class="stat-value">{{ $stats['contrast'] }}</div></div></div>
        <div class="col-md-2"><div class="stat-card info"><div class="stat-label">Film</div><div class="stat-value">{{ $stats['film'] }}</div></div></div>
        <div class="col-md-2"><div class="stat-card info"><div class="stat-label">Consumable</div><div class="stat-value">{{ $stats['consumable'] }}</div></div></div>
        <div class="col-md-2"><div class="stat-card info"><div class="stat-label">Supply</div><div class="stat-value">{{ $stats['supply'] }}</div></div></div>
        <div class="col-md-2"><div class="stat-card warning"><div class="stat-label">Low Stock</div><div class="stat-value">{{ $stats['low_stock'] }}</div></div></div>
    </div>

    @foreach(['contrast' => 'Contrast Agents', 'film' => 'Films', 'consumable' => 'Consumables', 'supply' => 'Supplies'] as $category => $label)
    @php $categoryItems = $itemsByCategory->get($category, collect()); @endphp
    @if($categoryItems->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header"><strong>{{ $label }}</strong></div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Catalog Stock</th>
                        @if($branchId)<th>Branch Stock</th>@endif
                        <th>Unit</th>
                        <th>Reorder</th>
                        <th>Expiry</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categoryItems as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->sku ?? '—' }}</td>
                        <td>{{ $item->current_stock }}</td>
                        @if($branchId)
                        <td>{{ $branchStock->get($item->id)?->sum('quantity') ?? 0 }}</td>
                        @endif
                        <td>{{ $item->unit }}</td>
                        <td>{{ $item->reorder_level }}</td>
                        <td>{{ $item->expiry_date?->format('M d, Y') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @endforeach

    @if($items->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-4">No radiology inventory items in catalog yet.</div></div>
    @endif
</div>
@endsection
