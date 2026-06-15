@extends('layouts.app')

@section('title', 'Lab PO ' . $order->po_number)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">{{ $order->po_number }}</h1>
            <span class="badge {{ $order->getStatusBadgeClass() }}">{{ $order->getStatusLabel() }}</span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('lab.purchases.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
            @can('receive_lab_purchases')
            @if($order->canReceive())
            <a href="{{ route('lab.purchases.receive', $order) }}" class="btn btn-success btn-sm"><i class="bi bi-box-arrow-in-down"></i> Receive Goods</a>
            @endif
            @endcan
            @can('create_lab_purchases')
            @if($order->canMarkOrdered())
            <form method="POST" action="{{ route('lab.purchases.order', $order) }}" class="d-inline">@csrf<button class="btn btn-primary btn-sm">Mark Ordered</button></form>
            @endif
            @if($order->canCancel())
            <form method="POST" action="{{ route('lab.purchases.cancel', $order) }}" class="d-inline" onsubmit="return confirm('Cancel this PO?')">@csrf<button class="btn btn-outline-danger btn-sm">Cancel</button></form>
            @endif
            @endcan
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card mb-4">
                <div class="card-header"><strong>Order Details</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Supplier</dt><dd class="col-sm-8">{{ $order->supplier?->name }}</dd>
                        <dt class="col-sm-4">Branch</dt><dd class="col-sm-8">{{ $order->branch?->name }}</dd>
                        <dt class="col-sm-4">Total</dt><dd class="col-sm-8 fw-bold">GH₵{{ number_format($order->total_amount, 2) }}</dd>
                        <dt class="col-sm-4">Notes</dt><dd class="col-sm-8">{{ $order->notes ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><strong>Line Items</strong></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr><th>Type</th><th>Item</th><th>Ordered</th><th>Received</th><th>Unit Cost</th><th>Batch</th><th>Expiry</th></tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td><span class="badge bg-info text-dark">{{ ucfirst($item->item_type) }}</span></td>
                                <td>{{ $item->getItemName() }}</td>
                                <td>{{ $item->quantity_ordered }}</td>
                                <td>{{ $item->quantity_received }}</td>
                                <td>GH₵{{ number_format($item->unit_cost, 2) }}</td>
                                <td>{{ $item->batch_number ?? '—' }}</td>
                                <td>{{ $item->expiry_date?->format('M d, Y') ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
