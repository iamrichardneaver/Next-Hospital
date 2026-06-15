@extends('layouts.app')

@section('title', 'Stock Counts')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-clipboard-check me-2"></i>Stock Counts</h1>
        @can('manage_pharmacy_inventory')
        <a href="{{ route('stock-count.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Start Count</a>
        @endcan
    </div>

    <div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>ID</th><th>Department</th><th>Status</th><th>Counted By</th><th>Counted At</th><th></th></tr></thead><tbody>
        @forelse($counts as $count)
            <tr>
                <td>#{{ $count->id }}</td>
                <td>{{ ucfirst($count->department) }}</td>
                <td><span class="badge bg-{{ $count->status === 'completed' ? 'success' : 'warning' }}">{{ ucfirst($count->status) }}</span></td>
                <td>{{ $count->counter?->name ?? '—' }}</td>
                <td>{{ optional($count->counted_at)->format('Y-m-d H:i') ?? '—' }}</td>
                <td><a href="{{ route('stock-count.show', $count) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No stock counts yet.</td></tr>
        @endforelse
    </tbody></table></div>@if($counts->hasPages())<div class="card-footer">{{ $counts->links() }}</div>@endif</div>
</div>
@endsection
