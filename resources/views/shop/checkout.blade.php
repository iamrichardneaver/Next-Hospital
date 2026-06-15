<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checkout - {{ config('app.name', $hospitalBranding['name'] ?? 'Hospital') }}</title>
    
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
        
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .section-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-title i {
            color: var(--primary-color);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 0.75rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.1);
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }
        
        .item-quantity {
            color: var(--text-light);
            font-size: 0.875rem;
        }
        
        .item-price {
            font-weight: 600;
            color: var(--text-dark);
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
        
        .btn-place-order {
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
        
        .btn-place-order:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
        }
        
        .payment-method-card {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1rem;
        }
        
        .payment-method-card:hover {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .payment-method-card.active {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.1);
        }
        
        .payment-method-card input[type="radio"] {
            margin-right: 0.75rem;
        }
        
        .payment-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
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
                    <a href="{{ route('shop.cart') }}" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left"></i> Back to Cart
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="checkout-container">
        <h1 class="mb-4">Checkout</h1>
        
        <form action="{{ route('shop.checkout.process') }}" method="POST" id="checkout-form">
            @csrf
            
            <div class="row">
                <!-- Left Column - Billing & Shipping -->
                <div class="col-lg-7">
                    <!-- Delivery Method -->
                    <div class="section-card">
                        <h5 class="section-title">
                            <i class="bi bi-truck"></i>
                            Delivery Method
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="payment-method-card" onclick="selectDeliveryMethod('pickup')">
                                    <div class="d-flex align-items-center">
                                        <input type="radio" name="delivery_method" value="pickup" id="delivery-pickup" required checked>
                                        <label for="delivery-pickup" class="m-0 flex-grow-1 cursor-pointer">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-shop payment-icon me-3"></i>
                                                <div>
                                                    <strong>Pickup</strong>
                                                    <div class="text-muted small">Collect from our facility</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="payment-method-card" onclick="selectDeliveryMethod('delivery')">
                                    <div class="d-flex align-items-center">
                                        <input type="radio" name="delivery_method" value="delivery" id="delivery-delivery" required>
                                        <label for="delivery-delivery" class="m-0 flex-grow-1 cursor-pointer">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-truck payment-icon me-3"></i>
                                                <div>
                                                    <strong>Delivery</strong>
                                                    <div class="text-muted small">We deliver to your address</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Delivery Information (shown only for delivery) -->
                    <div class="section-card" id="delivery-info" style="display: none;">
                        <h5 class="section-title">
                            <i class="bi bi-geo-alt"></i>
                            Delivery Information
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="full_name" value="{{ auth()->user()->name }}" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" name="phone" value="{{ auth()->user()->contact ?? auth()->user()->phone }}" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" value="{{ auth()->user()->email }}">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Delivery Address *</label>
                                <textarea class="form-control" name="delivery_address" rows="3" placeholder="Enter your full delivery address"></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City *</label>
                                <input type="text" class="form-control" name="city" placeholder="e.g., Accra">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Region *</label>
                                <select class="form-select" name="region">
                                    <option value="">Select Region</option>
                                    <option value="Greater Accra">Greater Accra</option>
                                    <option value="Ashanti">Ashanti</option>
                                    <option value="Western">Western</option>
                                    <option value="Eastern">Eastern</option>
                                    <option value="Central">Central</option>
                                    <option value="Northern">Northern</option>
                                    <option value="Upper East">Upper East</option>
                                    <option value="Upper West">Upper West</option>
                                    <option value="Volta">Volta</option>
                                    <option value="Bono">Bono</option>
                                    <option value="Ahafo">Ahafo</option>
                                    <option value="Bono East">Bono East</option>
                                    <option value="Savannah">Savannah</option>
                                    <option value="North East">North East</option>
                                    <option value="Oti">Oti</option>
                                    <option value="Western North">Western North</option>
                                </select>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Delivery Notes (Optional)</label>
                                <textarea class="form-control" name="delivery_notes" rows="2" placeholder="Any special instructions for delivery?"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="section-card">
                        <h5 class="section-title">
                            <i class="bi bi-credit-card"></i>
                            Payment Method
                        </h5>
                        
                        <div class="payment-method-card" onclick="selectPayment('cash')">
                            <div class="d-flex align-items-center">
                                <input type="radio" name="payment_method" value="cash" id="payment-cash" required>
                                <label for="payment-cash" class="m-0 flex-grow-1 cursor-pointer">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-cash-stack payment-icon me-3"></i>
                                        <div>
                                            <strong>Cash on Delivery</strong>
                                            <div class="text-muted small">Pay when you receive your order</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="payment-method-card" onclick="selectPayment('paystack')">
                            <div class="d-flex align-items-center">
                                <input type="radio" name="payment_method" value="paystack" id="payment-paystack" required>
                                <label for="payment-paystack" class="m-0 flex-grow-1 cursor-pointer">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-credit-card-2-front payment-icon me-3"></i>
                                        <div>
                                            <strong>Paystack (Card / MoMo Online)</strong>
                                            <div class="text-muted small">Pay securely online via Paystack</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Order Summary -->
                <div class="col-lg-5">
                    <div class="section-card">
                        <h5 class="section-title">
                            <i class="bi bi-bag-check"></i>
                            Order Summary
                        </h5>
                        
                        <div class="order-items">
                            @foreach($cartItems as $item)
                                @if($item->storeItem)
                                <div class="order-item">
                                    <div class="item-info">
                                        <div class="item-name">{{ $item->storeItem->name }}</div>
                                        <div class="item-quantity">Quantity: {{ $item->quantity }}</div>
                                    </div>
                                    <div class="item-price">
                                        GH₵{{ number_format($item->quantity * $item->storeItem->price, 2) }}
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        
                        <div class="mt-4">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <strong>GH₵{{ number_format($subtotal, 2) }}</strong>
                            </div>
                            
                            @if(isset($taxAmount) && $taxAmount > 0)
                            <div class="summary-row">
                                <span>Tax ({{ number_format(($taxRate ?? 0) * 100, 0) }}%)</span>
                                <strong>GH₵{{ number_format($taxAmount, 2) }}</strong>
                            </div>
                            @endif
                            
                            <div class="summary-row" id="delivery-fee-row">
                                <span>Delivery Fee</span>
                                <strong>GH₵<span id="delivery-fee-amount">{{ number_format($deliveryFee, 2) }}</span></strong>
                            </div>
                            
                            <div class="summary-row total">
                                <span>Total</span>
                                <strong>GH₵<span id="total-amount">{{ number_format($total, 2) }}</span></strong>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-place-order">
                            <i class="bi bi-lock-fill"></i> Place Order
                        </button>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                Your order information is secure
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const subtotal = {{ $subtotal }};
        const taxRate = {{ $taxRate ?? 0 }};
        const deliveryFee = {{ $deliveryFee }};
        
        // Delivery method selection
        function selectDeliveryMethod(method) {
            document.querySelectorAll('input[name="delivery_method"]').forEach(radio => {
                radio.closest('.payment-method-card').classList.remove('active');
            });
            
            const radio = document.getElementById(`delivery-${method}`);
            radio.checked = true;
            radio.closest('.payment-method-card').classList.add('active');
            
            // Show/hide delivery info
            const deliveryInfo = document.getElementById('delivery-info');
            const deliveryFeeRow = document.getElementById('delivery-fee-row');
            const deliveryFeeAmount = document.getElementById('delivery-fee-amount');
            const totalAmount = document.getElementById('total-amount');
            
            if (method === 'delivery') {
                deliveryInfo.style.display = 'block';
                deliveryFeeRow.style.display = 'flex';
                deliveryFeeAmount.textContent = deliveryFee.toFixed(2);
                
                // Make delivery fields required
                document.querySelectorAll('#delivery-info input[required], #delivery-info select[required], #delivery-info textarea[required]').forEach(field => {
                    field.setAttribute('required', 'required');
                });
            } else {
                deliveryInfo.style.display = 'none';
                deliveryFeeRow.style.display = 'none';
                deliveryFeeAmount.textContent = '0.00';
                
                // Remove required from delivery fields
                document.querySelectorAll('#delivery-info input, #delivery-info select, #delivery-info textarea').forEach(field => {
                    field.removeAttribute('required');
                });
            }
            
            // Recalculate total
            const taxAmount = subtotal * taxRate;
            const finalDeliveryFee = method === 'delivery' ? deliveryFee : 0;
            const total = subtotal + taxAmount + finalDeliveryFee;
            totalAmount.textContent = total.toFixed(2);
        }
        
        // Payment method selection
        function selectPayment(method) {
            document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
                radio.closest('.payment-method-card').classList.remove('active');
            });
            
            const radio = document.getElementById(`payment-${method}`);
            radio.checked = true;
            radio.closest('.payment-method-card').classList.add('active');
        }
        
        // Initialize delivery method on page load
        document.addEventListener('DOMContentLoaded', function() {
            const selectedMethod = document.querySelector('input[name="delivery_method"]:checked');
            if (selectedMethod) {
                selectDeliveryMethod(selectedMethod.value);
            }
        });
        
        // Form submission
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
            
            // Form will submit normally to server
        });
    </script>
</body>
</html>

