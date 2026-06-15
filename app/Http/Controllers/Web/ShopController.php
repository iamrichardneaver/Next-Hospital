<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\StoreItem;
use App\Models\Drug;
use App\Models\PatientCart;
use App\Models\StoreOrder;
use App\Models\OrderItem;
use App\Models\Delivery;
use App\Models\Patient;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use App\Services\StoreInventoryService;

class ShopController extends Controller
{
    protected StoreInventoryService $inventoryService;

    public function __construct(StoreInventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get patient ID from authenticated user
     */
    protected function getPatientId()
    {
        $user = auth()->user();
        
        if (!$user) {
            return null;
        }
        
        // If user has patient relationship, use patient ID
        if ($user->patient) {
            return $user->patient->id;
        }
        
        // Otherwise, try to find patient by user_id
        $patient = Patient::where('user_id', $user->id)->first();
        if ($patient) {
            return $patient->id;
        }
        
        return null;
    }
    
    /**
     * Get branch ID for current user
     */
    protected function getBranchId()
    {
        $user = auth()->user();
        
        if (!$user) {
            $defaultBranch = Branch::first();
            return $defaultBranch ? $defaultBranch->id : 1;
        }
        
        // Try to get branch from patient
        $patient = $user->patient ?? Patient::where('user_id', $user->id)->first();
        if ($patient && $patient->branch_id) {
            return $patient->branch_id;
        }
        
        // Try to get branch from user's branches
        if (method_exists($user, 'branches')) {
            $branch = $user->branches()->first();
            if ($branch) {
                return $branch->id;
            }
        }
        
        // Default to first branch
        $defaultBranch = Branch::first();
        return $defaultBranch ? $defaultBranch->id : 1;
    }
    /**
     * Display the public shop storefront
     */
    public function index(Request $request)
    {
        // Get featured/slider products (top 5 products)
        $featuredProducts = StoreItem::with(['drug'])
            ->where('is_active', true)
            ->where('is_available', true)
            ->where('stock_quantity', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Get all products with filters
        $query = StoreItem::with(['drug'])
            ->where('is_active', true)
            ->where('is_available', true)
            ->where('stock_quantity', '>', 0);
        
        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        
        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('drug', function($drugQuery) use ($search) {
                      $drugQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('generic_name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Price range filter
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        
        // Sort
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'latest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
        
        $products = $query->paginate(12);
        
        // Get all categories
        $categories = StoreItem::select('category')
            ->where('is_active', true)
            ->where('is_available', true)
            ->distinct()
            ->whereNotNull('category')
            ->pluck('category');
        
        // Get cart count for current user
        $cartCount = 0;
        if (auth()->check()) {
            $patientId = $this->getPatientId();
            if ($patientId) {
                $cartCount = PatientCart::where('patient_id', $patientId)
                    ->sum('quantity');
            }
        }
        
        return view('shop.index', compact('featuredProducts', 'products', 'categories', 'cartCount'));
    }
    
    /**
     * Display single product details
     */
    public function show($id)
    {
        $product = StoreItem::with(['drug'])->findOrFail($id);
        
        // Get related products (same category)
        $relatedProducts = StoreItem::with(['drug'])
            ->where('is_active', true)
            ->where('is_available', true)
            ->where('category', $product->category)
            ->where('id', '!=', $product->id)
            ->where('stock_quantity', '>', 0)
            ->limit(4)
            ->get();
        
        // Get cart count for current user
        $cartCount = 0;
        if (auth()->check()) {
            $patientId = $this->getPatientId();
            if ($patientId) {
                $cartCount = PatientCart::where('patient_id', $patientId)
                    ->sum('quantity');
            }
        }
        
        return view('shop.show', compact('product', 'relatedProducts', 'cartCount'));
    }
    
    /**
     * View shopping cart
     */
    public function cart()
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to view your cart.');
        }
        
        $patientId = $this->getPatientId();
        if (!$patientId) {
            return redirect()->route('shop.index')->with('error', 'Patient account not found. Please contact support.');
        }
        
        $cartItems = PatientCart::with(['storeItem.drug'])
            ->where('patient_id', $patientId)
            ->get();
        
        $subtotal = $cartItems->sum(function($item) {
            if ($item->storeItem) {
                return $item->quantity * $item->storeItem->price;
            }
            return 0;
        });
        
        // Get tax rate and delivery fee from system settings
        $systemSettings = \App\Models\SystemSetting::current();
        $taxRate = (float) ($systemSettings->tax_rate ?? 0.15);
        $deliveryFee = (float) ($systemSettings->delivery_fee ?? 10.00);
        $taxAmount = $subtotal * $taxRate;
        $total = $subtotal + $taxAmount + $deliveryFee;
        
        return view('shop.cart', compact('cartItems', 'subtotal', 'taxAmount', 'taxRate', 'deliveryFee', 'total'));
    }
    
    /**
     * Add item to cart (AJAX)
     */
    public function addToCart(Request $request)
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to add items to cart'
            ], 401);
        }
        
        $patientId = $this->getPatientId();
        if (!$patientId) {
            return response()->json([
                'success' => false,
                'message' => 'Patient account not found. Please contact support.'
            ], 404);
        }
        
        $request->validate([
            'store_item_id' => 'required|exists:store_items,id',
            'quantity' => 'required|integer|min:1'
        ]);
        
        $storeItem = StoreItem::findOrFail($request->store_item_id);
        $branchId = $this->getBranchId();
        $available = $this->inventoryService->getAvailableQuantity($storeItem, $branchId);

        if ($available < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock available'
            ], 400);
        }
        
        // Check if item already in cart
        $cartItem = PatientCart::where('patient_id', $patientId)
            ->where('store_item_id', $request->store_item_id)
            ->where('item_type', 'store_item')
            ->first();
        
        if ($cartItem) {
            // Update quantity
            $newQuantity = $cartItem->quantity + $request->quantity;
            
            if ($available < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot add more. Maximum available: ' . $available
                ], 400);
            }
            
            $cartItem->quantity = $newQuantity;
            $cartItem->save();
        } else {
            // Create new cart item
            PatientCart::create([
                'patient_id' => $patientId,
                'store_item_id' => $request->store_item_id,
                'item_type' => 'store_item',
                'quantity' => $request->quantity
            ]);
        }
        
        // Get updated cart count
        $cartCount = PatientCart::where('patient_id', $patientId)
            ->sum('quantity');
        
        return response()->json([
            'success' => true,
            'message' => 'Item added to cart successfully',
            'cartCount' => $cartCount
        ]);
    }
    
    /**
     * Update cart item quantity (AJAX)
     */
    public function updateCartItem(Request $request, $id)
    {
        try {
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            $patientId = $this->getPatientId();
            if (!$patientId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient account not found'
                ], 404);
            }
            
            $request->validate([
                'quantity' => 'required|integer|min:1'
            ]);
            
            $cartItem = PatientCart::where('id', $id)
                ->where('patient_id', $patientId)
                ->firstOrFail();
            
            if ($cartItem->storeItem) {
                $available = $this->inventoryService->getAvailableQuantity(
                    $cartItem->storeItem,
                    $this->getBranchId()
                );
                if ($available < $request->quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock available'
                    ], 400);
                }
            }
            
            $cartItem->quantity = $request->quantity;
            $cartItem->save();
            
            // Recalculate totals
            $cartItems = PatientCart::with(['storeItem'])
                ->where('patient_id', $patientId)
                ->get();
            
            $subtotal = $cartItems->sum(function($item) {
                if ($item->storeItem) {
                    return $item->quantity * $item->storeItem->price;
                }
                return 0;
            });
            
            $systemSettings = \App\Models\SystemSetting::current();
            $taxRate = (float) ($systemSettings->tax_rate ?? 0.15);
            $deliveryFee = (float) ($systemSettings->delivery_fee ?? 10.00);
            $taxAmount = $subtotal * $taxRate;
            $total = $subtotal + $taxAmount + $deliveryFee;
            
            return response()->json([
                'success' => true,
                'message' => 'Cart updated successfully',
                'subtotal' => number_format($subtotal, 2),
                'taxAmount' => number_format($taxAmount, 2),
                'total' => number_format($total, 2),
                'itemTotal' => $cartItem->storeItem ? number_format($cartItem->quantity * $cartItem->storeItem->price, 2) : '0.00'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating cart item: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'cart_item_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update cart item. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Remove item from cart (AJAX)
     */
    public function removeFromCart($id)
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        $patientId = $this->getPatientId();
        if (!$patientId) {
            return response()->json([
                'success' => false,
                'message' => 'Patient account not found'
            ], 404);
        }
        
        $cartItem = PatientCart::where('id', $id)
            ->where('patient_id', $patientId)
            ->firstOrFail();
        
        $cartItem->delete();
        
        // Get updated cart count
        $cartCount = PatientCart::where('patient_id', $patientId)
            ->sum('quantity');
        
        // Recalculate totals
        $cartItems = PatientCart::with(['storeItem'])
            ->where('patient_id', $patientId)
            ->get();
        
        $subtotal = $cartItems->sum(function($item) {
            if ($item->storeItem) {
                return $item->quantity * $item->storeItem->price;
            }
            return 0;
        });
        
        $systemSettings = \App\Models\SystemSetting::current();
        $taxRate = (float) ($systemSettings->tax_rate ?? 0.15);
        $deliveryFee = (float) ($systemSettings->delivery_fee ?? 10.00);
        $taxAmount = $subtotal * $taxRate;
        $total = $subtotal + $taxAmount + $deliveryFee;
        
        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
            'cartCount' => $cartCount,
            'subtotal' => number_format($subtotal, 2),
            'total' => number_format($total, 2)
        ]);
    }
    
    /**
     * Checkout page
     */
    public function checkout()
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to checkout.');
        }
        
        $patientId = $this->getPatientId();
        if (!$patientId) {
            return redirect()->route('shop.index')->with('error', 'Patient account not found. Please contact support.');
        }
        
        $cartItems = PatientCart::with(['storeItem.drug'])
            ->where('patient_id', $patientId)
            ->get();
        
        if ($cartItems->isEmpty()) {
            return redirect()->route('shop.index')->with('error', 'Your cart is empty.');
        }
        
        $subtotal = $cartItems->sum(function($item) {
            if ($item->storeItem) {
                return $item->quantity * $item->storeItem->price;
            }
            return 0;
        });
        
        // Get tax rate and delivery fee from system settings
        $systemSettings = \App\Models\SystemSetting::current();
        $taxRate = (float) ($systemSettings->tax_rate ?? 0.15);
        $deliveryFee = (float) ($systemSettings->delivery_fee ?? 10.00);
        $taxAmount = $subtotal * $taxRate;
        $total = $subtotal + $taxAmount + $deliveryFee;
        
        // Get Paystack public key for card payments
        $paystackService = new \App\Services\PaystackService();
        $paystackPublicKey = $paystackService->getPublicKey();
        
        return view('shop.checkout', compact('cartItems', 'subtotal', 'taxAmount', 'taxRate', 'deliveryFee', 'total', 'paystackPublicKey'));
    }
    
    /**
     * Process checkout and create order
     */
    public function processCheckout(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to checkout.');
        }
        
        $patientId = $this->getPatientId();
        if (!$patientId) {
            return back()->with('error', 'Patient account not found. Please contact support.')->withInput();
        }
        
        $branchId = $this->getBranchId();
        
        // Validate request
        $validator = Validator::make($request->all(), [
            'delivery_method' => 'required|in:pickup,delivery',
            'delivery_address' => 'required_if:delivery_method,delivery|nullable|string|max:500',
            'delivery_phone' => 'required_if:delivery_method,delivery|nullable|string|max:20',
            'city' => 'required_if:delivery_method,delivery|nullable|string|max:100',
            'region' => 'required_if:delivery_method,delivery|nullable|string|max:100',
            'delivery_notes' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:cash,paystack',
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        // Get cart items
        $cartItems = PatientCart::with(['storeItem'])
            ->where('patient_id', $patientId)
            ->get();
        
        if ($cartItems->isEmpty()) {
            return redirect()->route('shop.index')->with('error', 'Your cart is empty.');
        }
        
        DB::beginTransaction();
        
        try {
            // Calculate totals
            $subtotal = 0;
            $items = [];
            
            foreach ($cartItems as $cartItem) {
                if (!$cartItem->storeItem) {
                    continue;
                }
                
                $storeItem = $cartItem->storeItem;
                
                $available = $this->inventoryService->getAvailableQuantity($storeItem, $branchId);
                if (!$storeItem->is_available || $available < $cartItem->quantity) {
                    throw new \Exception("Item {$storeItem->name} is not available or insufficient stock");
                }
                
                $itemTotal = $storeItem->price * $cartItem->quantity;
                $subtotal += $itemTotal;
                
                $items[] = [
                    'store_item_id' => $storeItem->id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $storeItem->price,
                    'total_price' => $itemTotal
                ];
            }
            
            if (empty($items)) {
                throw new \Exception('No valid items in cart');
            }
            
            // Get tax rate and delivery fee from system settings
            $systemSettings = \App\Models\SystemSetting::current();
            $taxRate = (float) ($systemSettings->tax_rate ?? 0.15);
            $deliveryFee = $request->delivery_method === 'delivery'
                ? (float) ($systemSettings->delivery_fee ?? 10.00)
                : 0.00;
            
            $taxAmount = $subtotal * $taxRate;
            $totalAmount = $subtotal + $taxAmount + $deliveryFee;
            
            // Build delivery address and phone
            $deliveryAddress = null;
            $deliveryPhone = null;
            
            if ($request->delivery_method === 'delivery') {
                // Validate delivery fields are provided
                if (empty($request->delivery_address) && empty($request->city) && empty($request->region)) {
                    throw new \Exception('Delivery address details are required for delivery orders');
                }
                
                $addressParts = array_filter([
                    trim($request->delivery_address ?? ''),
                    trim($request->city ?? ''),
                    trim($request->region ?? '')
                ]);
                
                if (empty($addressParts)) {
                    throw new \Exception('At least one delivery address field (address, city, or region) is required');
                }
                
                $deliveryAddress = implode(', ', $addressParts);
                $deliveryPhone = $request->delivery_phone ?? $request->phone;
                
                if (empty($deliveryPhone)) {
                    throw new \Exception('Delivery phone number is required for delivery orders');
                }
            }
            
            // Create order (only set order_source if column exists for backwards compatibility)
            $orderData = [
                'patient_id' => $patientId,
                'branch_id' => $branchId,
                'order_date' => now()->toDateString(),
                'delivery_method' => $request->delivery_method,
                'delivery_address' => $deliveryAddress,
                'delivery_phone' => $deliveryPhone,
                'delivery_notes' => $request->delivery_notes,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $totalAmount,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'created_by' => auth()->id()
            ];
            if (Schema::hasColumn('store_orders', 'order_source')) {
                $orderData['order_source'] = 'web';
            }
            $order = StoreOrder::create($orderData);
            
            // Create order items
            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'store_item_id' => $item['store_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price']
                ]);
                
                $soldItem = StoreItem::find($item['store_item_id']);
                if ($soldItem) {
                    $this->inventoryService->deductStock($soldItem, $item['quantity'], $branchId);
                }
            }
            
            // Create delivery record if delivery method
            if ($request->delivery_method === 'delivery') {
                // Validation already done above, but double-check for safety
                if (empty($deliveryAddress) || empty($deliveryPhone)) {
                    throw new \Exception('Delivery address and phone are required for delivery orders');
                }
                
                Delivery::create([
                    'order_id' => $order->id,
                    'delivery_address' => $deliveryAddress,
                    'delivery_phone' => $deliveryPhone,
                    'status' => 'pending',
                    'estimated_delivery' => now()->addHours(24),
                    'created_by' => auth()->id()
                ]);
            }
            
            // Clear patient's cart after successful order creation
            PatientCart::where('patient_id', $patientId)->delete();
            
            DB::commit();
            
            // Handle payment based on method
            if ($request->payment_method === 'paystack') {
                return redirect()->route('shop.payment', ['order' => $order->id]);
            } else {
                // Cash on delivery / pickup
                return redirect()->route('shop.order-success', ['order' => $order->id])
                    ->with('success', 'Order placed successfully! We will contact you for payment confirmation.');
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing checkout: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'patient_id' => $patientId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Failed to process order: ' . $e->getMessage())->withInput();
        }
    }
    
    /**
     * Initialize Paystack payment for order
     */
    public function payment($orderId)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        $patientId = $this->getPatientId();
        $order = StoreOrder::where('id', $orderId)
            ->where('patient_id', $patientId)
            ->firstOrFail();
        
        if ($order->payment_status === 'paid') {
            return redirect()->route('shop.order-success', ['order' => $order->id]);
        }
        
        $paystackService = new \App\Services\PaystackService();
        $paystackPublicKey = $paystackService->getPublicKey();
        
        if (!$paystackPublicKey) {
            return back()->with('error', 'Payment gateway not configured. Please contact support.');
        }
        
        return view('shop.payment', compact('order', 'paystackPublicKey'));
    }
    
    /**
     * Process Paystack payment callback
     */
    public function processPayment(Request $request, $orderId)
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        $patientId = $this->getPatientId();
        $order = StoreOrder::where('id', $orderId)
            ->where('patient_id', $patientId)
            ->firstOrFail();
        
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
            'email' => 'required|email',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Verify payment with Paystack
        $paystackService = new \App\Services\PaystackService();
        $verification = $paystackService->verifyTransaction($request->reference);
        
        if (!$verification['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed'
            ], 400);
        }
        
        $transactionData = $verification['data'];

        if (!$paystackService->amountMatchesExpected((float) $order->total_amount, $transactionData)) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount verification failed'
            ], 400);
        }
        
        // Check if payment was successful
        if ($transactionData['status'] !== 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Payment was not successful'
            ], 400);
        }
        
        DB::beginTransaction();
        
        try {
            // Update order payment status
            $order->update([
                'payment_status' => 'paid',
                'payment_method' => \App\Enums\PaymentMethod::Paystack->value,
                'payment_reference' => $request->reference,
                'transaction_id' => $transactionData['id'] ?? null,
                'payment_metadata' => json_encode($transactionData),
                'paid_at' => now(),
                'status' => 'processing',
                'updated_by' => auth()->id()
            ]);
            
            // Reload order with items relationship for invoice creation
            $order->load(['items.storeItem']);
            
            // Create invoice for e-commerce order
            $invoice = $this->createInvoiceForStoreOrder($order);
            
            // Record payment
            $paymentService = app(\App\Services\PaymentService::class);
            $paymentResult = $paymentService->recordPayment(
                $invoice->id,
                (float) $order->total_amount,
                \App\Enums\PaymentMethod::Paystack->value,
                [
                    'reference_number' => $request->reference,
                    'transaction_id' => $transactionData['id'] ?? null,
                    'notes' => 'E-commerce order payment via Paystack - Order #' . ($order->order_number ?? $order->store_order_number ?? 'ORD-' . $order->id),
                    'processed_by' => auth()->id() ?? 1,
                    'source_platform' => 'web',
                    'device_info' => $request->header('User-Agent'),
                    'ip_address' => $request->ip(),
                    'metadata' => [
                        'order_id' => $order->id,
                        'payment_type' => 'order',
                        'order_number' => $order->order_number ?? $order->store_order_number ?? 'ORD-' . $order->id,
                        'paystack_transaction' => $transactionData,
                    ]
                ]
            );
            
            if (!$paymentResult['success']) {
                throw new \Exception('Failed to record payment: ' . $paymentResult['message']);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'redirect_url' => route('shop.order-success', ['order' => $order->id])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing payment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Order success page
     */
    public function orderSuccess($orderId)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        $patientId = $this->getPatientId();
        $order = StoreOrder::with(['items.storeItem', 'delivery'])
            ->where('id', $orderId)
            ->where('patient_id', $patientId)
            ->firstOrFail();
        
        return view('shop.order-success', compact('order'));
    }
    
    /**
     * Create invoice for store order
     */
    protected function createInvoiceForStoreOrder(StoreOrder $order)
    {
        // Ensure items relationship is loaded
        if (!$order->relationLoaded('items')) {
            $order->load(['items.storeItem']);
        }
        
        $invoiceItems = [];
        foreach ($order->items as $orderItem) {
            // Ensure storeItem relationship is loaded
            if (!$orderItem->relationLoaded('storeItem')) {
                $orderItem->load('storeItem');
            }
            
            $invoiceItems[] = [
                'id' => 'item_' . uniqid(),
                'description' => $orderItem->storeItem->name ?? 'Store Item',
                'quantity' => $orderItem->quantity ?? 1,
                'unit_price' => $orderItem->unit_price ?? 0,
                'total' => $orderItem->total_price ?? 0,
                'service_type' => 'ecommerce'
            ];
        }
        
        if ($order->delivery_fee > 0) {
            $invoiceItems[] = [
                'id' => 'item_' . uniqid(),
                'description' => 'Delivery Fee',
                'quantity' => 1,
                'unit_price' => $order->delivery_fee,
                'total' => $order->delivery_fee,
                'service_type' => 'ecommerce'
            ];
        }
        
        // Get order number safely
        $orderNumber = $order->order_number ?? $order->store_order_number ?? 'ORD-' . $order->id;
        
        $invoice = \App\Models\Invoice::create([
            'patient_id' => $order->patient_id,
            'branch_id' => $order->branch_id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'items' => $invoiceItems,
            'subtotal' => $order->subtotal ?? 0,
            'tax_amount' => $order->tax_amount ?? 0,
            'discount_amount' => 0,
            'total_amount' => $order->total_amount ?? 0,
            'status' => 'pending',
            'payment_method' => $order->payment_method ?? \App\Enums\PaymentMethod::Paystack->value,
            'notes' => 'E-commerce Order #' . $orderNumber . ' - ' . ($order->delivery_method ?? 'pickup'),
            'created_by' => $order->created_by ?? auth()->id() ?? 1
        ]);
        
        return $invoice;
    }
}

