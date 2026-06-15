@extends('layouts.app')

@section('title', 'Lab Inventory Movements')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;"><i class="bi bi-clock-history"></i> Lab Inventory Movements</h1>
            <p class="text-secondary mb-0">Audit trail of receipts, test consumption, and adjustments</p>
        </div>
        <a href="{{ route('lab.inventory.index') }}" class="btn btn-outline-secondary btn-sm">Back to Inventory</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <select name="movement_type" class="form-select">
                        <option value="">All movement types</option>
                        @foreach(['purchase_receipt','test_consumption','consumption_reversal','adjustment','waste'] as $type)
                        <option value="{{ $type }}" {{ request('movement_type') === $type ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Item</th>
                        <th>Branch</th>
                        <th class="text-end">Qty</th>
                        <th>Reference</th>
                        <th>By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at?->format('M d, Y H:i') }}</td>
                        <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $movement->movement_type) }}</span></td>
                        <td>{{ $movement->getItemName() }} <small class="text-muted">({{ $movement->item_type }})</small></td>
                        <td>{{ $movement->branch?->name }}</td>
                        <td class="text-end {{ (float)$movement->quantity < 0 ? 'text-danger' : 'text-success' }}">{{ $movement->quantity }}</td>
                        <td>
                            @if($movement->reference_type === \App\Models\LabRequest::class)
                            Lab Request #{{ $movement->reference_id }}
                            @else
                            {{ class_basename($movement->reference_type ?? '') }} {{ $movement->reference_id }}
                            @endif
                        </td>
                        <td>{{ $movement->performer?->name }}</td>
                        <td class="small">{{ $movement->notes }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No movements recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movements->hasPages())
        <div class="card-footer">{{ $movements->links() }}</div>
        @endif
    </div>
</div>
@endsection
