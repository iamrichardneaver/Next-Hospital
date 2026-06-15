<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StoreItem;
use App\Models\StoreOrder;
use App\Models\OrderItem;
use App\Models\Delivery;
use App\Models\Patient;
use App\Models\PatientCart;
use App\Models\Drug;
use App\Models\Branch;
use App\Models\Visit;
use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use App\Services\StoreInventoryService;

class ECommerceController extends Controller
{
    protected StoreInventoryService $inventoryService;

    public function __construct(StoreInventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display a listing of store items.
     */
    public function index(Request $request)
    {
        $query = StoreItem::with(['drug'])
            ->where('is_active', true)
            ->where('is_available', true);

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Search by name or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('drug', function ($drugQuery) use ($search) {
                        $drugQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('generic_name', 'like', "%{$search}%");
                    });
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        if (in_array($sortBy, ['name', 'price', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = (int) $request->get('per_page', 20);
        $items = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem()
            ],
            'message' => 'Store items retrieved successfully'
        ]);
    }

    /**
     * Display the specified store item.
     */
    public function show($id)
    {
        $item = StoreItem::with(['drug'])
            ->where('is_active', true)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $item,
            'message' => 'Store item retrieved successfully'
        ]);
    }

    /**
     * Create a new store order.
     */
    public function createOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
            'items' => 'required|array|min:1',
            'items.*.store_item_id' => 'required|integer|exists:store_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'delivery_method' => 'required|in:pickup,delivery',
            'delivery_address' => 'required_if:delivery_method,delivery|nullable|string|max:500',
            'delivery_phone' => 'required_if:delivery_method,delivery|nullable|string|max:20',
            'city' => 'required_if:delivery_method,delivery|nullable|string|max:100',
            'region' => 'required_if:delivery_method,delivery|nullable|string|max:100',
            'delivery_notes' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:cash,paystack,card,momo,mobile_money,mobile_money_offline,insurance',
            'payment_reference' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            Log::error('Order validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Security: Validate that patient_id matches authenticated user's patient record
        $user = $request->user();
        if ($user && $user->hasRole('patient')) {
            $userPatient = $user->patient ?? Patient::where('user_id', $user->id)->first();
            if ($userPatient && $userPatient->id != $request->patient_id) {
                Log::warning('Patient ID mismatch in order creation', [
                    'user_id' => $user->id,
                    'requested_patient_id' => $request->patient_id,
                    'user_patient_id' => $userPatient->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid patient ID. You can only create orders for your own account.'
                ], 403);
            }
        }

        DB::beginTransaction();

        try {
            // Calculate totals
            $subtotal = 0;
            $items = [];

            foreach ($request->items as $item) {
                $storeItem = StoreItem::findOrFail($item['store_item_id']);

                $available = $this->inventoryService->getAvailableQuantity($storeItem, (int) $request->branch_id);
                if (!$storeItem->is_available || $available < $item['quantity']) {
                    throw new \Exception("Item {$storeItem->name} is not available or insufficient stock");
                }

                $itemTotal = $storeItem->price * $item['quantity'];
                $subtotal += $itemTotal;

                $items[] = [
                    'store_item_id' => $item['store_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $storeItem->price,
                    'total_price' => $itemTotal
                ];
            }

            // Validate that we have at least one valid item (matching web implementation)
            if (empty($items)) {
                throw new \Exception('No valid items to order');
            }

            // Get tax rate and delivery fee from system settings (dynamic)
            $systemSettings = \App\Models\SystemSetting::current();
            $taxRate = (float) ($systemSettings->tax_rate ?? 0.15);
            $deliveryFee = $request->delivery_method === 'delivery'
                ? (float) ($systemSettings->delivery_fee ?? 10.00)
                : 0.00;

            $taxAmount = $subtotal * $taxRate;
            $totalAmount = $subtotal + $taxAmount + $deliveryFee;

            // Build delivery address and phone (matching web implementation)
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
                $deliveryPhone = $request->delivery_phone ?? $request->phone ?? null;
                
                if (empty($deliveryPhone)) {
                    throw new \Exception('Delivery phone number is required for delivery orders');
                }
            }

            // Create order (ID will be generated automatically by HasIdPrefix trait)
            $orderData = [
                'patient_id' => $request->patient_id,
                'branch_id' => $request->branch_id,
                'order_date' => now()->toDateString(),
                'delivery_method' => $request->delivery_method,
                'delivery_address' => $deliveryAddress,
                'delivery_phone' => $deliveryPhone,
                'delivery_notes' => $request->delivery_notes,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $totalAmount,
                'payment_method' => in_array($request->payment_method, ['mobile_money', 'momo'], true)
                    ? \App\Enums\PaymentMethod::MobileMoneyOffline->value
                    : ($request->payment_method === 'card' ? \App\Enums\PaymentMethod::Paystack->value : $request->payment_method),
                'status' => 'pending',
                'notes' => $request->notes,
                'created_by' => auth()->id()
            ];
            if (Schema::hasColumn('store_orders', 'order_source')) {
                $orderData['order_source'] = 'mobile_app';
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
                    $this->inventoryService->deductStock($soldItem, $item['quantity'], (int) $request->branch_id);
                }
            }

            // Save payment reference if provided (for Paystack)
            if ($request->payment_reference) {
                $order->update(['payment_reference' => $request->payment_reference]);
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
            PatientCart::where('patient_id', $request->patient_id)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $order->load(['patient', 'branch', 'items.storeItem', 'delivery']),
                'message' => 'Order created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating order: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'patient_id' => $request->patient_id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get store orders.
     */
    public function getOrders(Request $request)
    {
        $query = StoreOrder::with(['patient', 'branch', 'items.storeItem', 'delivery'])
            ->orderBy('id', 'desc');

        // Security: If user is a patient, only show their own orders
        $user = $request->user();
        if ($user && $user->hasRole('patient')) {
            $userPatient = $user->patient ?? Patient::where('user_id', $user->id)->first();
            if ($userPatient) {
                $query->where('patient_id', $userPatient->id);
            } else {
                // Patient user but no patient record - return empty
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 20,
                        'total' => 0,
                        'from' => null,
                        'to' => null
                    ],
                    'message' => 'No orders found'
                ]);
            }
        }

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('patient_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items.storeItem', function ($itemQuery) use ($search) {
                        $itemQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by patient
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // Filter by delivery method
        if ($request->has('delivery_method')) {
            $query->where('delivery_method', $request->delivery_method);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        $perPage = (int) $request->get('per_page', 20);
        $orders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem()
            ],
            'message' => 'Store orders retrieved successfully'
        ]);
    }

    /**
     * Get order details.
     */
    public function getOrderDetails($orderId)
    {
        $order = StoreOrder::with(['patient', 'branch', 'items.storeItem', 'delivery'])
            ->findOrFail($orderId);

        // Security: If user is a patient, validate order ownership
        $user = request()->user();
        if ($user && $user->hasRole('patient')) {
            $userPatient = $user->patient ?? Patient::where('user_id', $user->id)->first();
            if ($userPatient && $order->patient_id != $userPatient->id) {
                Log::warning('Order access denied - ownership mismatch', [
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'order_patient_id' => $order->patient_id,
                    'user_patient_id' => $userPatient->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or access denied'
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order details retrieved successfully'
        ]);
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(Request $request, $orderId)
    {
        $order = StoreOrder::findOrFail($orderId);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,processing,ready,shipped,delivered,cancelled',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $order->update([
                'status' => $request->status,
                'status_updated_at' => now(),
                'status_notes' => $request->notes,
                'updated_by' => auth()->id()
            ]);

            if ($request->status === 'cancelled') {
                $order->load(['items.storeItem']);
                foreach ($order->items as $item) {
                    if ($item->storeItem) {
                        $this->inventoryService->restoreStock(
                            $item->storeItem,
                            $item->quantity,
                            $order->branch_id
                        );
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $order->load(['patient', 'branch', 'items.storeItem', 'delivery']),
                'message' => 'Order status updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating order status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get delivery tracking.
     */
    public function getDeliveryTracking($orderId)
    {
        $order = StoreOrder::with(['delivery', 'patient', 'branch'])
            ->findOrFail($orderId);

        if ($order->delivery_method !== 'delivery') {
            return response()->json([
                'success' => false,
                'message' => 'Order is not for delivery'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'delivery' => $order->delivery,
                'tracking_status' => $this->getTrackingStatus($order->status)
            ],
            'message' => 'Delivery tracking retrieved successfully'
        ]);
    }

    /**
     * Update delivery status.
     */
    public function updateDeliveryStatus(Request $request, $orderId)
    {
        $order = StoreOrder::findOrFail($orderId);

        $validator = Validator::make($request->all(), [
            'delivery_status' => 'required|in:pending,assigned,out_for_delivery,delivered,failed',
            'delivery_notes' => 'nullable|string',
            'delivered_at' => 'nullable|date',
            'delivery_person' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $delivery = $order->delivery;
        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'No delivery record found for this order'
            ], 404);
        }

        $delivery->update([
            'status' => $request->delivery_status,
            'delivery_notes' => $request->delivery_notes,
            'delivered_at' => $request->delivered_at,
            'delivery_person' => $request->delivery_person,
            'updated_by' => auth()->id()
        ]);

        // Update order status based on delivery status
        if ($request->delivery_status === 'delivered') {
            $order->update(['status' => 'delivered']);
        }

        return response()->json([
            'success' => true,
            'data' => $delivery,
            'message' => 'Delivery status updated successfully'
        ]);
    }

    /**
     * Get store categories.
     */
    public function getCategories()
    {
        $categories = StoreItem::select('category')
            ->distinct()
            ->where('is_active', true)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $categories->values(),
            'message' => 'Store categories retrieved successfully'
        ]);
    }

    /**
     * Get e-commerce statistics.
     */
    public function getStatistics(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(30);
        $dateTo = $request->date_to ?? now();

        $stats = [
            'total_items' => StoreItem::where('is_active', true)->count(),
            'available_items' => StoreItem::where('is_active', true)->where('is_available', true)->count(),
            'low_stock_items' => StoreItem::where('is_active', true)->whereRaw('stock_quantity <= minimum_stock')->count(),
            'total_orders' => StoreOrder::whereBetween('order_date', [$dateFrom, $dateTo])->count(),
            'pending_orders' => StoreOrder::where('status', 'pending')->count(),
            'delivered_orders' => StoreOrder::where('status', 'delivered')->whereBetween('order_date', [$dateFrom, $dateTo])->count(),
            'total_revenue' => StoreOrder::whereBetween('order_date', [$dateFrom, $dateTo])->sum('total_amount'),
            'delivery_orders' => StoreOrder::where('delivery_method', 'delivery')->whereBetween('order_date', [$dateFrom, $dateTo])->count(),
            'pickup_orders' => StoreOrder::where('delivery_method', 'pickup')->whereBetween('order_date', [$dateFrom, $dateTo])->count(),
            'top_items' => $this->getTopSellingItems($dateFrom, $dateTo),
            'monthly_sales' => $this->getMonthlySales($dateFrom, $dateTo)
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'E-commerce statistics retrieved successfully'
        ]);
    }

    /**
     * Get top selling items.
     */
    private function getTopSellingItems($dateFrom, $dateTo)
    {
        return OrderItem::whereHas('order', function ($query) use ($dateFrom, $dateTo) {
            $query->whereBetween('order_date', [$dateFrom, $dateTo]);
        })
            ->join('store_items', 'order_items.store_item_id', '=', 'store_items.id')
            ->selectRaw('store_items.name, store_items.category, SUM(order_items.quantity) as total_quantity, SUM(order_items.total_price) as total_revenue')
            ->groupBy('store_items.id', 'store_items.name', 'store_items.category')
            ->orderBy('total_quantity', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get monthly sales.
     */
    private function getMonthlySales($dateFrom, $dateTo)
    {
        return StoreOrder::whereBetween('order_date', [$dateFrom, $dateTo])
            ->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as month, COUNT(*) as order_count, SUM(total_amount) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    /**
     * Get tracking status.
     */
    private function getTrackingStatus($orderStatus)
    {
        $statusMap = [
            'pending' => 'Order received',
            'confirmed' => 'Order confirmed',
            'processing' => 'Preparing order',
            'ready' => 'Ready for pickup/delivery',
            'shipped' => 'Out for delivery',
            'delivered' => 'Delivered',
            'cancelled' => 'Order cancelled'
        ];

        return $statusMap[$orderStatus] ?? 'Unknown status';
    }

    /**
     * Search store items.
     */
    public function searchItems(Request $request)
    {
        $query = $request->get('q');

        if (!$query || strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Search query must be at least 2 characters'
            ], 400);
        }

        $items = StoreItem::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->orWhereHas('drug', function ($drugQuery) use ($query) {
                    $drugQuery->where('name', 'like', "%{$query}%")
                        ->orWhere('generic_name', 'like', "%{$query}%");
                });
        })
            ->where('is_active', true)
            ->where('is_available', true)
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
            'message' => 'Search completed successfully'
        ]);
    }

    /**
     * Create pharmacy-only visit for e-commerce order.
     */
    public function createPharmacyVisit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
            'order_id' => 'required|exists:store_orders,id',
            'chief_complaint' => 'nullable|string',
            'priority' => 'nullable|in:routine,urgent,critical'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create pharmacy-only visit
            $visit = Visit::create([
                'patient_id' => $request->patient_id,
                'branch_id' => $request->branch_id,
                'visit_type' => 'PharmacyOnly',
                'chief_complaint' => $request->chief_complaint ?? 'E-commerce order pickup',
                'priority' => $request->priority ?? 'routine',
                'check_in_time' => now(),
                'status' => 'active',
                'created_by' => auth()->id()
            ]);

            // Add to pharmacy queue
            $lastPosition = Queue::where('queue_type', 'Pharmacy')
                ->where('branch_id', $request->branch_id)
                ->where('status', '!=', 'cancelled')
                ->max('position') ?? 0;

            Queue::create([
                'visit_id' => $visit->id,
                'patient_id' => $visit->patient_id,
                'branch_id' => $visit->branch_id,
                'queue_type' => 'Pharmacy',
                'position' => $lastPosition + 1,
                'status' => 'waiting',
                'queued_at' => now(),
                'priority' => $visit->priority,
                'estimated_wait_time' => $this->calculateEstimatedWaitTime('Pharmacy', $request->branch_id)
            ]);

            // Update store order with visit reference
            $order = StoreOrder::findOrFail($request->order_id);
            $order->update([
                'visit_id' => $visit->id,
                'status' => 'confirmed'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $visit->load(['patient', 'branch', 'queues']),
                'message' => 'Pharmacy visit created successfully for e-commerce order'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating pharmacy visit: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Calculate estimated wait time for pharmacy queue.
     */
    private function calculateEstimatedWaitTime(string $queueType, int $branchId): int
    {
        $avgServiceTime = Queue::where('queue_type', $queueType)
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereNotNull('serving_at')
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, serving_at, completed_at)) as avg_service')
            ->value('avg_service') ?? 5; // Default 5 minutes for pharmacy

        $patientsAhead = Queue::where('queue_type', $queueType)
            ->where('branch_id', $branchId)
            ->where('status', 'waiting')
            ->count();

        return $patientsAhead * $avgServiceTime;
    }

    /**
     * Complete e-commerce order.
     */
    public function completeOrder(Request $request, $orderId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'delivery_notes' => 'nullable|string|max:500',
            'delivery_confirmation' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = StoreOrder::with(['visit.queues'])->findOrFail($orderId);

            if ($order->status === 'delivered') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is already delivered'
                ], 400);
            }

            DB::beginTransaction();

            // Update order status
            $order->update([
                'status' => 'delivered',
                'status_notes' => $request->delivery_notes,
                'status_updated_at' => now(),
                'updated_by' => auth()->id()
            ]);

            // Update delivery record if exists
            if ($order->delivery) {
                $order->delivery->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                    'delivery_notes' => $request->delivery_notes,
                    'confirmation_code' => $request->delivery_confirmation,
                    'delivery_confirmation' => $request->delivery_confirmation,
                    'updated_by' => auth()->id()
                ]);
            }

            // Complete associated visit if exists
            if ($order->visit) {
                // Update pharmacy queue status
                $order->visit->queues()->where('queue_type', 'Pharmacy')->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'served_by' => auth()->id()
                ]);

                // Complete visit if all queues are completed
                $activeQueues = $order->visit->queues()->whereIn('status', ['waiting', 'called', 'serving'])->count();
                if ($activeQueues === 0) {
                    $order->visit->update([
                        'status' => 'completed',
                        'check_out_time' => now(),
                        'updated_by' => auth()->id()
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $order->load(['patient', 'items.storeItem', 'delivery', 'visit']),
                'message' => 'Order completed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error completing order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initialize Paystack payment for e-commerce order
     */
    public function initializeOrderPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:store_orders,id',
                'email' => 'required|email',
                'reference' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $order = StoreOrder::findOrFail($request->order_id);

            // Security: Validate that order belongs to authenticated user's patient record
            $user = $request->user();
            if ($user && $user->hasRole('patient')) {
                $userPatient = $user->patient ?? Patient::where('user_id', $user->id)->first();
                if ($userPatient && $order->patient_id != $userPatient->id) {
                    Log::warning('Order ownership mismatch in payment initialization', [
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'order_patient_id' => $order->patient_id,
                        'user_patient_id' => $userPatient->id
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Order not found or access denied'
                    ], 403);
                }
            }

            // Check if order is already paid
            if ($order->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is already paid'
                ], 400);
            }

            // Prepare metadata
            $metadata = [
                'payment_type' => 'order',
                'order_id' => $order->id, // Primary key for order identification
                'reference_id' => $order->id, // Alias for backward compatibility
                'order_number' => $order->order_number ?? 'ORD-' . $order->id,
                'patient_id' => $order->patient_id,
                'branch_id' => $order->branch_id,
            ];

            // Get dynamic callback URL
            $callbackUrl = \App\Models\PaymentSetting::getPaystackCallbackUrl();

            // Initialize payment with Paystack
            $paystackService = new \App\Services\PaystackService();
            $result = $paystackService->initializeTransaction(
                $request->email,
                (float) $order->total_amount,
                $request->reference,
                $metadata,
                $callbackUrl
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment initialization failed'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'callback_url' => $callbackUrl,
                'message' => 'Payment initialized successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Order Paystack initialization error: ' . $e->getMessage(), [
                'order_id' => $request->order_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment initialization failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process Paystack payment for e-commerce order
     */
    public function processOrderPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:store_orders,id',
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

            $order = StoreOrder::findOrFail($request->order_id);

            // Security: Validate that order belongs to authenticated user's patient record
            $user = $request->user();
            if ($user && $user->hasRole('patient')) {
                $userPatient = $user->patient ?? Patient::where('user_id', $user->id)->first();
                if ($userPatient && $order->patient_id != $userPatient->id) {
                    Log::warning('Order ownership mismatch in payment processing', [
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'order_patient_id' => $order->patient_id,
                        'user_patient_id' => $userPatient->id
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Order not found or access denied'
                    ], 403);
                }
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

            // Update order payment status and status to processing (matching web implementation - single update for efficiency)
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

            // AUTO-CREATE INVOICE for e-commerce order (NEW)
            $invoice = $this->createInvoiceForStoreOrder($order);

            // AUTO-RECORD PAYMENT for the invoice (NEW)
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
                    'source_platform' => 'mobile', // TAG: Mobile e-commerce payment
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
                'data' => $order->load(['patient', 'items.storeItem', 'delivery']),
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'total_amount' => $invoice->total_amount,
                    'payment_status' => $paymentResult['invoice']->payment_status
                ],
                'message' => 'Payment processed successfully and invoice created'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order Paystack payment error: ' . $e->getMessage(), [
                'order_id' => $request->order_id ?? null,
                'reference' => $request->reference ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify order payment status
     */
    public function verifyOrderPayment($orderId)
    {
        try {
            $order = StoreOrder::with(['patient'])->findOrFail($orderId);

            // Security: Validate that order belongs to authenticated user's patient record
            $user = request()->user();
            if ($user && $user->hasRole('patient')) {
                $userPatient = $user->patient ?? Patient::where('user_id', $user->id)->first();
                if ($userPatient && $order->patient_id != $userPatient->id) {
                    Log::warning('Order ownership mismatch in payment verification', [
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'order_patient_id' => $order->patient_id,
                        'user_patient_id' => $userPatient->id
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Order not found or access denied'
                    ], 403);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->id,
                    'payment_status' => $order->payment_status,
                    'payment_method' => $order->payment_method,
                    'payment_reference' => $order->payment_reference,
                    'total_amount' => $order->total_amount,
                    'paid_at' => $order->paid_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error verifying payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create invoice for store order
     * Integrates e-commerce revenue into main financial system
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
        
        $invoice = Invoice::create([
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

        Log::info('Invoice created for e-commerce order', [
            'order_id' => $order->id,
            'order_number' => $orderNumber,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'amount' => $invoice->total_amount
        ]);

        return $invoice;
    }

    /**
     * Store a newly created store item.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'drug_id' => 'nullable|exists:drugs,id',
            'stock_quantity' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $imageUrl = $request->image_url;
            if (!$imageUrl && is_array($request->images) && !empty($request->images)) {
                $imageUrl = $request->images[0];
            }

            $storeItem = StoreItem::create([
                'name' => $request->name,
                'description' => $request->description,
                'category' => $request->category,
                'price' => $request->price,
                'drug_id' => $request->drug_id,
                'stock_quantity' => $request->stock_quantity ?? 0,
                'is_active' => $request->is_active ?? true,
                'is_available' => $request->is_available ?? true,
                'image_url' => $imageUrl,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $storeItem->load('drug'),
                'message' => 'Store item created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating store item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified store item.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $storeItem = StoreItem::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'price' => 'nullable|numeric|min:0',
                'drug_id' => 'nullable|exists:drugs,id',
                'stock_quantity' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
                'is_available' => 'boolean',
                'images' => 'nullable|array',
                'images.*' => 'url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updates = $request->only([
                'name',
                'description',
                'category',
                'price',
                'drug_id',
                'stock_quantity',
                'is_active',
                'is_available',
                'image_url',
            ]);

            if (!$request->has('image_url') && is_array($request->images) && !empty($request->images)) {
                $updates['image_url'] = $request->images[0];
            }

            $updates['updated_by'] = auth()->id();
            $storeItem->update($updates);

            return response()->json([
                'success' => true,
                'data' => $storeItem->load('drug'),
                'message' => 'Store item updated successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Store item not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating store item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified store item.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $storeItem = StoreItem::findOrFail($id);

            // Check if item has active orders
            $activeOrders = StoreOrder::whereHas('items', function ($query) use ($id) {
                $query->where('store_item_id', $id);
            })->whereIn('status', ['pending', 'processing', 'shipped'])->count();

            if ($activeOrders > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete store item with active orders'
                ], 422);
            }

            $storeItem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Store item deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Store item not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting store item: ' . $e->getMessage()
            ], 500);
        }
    }
}
