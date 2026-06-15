<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Shopping Cart - {{ config('app.name', $hospitalBranding['name'] ?? 'Hospital') }}</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f9fafb;
            --border-color: #e5e7eb;
        }
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background-color: var(--bg-light);
        }
        
        .shop-navbar {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .shop-navbar .brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .cart-header {
            margin-bottom: 2rem;
        }
        
        .cart-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .cart-item {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .item-image {
            width: 100px;
            height: 100px;
            background: var(--bg-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-image i {
            font-size: 2.5rem;
            color: var(--text-light);
        }
        
        .item-details h5 {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .item-category {
            color: var(--primary-color);
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .item-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quantity-control button {
            width: 32px;
            height: 32px;
            border: 1px solid var(--border-color);
            background: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .quantity-control button:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .quantity-control input {
            width: 60px;
            text-align: center;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.25rem;
        }
        
        .btn-remove {
            color: #ef4444;
            border: none;
            background: none;
            padding: 0.5rem;
            transition: all 0.3s;
        }
        
        .btn-remove:hover {
            color: #dc2626;
        }
        
        .cart-summary {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: sticky;
            top: 2rem;
        }
        
        .cart-summary h4 {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .summary-row:last-child {
            border-bottom: none;
            padding-top: 1rem;
            margin-top: 1rem;
            border-top: 2px solid var(--border-color);
        }
        
        .summary-row.total {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .btn-checkout {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.125rem;
            margin-top: 1.5rem;
            transition: all 0.3s;
        }
        
        .btn-checkout:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
        }
        
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
        }
        
        .empty-cart i {
            font-size: 5rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }
        
        .empty-cart h3 {
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .empty-cart p {
            color: var(--text-light);
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="shop-navbar">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('shop.index') }}" class="brand">
                    <i class="bi bi-shop"></i>
                    {{ config('app.name', $hospitalBranding['name'] ?? 'Hospital') }} Shop
                </a>
                
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('shop.index') }}" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left"></i> Continue Shopping
                    </a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                            <i class="bi bi-grid"></i> Dashboard
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <div class="cart-container">
        @if($cartItems->isEmpty())
            <!-- Empty Cart -->
            <div class="empty-cart">
                <i class="bi bi-cart-x"></i>
                <h3>Your cart is empty</h3>
                <p>Add some products to your cart to get started</p>
                <a href="{{ route('shop.index') }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-shop"></i> Start Shopping
                </a>
            </div>
        @else
            <div class="row">
                <!-- Cart Items -->
                <div class="col-lg-8">
                    <div class="cart-header">
                        <h1>Shopping Cart</h1>
                        <p class="text-muted">{{ $cartItems->count() }} item{{ $cartItems->count() > 1 ? 's' : '' }} in your cart</p>
                    </div>
                    
                    @foreach($cartItems as $item)
                        <div class="cart-item" data-item-id="{{ $item->id }}">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <div class="item-image">
                                        @if($item->storeItem->image_url)
                                            <img src="{{ $item->storeItem->image_url }}" alt="{{ $item->storeItem->name }}">
                                        @else
                                            <i class="bi bi-capsule"></i>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="item-details">
                                        @if($item->storeItem->category)
                                            <div class="item-category">{{ $item->storeItem->category }}</div>
                                        @endif
                                        <h5>{{ $item->storeItem->name }}</h5>
                                        <small class="text-muted">Stock: {{ $item->storeItem->stock_quantity }} available</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <div class="item-price">
                                        GH₵{{ number_format($item->storeItem->price, 2) }}
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <div class="quantity-control">
                                        <button type="button" class="btn-quantity" data-action="decrease" data-item-id="{{ $item->id }}">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <input type="number" value="{{ $item->quantity }}" min="1" max="{{ $item->storeItem->stock_quantity }}" 
                                               class="form-control quantity-input" data-item-id="{{ $item->id }}" readonly>
                                        <button type="button" class="btn-quantity" data-action="increase" data-item-id="{{ $item->id }}">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-2 text-end">
                                    <div class="item-total" data-item-id="{{ $item->id }}">
                                        <strong>GH₵<span class="item-total-amount">{{ number_format($item->quantity * $item->storeItem->price, 2) }}</span></strong>
                                    </div>
                                    <button type="button" class="btn-remove" data-item-id="{{ $item->id }}">
                                        <i class="bi bi-trash"></i> Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Cart Summary -->
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h4>Order Summary</h4>
                        
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <strong>GH₵<span id="subtotal-amount">{{ number_format($subtotal, 2) }}</span></strong>
                        </div>
                        
                        <div class="summary-row">
                            <span>Delivery Fee</span>
                            <strong>GH₵<span id="delivery-amount">{{ number_format($deliveryFee, 2) }}</span></strong>
                        </div>
                        
                        <div class="summary-row total">
                            <span>Total</span>
                            <strong>GH₵<span id="total-amount">{{ number_format($total, 2) }}</span></strong>
                        </div>
                        
                        <a href="{{ route('shop.checkout') }}" class="btn-checkout">
                            <i class="bi bi-lock-fill"></i> Proceed to Checkout
                        </a>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="bi bi-shield-check"></i> Secure payment processing
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Cart Management Script -->
    <script>
        // Update quantity
        document.querySelectorAll('.btn-quantity').forEach(btn => {
            btn.addEventListener('click', function() {
                const itemId = this.dataset.itemId;
                const action = this.dataset.action;
                const input = document.querySelector(`.quantity-input[data-item-id="${itemId}"]`);
                let quantity = parseInt(input.value);
                const maxStock = parseInt(input.max);
                
                if (action === 'increase' && quantity < maxStock) {
                    quantity++;
                } else if (action === 'decrease' && quantity > 1) {
                    quantity--;
                }
                
                updateCartItem(itemId, quantity);
            });
        });
        
        // Remove item
        document.querySelectorAll('.btn-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Remove this item from cart?')) {
                    const itemId = this.dataset.itemId;
                    removeCartItem(itemId);
                }
            });
        });
        
        // Update cart item
        function updateCartItem(itemId, quantity) {
            fetch(`/shop/cart/${itemId}/update`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ quantity: quantity })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update quantity input
                    document.querySelector(`.quantity-input[data-item-id="${itemId}"]`).value = quantity;
                    
                    // Update item total
                    document.querySelector(`.item-total[data-item-id="${itemId}"] .item-total-amount`).textContent = data.itemTotal;
                    
                    // Update summary
                    document.getElementById('subtotal-amount').textContent = data.subtotal;
                    document.getElementById('total-amount').textContent = data.total;
                    
                    showToast('success', 'Cart updated');
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Failed to update cart');
            });
        }
        
        // Remove cart item
        function removeCartItem(itemId) {
            fetch(`/shop/cart/${itemId}/remove`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove item from DOM
                    document.querySelector(`.cart-item[data-item-id="${itemId}"]`).remove();
                    
                    // Update summary
                    document.getElementById('subtotal-amount').textContent = data.subtotal;
                    document.getElementById('total-amount').textContent = data.total;
                    
                    // If cart is empty, reload page
                    if (data.cartCount === 0) {
                        location.reload();
                    }
                    
                    showToast('success', 'Item removed from cart');
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Failed to remove item');
            });
        }
        
        // Toast notification
        function showToast(type, message) {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed bottom-0 end-0 m-3`;
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
</body>
</html>

