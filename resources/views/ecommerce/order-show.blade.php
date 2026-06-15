@extends('layouts.app')

@section('title', 'Order Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-shopping-cart me-2"></i>Order Details
        </h1>
        <a href="{{ route('ecommerce.orders') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
    </div>

    <div class="row">
        <!-- Order Information -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Order Information</h6>
                    <span class="badge badge-{{
                        $order->status === 'delivered' ? 'success' :
                        ($order->status === 'cancelled' ? 'danger' :
                        ($order->status === 'processing' || $order->status === 'shipped' ? 'info' : 'warning'))
                    }} badge-lg">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
                            <p><strong>Patient:</strong> {{ $order->patient->first_name }} {{ $order->patient->last_name }}</p>
                            <p><strong>Branch:</strong> {{ $order->branch->name ?? 'N/A' }}</p>
                            @if($order->order_source)
                            <p><strong>Order Source:</strong> 
                                <span class="badge bg-{{ $order->order_source === 'mobile_app' ? 'info' : 'secondary' }}">
                                    <i class="bi bi-{{ $order->order_source === 'mobile_app' ? 'phone' : 'globe' }}"></i>
                                    {{ $order->order_source === 'mobile_app' ? 'Mobile App' : 'Web' }}
                                </span>
                            </p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <p><strong>Order Date:</strong> {{ $order->created_at->format('M d, Y H:i') }}</p>
                            <p><strong>Payment Method:</strong> {{ ucfirst($order->payment_method) }}</p>
                            <p><strong>Delivery Method:</strong> {{ ucfirst($order->delivery_method) }}</p>
                        </div>
                    </div>

                    @if($order->delivery_method === 'delivery')
                    <div class="alert alert-info">
                        <h6><i class="fas fa-truck"></i> Delivery Information</h6>
                        <p class="mb-1"><strong>Address:</strong> {{ $order->delivery_address }}</p>
                        <p class="mb-0"><strong>Phone:</strong> {{ $order->delivery_phone }}</p>
                        @if($order->delivery_notes)
                            <p class="mb-0"><strong>Notes:</strong> {{ $order->delivery_notes }}</p>
                        @endif
                    </div>
                    @endif

                    <!-- Order Items -->
                    <h6 class="font-weight-bold mt-4">Order Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $item->storeItem->name ?? 'N/A' }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>GH₵ {{ number_format($item->unit_price, 2) }}</td>
                                    <td>GH₵ {{ number_format($item->total_price, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-right">Subtotal:</th>
                                    <th>GH₵ {{ number_format($order->subtotal, 2) }}</th>
                                </tr>
                                @if($order->tax_amount > 0)
                                <tr>
                                    <th colspan="3" class="text-right">Tax:</th>
                                    <th>GH₵ {{ number_format($order->tax_amount, 2) }}</th>
                                </tr>
                                @endif
                                @if($order->delivery_fee > 0)
                                <tr>
                                    <th colspan="3" class="text-right">Delivery Fee:</th>
                                    <th>GH₵ {{ number_format($order->delivery_fee, 2) }}</th>
                                </tr>
                                @endif
                                <tr class="table-primary">
                                    <th colspan="3" class="text-right">Total Amount:</th>
                                    <th>GH₵ {{ number_format($order->total_amount, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Delivery Tracking (if applicable) -->
            @if($order->delivery_method === 'delivery' && $order->delivery)
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Delivery Tracking</h6>
                </div>
                <div class="card-body">
                    <p><strong>Status:</strong> 
                        <span class="badge badge-{{
                            $order->delivery->status === 'delivered' ? 'success' :
                            ($order->delivery->status === 'failed' ? 'danger' : 'info')
                        }}">
                            {{ ucfirst(str_replace('_', ' ', $order->delivery->status)) }}
                        </span>
                    </p>
                    @if($order->delivery->rider)
                        <p><strong>Rider:</strong> {{ $order->delivery->rider->user->name }}</p>
                        <p><strong>Rider Phone:</strong> {{ $order->delivery->rider->phone }}</p>
                    @else
                        <p class="text-muted">No rider assigned yet</p>
                    @endif
                    @if($order->delivery->estimated_delivery)
                        <p><strong>Estimated Delivery:</strong> {{ $order->delivery->estimated_delivery->format('M d, Y H:i') }}</p>
                    @endif
                    @if($order->delivery->delivered_at)
                        <p><strong>Delivered At:</strong> {{ $order->delivery->delivered_at->format('M d, Y H:i') }}</p>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Actions -->
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Actions</h6>
                </div>
                <div class="card-body">
                    @if($order->status !== 'delivered' && $order->status !== 'cancelled')
                        <form method="POST" action="{{ route('ecommerce.orders.status', $order->id) }}" class="mb-2">
                            @csrf
                            <input type="hidden" name="status" value="confirmed">
                            @if($order->status === 'pending')
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-check"></i> Confirm Order
                                </button>
                            @endif
                        </form>

                        <form method="POST" action="{{ route('ecommerce.orders.status', $order->id) }}" class="mb-2">
                            @csrf
                            <input type="hidden" name="status" value="processing">
                            @if($order->status === 'confirmed')
                                <button type="submit" class="btn btn-info btn-block">
                                    <i class="fas fa-spinner"></i> Start Processing
                                </button>
                            @endif
                        </form>

                        <form method="POST" action="{{ route('ecommerce.orders.status', $order->id) }}" class="mb-2">
                            @csrf
                            <input type="hidden" name="status" value="ready">
                            @if($order->status === 'processing')
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-box"></i> Mark as Ready
                                </button>
                            @endif
                        </form>

                        <hr>

                        <form method="POST" action="{{ route('ecommerce.orders.status', $order->id) }}" 
                              onsubmit="return confirm('Are you sure you want to cancel this order?')">
                            @csrf
                            <input type="hidden" name="status" value="cancelled">
                            <button type="submit" class="btn btn-danger btn-block">
                                <i class="fas fa-times"></i> Cancel Order
                            </button>
                        </form>
                    @endif
                    
                    <hr>
                    
                    <a href="{{ route('ecommerce.orders') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-arrow-left"></i> Back to Orders
                    </a>
                </div>
            </div>

            @if($order->delivery_method === 'delivery' && !$order->delivery)
            <div class="card shadow mt-3">
                <div class="card-body">
                    <p class="text-warning">
                        <i class="fas fa-exclamation-triangle"></i> No delivery record found for this order.
                    </p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
