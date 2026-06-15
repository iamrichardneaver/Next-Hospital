@extends('layouts.app')

@section('title', 'Lab Supplies Purchases')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;"><i class="bi bi-flask"></i> Lab Supplies Purchases</h1>
            <p class="text-secondary mb-0">Purchase reagents and consumables — separate from pharmacy inventory</p>
        </div>
        @can('create_lab_purchases')
        <a href="{{ route('lab.purchases.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Purchase Order
        </a>
        @endcan
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="PO number or supplier..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach(['draft','ordered','partially_received','received','cancelled'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </div>
                @if(auth()->user()->hasRole('super_admin'))
                <div class="col-md-3">
                    <select name="branch_id" class="form-select">
                        <option value="">All branches</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string)$branchId === (string)$branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
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
                        <th>PO Number</th>
                        <th>Supplier</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th class="text-end">Total</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td><strong>{{ $order->po_number }}</strong></td>
                        <td>{{ $order->supplier?->name }}</td>
                        <td>{{ $order->branch?->name }}</td>
                        <td><span class="badge {{ $order->getStatusBadgeClass() }}">{{ $order->getStatusLabel() }}</span></td>
                        <td class="text-end">GH₵{{ number_format($order->total_amount, 2) }}</td>
                        <td>{{ $order->created_at?->format('M d, Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('lab.purchases.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No lab purchase orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
        <div class="card-footer">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
