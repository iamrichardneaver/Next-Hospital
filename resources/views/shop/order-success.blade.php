<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - {{ config('app.name', 'Hospital') }}</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f9fafb;
            font-family: 'Inter', sans-serif;
        }
        .success-container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 2rem;
        }
        .success-card {
            background: white;
            border-radius: 12px;
            padding: 3rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success-icon {
            font-size: 4rem;
            color: #10b981;
            margin-bottom: 1rem;
        }
        .order-details {
            background: #f9fafb;
            border-radius: 8px;
            padding: 2rem;
            margin: 2rem 0;
            text-align: left;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .order-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            
            <h1 class="mb-3">Order Placed Successfully!</h1>
            <p class="text-muted mb-4">Thank you for your order. We'll process it shortly.</p>
            
            <div class="order-details">
                <h5 class="mb-3">Order Details</h5>
                
                <div class="mb-3">
                    <strong>Order Number:</strong> {{ $order->order_number ?? $order->store_order_number ?? 'ORD-' . $order->id }}
                </div>
                
                <div class="mb-3">
                    <strong>Order Date:</strong> 
                    @if($order->order_date)
                        @if(is_object($order->order_date))
                            {{ $order->order_date->format('F d, Y') }}
                        @else
                            {{ \Carbon\Carbon::parse($order->order_date)->format('F d, Y') }}
                        @endif
                    @else
                        N/A
                    @endif
                </div>
                
                <div class="mb-3">
                    <strong>Delivery Method:</strong> 
                    <span class="badge bg-primary">{{ ucfirst($order->delivery_method ?? 'pickup') }}</span>
                </div>
                
                @if(($order->delivery_method ?? 'pickup') === 'delivery' && !empty($order->delivery_address))
                <div class="mb-3">
                    <strong>Delivery Address:</strong><br>
                    {{ $order->delivery_address }}
                </div>
                @endif
                
                <div class="mb-3">
                    <strong>Payment Status:</strong> 
                    <span class="badge {{ ($order->payment_status ?? 'pending') === 'paid' ? 'bg-success' : 'bg-warning' }}">
                        {{ ucfirst($order->payment_status ?? 'pending') }}
                    </span>
                </div>
                
                <div class="mb-3">
                    <strong>Payment Method:</strong> {{ ucfirst($order->payment_method ?? 'pending') }}
                </div>
                
                <hr>
                
                <h6 class="mb-3">Order Items</h6>
                @if($order->items && $order->items->count() > 0)
                    @foreach($order->items as $item)
                    <div class="order-item">
                        <div>
                            <strong>{{ $item->storeItem->name ?? 'Item' }}</strong>
                            <div class="text-muted small">Quantity: {{ $item->quantity ?? 1 }}</div>
                        </div>
                        <div>
                            <strong>GH₵{{ number_format($item->total_price ?? 0, 2) }}</strong>
                        </div>
                    </div>
                    @endforeach
                @else
                    <p class="text-muted">No items found</p>
                @endif
                
                <hr>
                
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <strong>GH₵{{ number_format($order->subtotal ?? 0, 2) }}</strong>
                </div>
                
                @if(($order->tax_amount ?? 0) > 0)
                <div class="d-flex justify-content-between mb-2">
                    <span>Tax:</span>
                    <strong>GH₵{{ number_format($order->tax_amount ?? 0, 2) }}</strong>
                </div>
                @endif
                
                @if(($order->delivery_fee ?? 0) > 0)
                <div class="d-flex justify-content-between mb-2">
                    <span>Delivery Fee:</span>
                    <strong>GH₵{{ number_format($order->delivery_fee ?? 0, 2) }}</strong>
                </div>
                @endif
                
                <div class="d-flex justify-content-between mt-3 pt-3 border-top">
                    <strong>Total:</strong>
                    <strong class="text-primary" style="font-size: 1.25rem;">GH₵{{ number_format($order->total_amount ?? 0, 2) }}</strong>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                <a href="{{ route('shop.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Continue Shopping
                </a>
                <a href="{{ route('shop.cart') }}" class="btn btn-primary">
                    <i class="bi bi-bag"></i> View Orders
                </a>
            </div>
        </div>
    </div>
</body>
</html>
