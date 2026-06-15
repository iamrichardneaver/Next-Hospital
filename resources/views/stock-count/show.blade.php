@extends('layouts.app')

@section('title', 'Stock Count #' . $stockCount->id)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Stock Count #{{ $stockCount->id }}</h1>
            <p class="text-muted mb-0">{{ ucfirst($stockCount->department) }} — {{ ucfirst($stockCount->status) }}</p>
        </div>
        <a href="{{ route('stock-count.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Items</small><h4>{{ $summary['total_items'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Counted</small><h4>{{ $summary['counted_items'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Variances</small><h4>{{ $summary['variance_items'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Net Variance</small><h4>{{ number_format($summary['total_variance'], 2) }}</h4></div></div></div>
    </div>

    @if($stockCount->status === 'draft')
    <form action="{{ route('stock-count.update-counts', $stockCount) }}" method="POST">@csrf @method('PUT')
        <div class="card mb-3"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Item</th><th>System Qty</th><th>Counted Qty</th><th>Variance</th></tr></thead><tbody>
            @foreach($stockCount->items as $index => $item)
                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                <tr>
                    <td>{{ $item->item_name }}</td>
                    <td>{{ number_format($item->system_qty, 2) }}</td>
                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][counted_qty]" class="form-control form-control-sm" value="{{ $item->counted_qty }}" style="max-width:120px"></td>
                    <td>{{ $item->variance !== null ? number_format($item->variance, 2) : '—' }}</td>
                </tr>
            @endforeach
        </tbody></table></div></div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary">Save Counts</button>
        </div>
    </form>
    <form action="{{ route('stock-count.complete', $stockCount) }}" method="POST" class="mt-3" onsubmit="return confirm('Submit and finalize this stock count?')">@csrf
        <button class="btn btn-success"><i class="bi bi-check-circle"></i> Complete Count</button>
    </form>
    @else
    <div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Item</th><th>System</th><th>Counted</th><th>Variance</th></tr></thead><tbody>
        @foreach($stockCount->items as $item)
            <tr class="{{ $item->variance != 0 ? 'table-warning' : '' }}">
                <td>{{ $item->item_name }}</td>
                <td>{{ number_format($item->system_qty, 2) }}</td>
                <td>{{ number_format($item->counted_qty, 2) }}</td>
                <td>{{ number_format($item->variance, 2) }}</td>
            </tr>
        @endforeach
    </tbody></table></div></div>
    @endif
</div>
@endsection
