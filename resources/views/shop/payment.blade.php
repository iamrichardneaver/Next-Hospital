<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payment - {{ config('app.name', 'Hospital') }}</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f9fafb;
            font-family: 'Inter', sans-serif;
        }
        .payment-container {
            max-width: 600px;
            margin: 3rem auto;
            padding: 2rem;
        }
        .payment-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .order-summary {
            background: #f9fafb;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .btn-pay {
            width: 100%;
            padding: 1rem;
            font-size: 1.125rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <h2 class="mb-4">
                <i class="bi bi-credit-card"></i>
                Complete Payment
            </h2>
            
            <div class="order-summary">
                <h5 class="mb-3">Order Summary</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span>Order Number:</span>
                    <strong>{{ $order->order_number ?? $order->store_order_number ?? 'ORD-' . $order->id }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Amount:</span>
                    <strong class="text-primary">GH₵{{ number_format($order->total_amount ?? 0, 2) }}</strong>
                </div>
            </div>
            
            <form id="payment-form">
                <div class="mb-3">
                    <label class="form-label">Email Address *</label>
                    <input type="email" class="form-control" id="email" value="{{ auth()->user()->email }}" required>
                </div>
                
                        <button type="submit" class="btn btn-primary btn-pay" id="pay-button">
                            <i class="bi bi-lock-fill"></i>
                            Pay GH₵{{ number_format($order->total_amount ?? 0, 2) }}
                        </button>
            </form>
            
            <div class="text-center mt-3">
                <small class="text-muted">
                    <i class="bi bi-shield-check"></i>
                    Secure payment powered by Paystack
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script>
        const paystackPublicKey = '{{ $paystackPublicKey ?? '' }}';
        const orderId = {{ $order->id ?? 0 }};
        const amount = {{ (float)($order->total_amount ?? 0) * 100 }}; // Amount in pesewas
        
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const payButton = document.getElementById('pay-button');
            
            if (!email) {
                alert('Please enter your email address');
                return;
            }
            
            if (!paystackPublicKey || paystackPublicKey.trim() === '') {
                alert('Payment gateway not configured. Please contact support.');
                return;
            }
            
            // Generate unique reference
            const reference = 'ORD_' + orderId + '_' + Date.now();
            
            payButton.disabled = true;
                        payButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
            
            if (amount <= 0) {
                alert('Invalid order amount. Please contact support.');
                payButton.disabled = false;
                payButton.innerHTML = '<i class="bi bi-lock-fill"></i> Pay GH₵{{ number_format($order->total_amount ?? 0, 2) }}';
                return;
            }
            
            const handler = PaystackPop.setup({
                key: paystackPublicKey,
                email: email,
                amount: amount,
                currency: 'GHS',
                ref: reference,
                metadata: {
                    custom_fields: [
                        {
                            display_name: "Order ID",
                            variable_name: "order_id",
                            value: orderId
                        }
                    ]
                },
                callback: function(response) {
                    // Verify payment on server
                    fetch(`/shop/payment/${orderId}/process`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            reference: response.reference,
                            email: email
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = data.redirect_url;
                        } else {
                            alert('Payment verification failed: ' + data.message);
                            payButton.disabled = false;
                            payButton.innerHTML = '<i class="bi bi-lock-fill"></i> Pay GH₵{{ number_format($order->total_amount, 2) }}';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                        payButton.disabled = false;
                        payButton.innerHTML = '<i class="bi bi-lock-fill"></i> Pay GH₵{{ number_format($order->total_amount ?? 0, 2) }}';
                    });
                },
            onClose: function() {
                payButton.disabled = false;
                payButton.innerHTML = '<i class="bi bi-lock-fill"></i> Pay GH₵{{ number_format($order->total_amount ?? 0, 2) }}';
            }
            });
            
            handler.openIframe();
        });
    </script>
</body>
</html>
