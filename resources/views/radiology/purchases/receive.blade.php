@extends('layouts.app')

@section('title', 'Receive Radiology Goods')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3" style="color: #1e3a5f;"><i class="bi bi-box-arrow-in-down"></i> Receive Radiology Goods — {{ $order->po_number }}</h1>
        <p class="text-secondary">Received quantities will be added to radiology inventory at {{ $order->branch?->name }}</p>
    </div>

    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <form method="POST" action="{{ route('radiology.purchases.receive.store', $order) }}">
        @csrf
        <div class="card mb-4">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Ordered</th>
                            <th>Already Received</th>
                            <th>Receive Qty</th>
                            <th>Batch</th>
                            <th>Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        @if($item->remainingQuantity() > 0)
                        <tr>
                            <td>{{ $item->getItemName() }}</td>
                            <td>{{ ucfirst($item->inventoryItem?->category ?? '') }}</td>
                            <td>{{ $item->quantity_ordered }}</td>
                            <td>{{ $item->quantity_received }}</td>
                            <td style="width:120px">
                                <input type="number" step="0.01" name="receipts[{{ $item->id }}][quantity]" class="form-control" min="0" max="{{ $item->remainingQuantity() }}" value="{{ $item->remainingQuantity() }}">
                            </td>
                            <td><input type="text" name="receipts[{{ $item->id }}][batch_number]" class="form-control" value="{{ $item->batch_number }}"></td>
                            <td><input type="date" name="receipts[{{ $item->id }}][expiry_date]" class="form-control" value="{{ $item->expiry_date?->format('Y-m-d') }}"></td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <button type="submit" class="btn btn-success">Confirm Receipt</button>
        <a href="{{ route('radiology.purchases.show', $order) }}" class="btn btn-link">Cancel</a>
    </form>
</div>
@endsection
