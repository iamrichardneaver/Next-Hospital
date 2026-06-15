@extends('layouts.app')

@section('title', 'Store Item Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-box-seam me-2"></i>Store Item Details
        </h1>
        <div>
            <a href="{{ route('ecommerce.edit', $storeItem->id) }}" class="btn btn-primary">
                <i class="bi bi-pencil-square"></i> Edit
            </a>
            <a href="{{ route('ecommerce.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Item Details -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Item Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Name:</th>
                            <td><strong>{{ $storeItem->name }}</strong></td>
                        </tr>
                        <tr>
                            <th>SKU:</th>
                            <td>{{ $storeItem->sku ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Category:</th>
                            <td>{{ $storeItem->category ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Description:</th>
                            <td>{{ $storeItem->description ?? 'No description' }}</td>
                        </tr>
                        <tr>
                            <th>Selling Price:</th>
                            <td><strong class="text-success">GH₵ {{ number_format($storeItem->price, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <th>Cost Price:</th>
                            <td>GH₵ {{ number_format($storeItem->cost_price, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Stock Quantity:</th>
                            <td>
                                @if($storeItem->stock_quantity == 0)
                                    <span class="badge badge-danger">Out of Stock (0)</span>
                                @elseif($storeItem->stock_quantity <= $storeItem->minimum_stock)
                                    <span class="badge badge-warning">Low Stock ({{ $storeItem->stock_quantity }})</span>
                                @else
                                    <span class="badge badge-success">{{ $storeItem->stock_quantity }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Minimum Stock:</th>
                            <td>{{ $storeItem->minimum_stock }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge badge-{{ $storeItem->is_active ? 'success' : 'secondary' }}">
                                    {{ $storeItem->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Created By:</th>
                            <td>{{ $storeItem->creator->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Created Date:</th>
                            <td>{{ $storeItem->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-md-4">
            <div class="card shadow mb-4 border-left-primary">
                <div class="card-body">
                    <h6 class="font-weight-bold text-primary">Quick Stats</h6>
                    <hr>
                    <p class="mb-2">
                        <strong>Total Orders:</strong> {{ $orderHistory->count() }}
                    </p>
                    <p class="mb-2">
                        <strong>Total Quantity Sold:</strong> {{ $orderHistory->sum('quantity') }}
                    </p>
                    <p class="mb-0">
                        <strong>Stock Value:</strong> GH₵ {{ number_format($storeItem->stock_quantity * $storeItem->cost_price, 2) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Order History -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Order History (Last 20)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Patient</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orderHistory as $item)
                        @if($item->order)
                        <tr>
                            <td>
                                <a href="{{ route('ecommerce.orders.show', $item->order->id) }}">
                                    {{ $item->order->order_number ?? 'N/A' }}
                                </a>
                            </td>
                            <td>
                                @if($item->order->patient)
                                    {{ $item->order->patient->first_name }} {{ $item->order->patient->last_name }}
                                @else
                                    <span class="text-muted">Unknown Customer</span>
                                @endif
                            </td>
                            <td>{{ $item->quantity }}</td>
                            <td>GH₵ {{ number_format($item->unit_price, 2) }}</td>
                            <td>GH₵ {{ number_format($item->total_price, 2) }}</td>
                            <td>{{ $item->created_at->format('M d, Y') }}</td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No order history available</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
