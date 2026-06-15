<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Models\StoreItem;
use App\Models\StoreOrder;
use App\Models\OrderItem;
use App\Models\Patient;
use App\Models\Category;
use App\Models\Delivery;
use App\Models\DeliveryRider;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\StoreInventoryService;

class ECommerceController extends Controller
{
    use ExportsListData;
    protected StoreInventoryService $inventoryService;

    public function __construct(StoreInventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display e-commerce dashboard
     */
    public function dashboard()
    {
        $statistics = [
            'total_items' => StoreItem::count(),
            'active_items' => StoreItem::where('is_active', true)->count(),
            'low_stock' => StoreItem::whereColumn('stock_quantity', '<=', 'minimum_stock')->count(),
            'out_of_stock' => StoreItem::where('stock_quantity', 0)->count(),
            'total_orders' => StoreOrder::count(),
            'pending_orders' => StoreOrder::where('status', 'pending')->count(),
            'processing_orders' => StoreOrder::where('status', 'processing')->count(),
            'shipped_orders' => StoreOrder::where('status', 'shipped')->count(),
            'delivered_orders' => StoreOrder::where('status', 'delivered')->count(),
            'total_deliveries' => Delivery::count(),
            'pending_deliveries' => Delivery::where('status', 'pending')->count(),
            'active_riders' => DeliveryRider::where('status', 'active')->count(),
            'riders_on_delivery' => DeliveryRider::where('status', 'on_delivery')->count(),
        ];

        $recentOrders = StoreOrder::with(['patient', 'branch'])
            ->latest()
            ->limit(10)
            ->get();

        $pendingDeliveries = Delivery::with(['order.patient', 'rider'])
            ->whereIn('status', ['pending', 'assigned', 'in_transit'])
            ->latest()
            ->limit(10)
            ->get();

        return view('ecommerce.dashboard', compact('statistics', 'recentOrders', 'pendingDeliveries'));
    }

    /**
     * Display a listing of store items (server-side rendering)
     */
    public function index(Request $request)
    {
        $query = StoreItem::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by stock status
        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'in_stock':
                    $query->where('stock_quantity', '>', 0);
                    break;
                case 'low_stock':
                    $query->whereColumn('stock_quantity', '<=', 'minimum_stock')
                          ->where('stock_quantity', '>', 0);
                    break;
                case 'out_of_stock':
                    $query->where('stock_quantity', 0);
                    break;
            }
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $storeItems = $query->latest('id')->paginate(20);
        
        // Get unique categories from store items
        $categories = StoreItem::select('category')->distinct()->whereNotNull('category')->pluck('category');
        
        $statistics = [
            'total_items' => StoreItem::count(),
            'active_items' => StoreItem::where('is_active', true)->count(),
            'low_stock' => StoreItem::whereColumn('stock_quantity', '<=', 'minimum_stock')->count(),
            'out_of_stock' => StoreItem::where('stock_quantity', 0)->count(),
        ];
        
        return view('ecommerce.index', compact('storeItems', 'statistics', 'categories'));
    }
    
    /**
     * Show the form for creating a new store item
     */
    public function create()
    {
        try {
            \Log::info('=== STORE ITEM CREATE PAGE LOAD ===');
            \Log::info('User ID: ' . auth()->id());
            \Log::info('User Email: ' . auth()->user()->email);
            
            // Check permission
            if (!auth()->user()->can('create_store_items')) {
                \Log::warning('User attempted to access create page without permission');
                abort(403, 'You do not have permission to create store items.');
            }
            
            // Get unique categories from existing store items
            $existingCategories = StoreItem::select('category')->distinct()->whereNotNull('category')->pluck('category')->toArray();
            \Log::info('Existing categories count: ' . count($existingCategories));
            
            // Define predefined categories
            $predefinedCategories = [
                'Medicines & Drugs',
                'Medical Equipment',
                'First Aid & Bandages',
                'Health Supplements',
                'Personal Care',
                'Baby Care',
                'Surgical Supplies',
                'Laboratory Supplies',
                'Diagnostic Equipment',
                'Mobility Aids',
                'Home Healthcare',
                'Vitamins & Minerals',
                'Skin Care',
                'Hygiene Products',
                'Medical Devices',
                'Other'
            ];
            
            // Merge and remove duplicates
            $categories = collect(array_unique(array_merge($predefinedCategories, $existingCategories)))->sort()->values();
            \Log::info('Total categories: ' . $categories->count());
            
            \Log::info('=== CREATE PAGE LOADED SUCCESSFULLY ===');
            
            return view('ecommerce.create', compact('categories'));
            
        } catch (\Exception $e) {
            \Log::error('=== CREATE PAGE LOAD ERROR ===');
            \Log::error('Error: ' . $e->getMessage());
            \Log::error('Stack Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Store a newly created store item in database
     */
    public function store(Request $request)
    {
        try {
            // Debug: Log request start
            \Log::info('=== STORE ITEM CREATE REQUEST START ===');
            \Log::info('User ID: ' . auth()->id());
            \Log::info('User Email: ' . auth()->user()->email);
            \Log::info('Request Method: ' . $request->method());
            \Log::info('Request URL: ' . $request->fullUrl());
            \Log::info('Request Data: ', $request->all());
            \Log::info('CSRF Token: ' . $request->header('X-CSRF-TOKEN'));
            \Log::info('Session ID: ' . session()->getId());
            
            // Check permission
            \Log::info('Checking create_store_items permission...');
            if (!auth()->user()->can('create_store_items')) {
                \Log::error('Permission denied for user: ' . auth()->user()->email);
                abort(403, 'You do not have permission to create store items.');
            }
            \Log::info('Permission check passed');

            // Validate
            \Log::info('Starting validation...');
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'sku' => 'nullable|string|max:100|unique:store_items,sku',
                'category' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'cost_price' => 'required|numeric|min:0',
                'stock_quantity' => 'required|integer|min:0',
                'minimum_stock' => 'required|integer|min:0',
                'is_active' => 'nullable|in:on,1,true,0,false',
            ]);
            \Log::info('Validation passed');
            \Log::info('Validated data: ', $validated);
            
            // Prepare data
            $validated['created_by'] = auth()->id();
            // Convert checkbox value to boolean (checkbox sends "on" when checked, nothing when unchecked)
            $validated['is_active'] = $request->has('is_active') && in_array($request->input('is_active'), ['on', '1', 'true', true]) ? 1 : 0;
            \Log::info('Final data to insert: ', $validated);
            \Log::info('is_active value after conversion: ' . $validated['is_active']);
            
            // Create store item
            \Log::info('Creating store item...');
            $storeItem = StoreItem::create($validated);
            \Log::info('Store item created successfully with ID: ' . $storeItem->id);
            
            \Log::info('=== STORE ITEM CREATE REQUEST SUCCESS ===');
            
            return redirect()->route('ecommerce.index')
                ->with('success', 'Store item created successfully!');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('=== VALIDATION ERROR ===');
            \Log::error('Validation errors: ', $e->errors());
            throw $e;
            
        } catch (\Exception $e) {
            \Log::error('=== STORE ITEM CREATE ERROR ===');
            \Log::error('Error Message: ' . $e->getMessage());
            \Log::error('Error File: ' . $e->getFile());
            \Log::error('Error Line: ' . $e->getLine());
            \Log::error('Stack Trace: ' . $e->getTraceAsString());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create store item: ' . $e->getMessage());
        }
    }
    
    /**
     * Display the specified store item
     */
    public function show(StoreItem $ecommerce)
    {
        // Rename for clarity
        $storeItem = $ecommerce;
        
        $storeItem->load(['creator']);
        
        // Get order history for this item
        $orderHistory = OrderItem::with(['order.patient'])
            ->where('store_item_id', $storeItem->id)
            ->latest()
            ->limit(20)
            ->get();
        
        return view('ecommerce.show', compact('storeItem', 'orderHistory'));
    }
    
    /**
     * Show the form for editing the specified store item
     */
    public function edit(StoreItem $ecommerce)
    {
        // Rename for clarity
        $storeItem = $ecommerce;
        
        // Get unique categories from existing store items
        $existingCategories = StoreItem::select('category')->distinct()->whereNotNull('category')->pluck('category')->toArray();
        
        // Define predefined categories
        $predefinedCategories = [
            'Medicines & Drugs',
            'Medical Equipment',
            'First Aid & Bandages',
            'Health Supplements',
            'Personal Care',
            'Baby Care',
            'Surgical Supplies',
            'Laboratory Supplies',
            'Diagnostic Equipment',
            'Mobility Aids',
            'Home Healthcare',
            'Vitamins & Minerals',
            'Skin Care',
            'Hygiene Products',
            'Medical Devices',
            'Other'
        ];
        
        // Merge and remove duplicates
        $categories = collect(array_unique(array_merge($predefinedCategories, $existingCategories)))->sort()->values();
        
        return view('ecommerce.edit', compact('storeItem', 'categories'));
    }
    
    /**
     * Update the specified store item in database
     */
    public function update(Request $request, StoreItem $ecommerce)
    {
        try {
            // Rename for clarity
            $storeItem = $ecommerce;
            
            // Check permission
            if (!auth()->user()->can('edit_store_items')) {
                abort(403, 'You do not have permission to edit store items.');
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'sku' => 'nullable|string|max:100|unique:store_items,sku,' . $storeItem->id,
                'category' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'cost_price' => 'required|numeric|min:0',
                'stock_quantity' => 'required|integer|min:0',
                'minimum_stock' => 'required|integer|min:0',
                'is_active' => 'nullable|in:on,1,true,0,false',
            ]);
            
            $validated['updated_by'] = auth()->id();
            // Convert checkbox value to boolean (checkbox sends "on" when checked, nothing when unchecked)
            $validated['is_active'] = $request->has('is_active') && in_array($request->input('is_active'), ['on', '1', 'true', true]) ? 1 : 0;
            
            $storeItem->update($validated);
            
            return redirect()->route('ecommerce.index')
                ->with('success', 'Store item updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Error updating store item: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'store_item_id' => $ecommerce->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update store item. Please try again.');
        }
    }
    
    /**
     * Remove the specified store item from database
     */
    public function destroy(StoreItem $ecommerce)
    {
        try {
            // Rename for clarity
            $storeItem = $ecommerce;
            
            // Check permission
            if (!auth()->user()->can('delete_store_items')) {
                abort(403, 'You do not have permission to delete store items.');
            }

            // Check if item has active orders
            $activeOrders = \App\Models\StoreOrder::whereHas('items', function ($query) use ($storeItem) {
                $query->where('store_item_id', $storeItem->id);
            })->whereIn('status', ['pending', 'processing', 'shipped'])->count();

            if ($activeOrders > 0) {
                return back()
                    ->with('error', 'Cannot delete store item with active orders.');
            }

            $storeItem->delete();
            
            return redirect()->route('ecommerce.index')
                ->with('success', 'Store item deleted successfully!');
        } catch (\Exception $e) {
            \Log::error('Error deleting store item: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'store_item_id' => $ecommerce->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete store item. Please try again.');
        }
    }
    
    /**
     * Display a listing of store orders
     */
    public function orders(Request $request)
    {
        $query = StoreOrder::with(['patient', 'branch', 'items', 'creator']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('patient_number', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by delivery method
        if ($request->filled('delivery_method')) {
            $query->where('delivery_method', $request->delivery_method);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('order_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('order_date', '<=', $request->end_date);
        }

        $orders = $query->latest('id')->paginate(20);
        
        $statistics = [
            'total_orders' => StoreOrder::count(),
            'pending_orders' => StoreOrder::where('status', 'pending')->count(),
            'processing_orders' => StoreOrder::where('status', 'processing')->count(),
            'shipped_orders' => StoreOrder::where('status', 'shipped')->count(),
            'delivered_orders' => StoreOrder::where('status', 'delivered')->count(),
            'cancelled_orders' => StoreOrder::where('status', 'cancelled')->count(),
            'total_revenue' => StoreOrder::whereIn('status', ['delivered', 'completed'])->sum('total_amount'),
        ];
        
        return view('ecommerce.orders', compact('orders', 'statistics'));
    }
    
    /**
     * Display the specified store order
     */
    public function showOrder(StoreOrder $order)
    {
        $order->load(['patient', 'branch', 'items.storeItem', 'creator', 'delivery.rider']);
        
        return view('ecommerce.order-show', compact('order'));
    }
    
    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request, StoreOrder $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,processing,ready,shipped,delivered,cancelled',
            'status_notes' => 'nullable|string|max:1000',
        ]);
        
        $order->update([
            'status' => $validated['status'],
            'status_notes' => $validated['status_notes'] ?? null,
            'status_updated_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        if ($validated['status'] === 'cancelled') {
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
        
        return redirect()->back()
            ->with('success', 'Order status updated successfully!');
    }

    // ==================== DELIVERY MANAGEMENT ====================

    /**
     * Display delivery dashboard
     */
    public function deliveries(Request $request)
    {
        $query = Delivery::with(['order.patient', 'rider', 'order.branch']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('order', function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($q2) use ($search) {
                      $q2->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by rider
        if ($request->filled('rider_id')) {
            $query->where('rider_id', $request->rider_id);
        }

        $deliveries = $query->latest('id')->paginate(20);
        $riders = DeliveryRider::active()->get();
        
        $statistics = [
            'total_deliveries' => Delivery::count(),
            'pending_deliveries' => Delivery::where('status', 'pending')->count(),
            'assigned_deliveries' => Delivery::where('status', 'assigned')->count(),
            'in_transit_deliveries' => Delivery::where('status', 'in_transit')->count(),
            'delivered' => Delivery::where('status', 'delivered')->count(),
            'failed_deliveries' => Delivery::where('status', 'failed')->count(),
        ];
        
        return view('ecommerce.deliveries', compact('deliveries', 'statistics', 'riders'));
    }

    /**
     * Assign delivery to rider
     */
    public function assignDelivery(Request $request, Delivery $delivery)
    {
        $validated = $request->validate([
            'rider_id' => 'required|exists:delivery_riders,id',
            'estimated_delivery' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            // Update delivery
            $delivery->update([
                'rider_id' => $validated['rider_id'],
                'status' => 'assigned',
                'assigned_at' => now(),
                'estimated_delivery' => $validated['estimated_delivery'] ?? now()->addHours(2),
                'updated_by' => auth()->id(),
            ]);

            // Update rider status
            $rider = DeliveryRider::find($validated['rider_id']);
            $rider->update(['status' => 'on_delivery']);

            // Update order status
            $delivery->order->update([
                'status' => 'shipped',
                'status_updated_at' => now(),
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Delivery assigned to rider successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to assign delivery: ' . $e->getMessage());
        }
    }

    /**
     * Update delivery status
     */
    public function updateDeliveryStatus(Request $request, Delivery $delivery)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,assigned,in_transit,delivered,failed,cancelled',
            'rider_notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $updates = [
                'status' => $validated['status'],
                'rider_notes' => $validated['rider_notes'] ?? null,
                'updated_by' => auth()->id(),
            ];

            // Set timestamps based on status
            if ($validated['status'] === 'in_transit' && !$delivery->picked_up_at) {
                $updates['picked_up_at'] = now();
            }

            if ($validated['status'] === 'delivered') {
                $updates['delivered_at'] = now();
                $updates['actual_delivery'] = now();

                // Update order status
                $delivery->order->update([
                    'status' => 'delivered',
                    'status_updated_at' => now(),
                ]);

                // Update rider status and statistics
                if ($delivery->rider) {
                    $delivery->rider->update(['status' => 'active']);
                    $delivery->rider->updateStatistics();
                }
            }

            if ($validated['status'] === 'failed') {
                // Update rider status
                if ($delivery->rider) {
                    $delivery->rider->update(['status' => 'active']);
                    $delivery->rider->updateStatistics();
                }
            }

            $delivery->update($updates);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Delivery status updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to update delivery status: ' . $e->getMessage());
        }
    }

    // ==================== RIDER MANAGEMENT ====================

    /**
     * Display riders list
     */
    public function riders(Request $request)
    {
        $query = DeliveryRider::with(['user', 'branch']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('rider_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $riders = $query->latest('id')->paginate(20);
        $branches = Branch::all();
        
        $statistics = [
            'total_riders' => DeliveryRider::count(),
            'active_riders' => DeliveryRider::where('status', 'active')->count(),
            'on_delivery' => DeliveryRider::where('status', 'on_delivery')->count(),
            'inactive_riders' => DeliveryRider::where('status', 'inactive')->count(),
        ];
        
        return view('ecommerce.riders', compact('riders', 'statistics', 'branches'));
    }

    /**
     * Show rider details
     */
    public function showRider(DeliveryRider $rider)
    {
        $rider->load(['user', 'branch', 'deliveries.order.patient']);
        
        $statistics = [
            'total_deliveries' => $rider->total_deliveries,
            'successful_deliveries' => $rider->successful_deliveries,
            'failed_deliveries' => $rider->failed_deliveries,
            'success_rate' => $rider->total_deliveries > 0 
                ? round(($rider->successful_deliveries / $rider->total_deliveries) * 100, 2) 
                : 0,
            'average_rating' => $rider->rating,
        ];

        $recentDeliveries = $rider->deliveries()
            ->with(['order.patient'])
            ->latest()
            ->limit(20)
            ->get();
        
        return view('ecommerce.rider-show', compact('rider', 'statistics', 'recentDeliveries'));
    }

    /**
     * Show create rider form
     */
    public function createRider()
    {
        $branches = Branch::all();
        $users = \App\Models\User::whereDoesntHave('deliveryRider')->get();
        
        return view('ecommerce.rider-create', compact('branches', 'users'));
    }

    /**
     * Store new rider
     */
    public function storeRider(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|unique:delivery_riders,user_id',
            'branch_id' => 'required|exists:branches,id',
            'phone' => 'required|string|max:20',
            'emergency_contact' => 'nullable|string|max:20',
            'vehicle_type' => 'nullable|string|max:50',
            'vehicle_number' => 'nullable|string|max:50',
            'license_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['status'] = 'active';

        $rider = DeliveryRider::create($validated);

        return redirect()->route('ecommerce.riders')
            ->with('success', 'Delivery rider created successfully!');
    }

    /**
     * Edit rider
     */
    public function editRider(DeliveryRider $rider)
    {
        $branches = Branch::all();
        
        return view('ecommerce.rider-edit', compact('rider', 'branches'));
    }

    /**
     * Update rider
     */
    public function updateRider(Request $request, DeliveryRider $rider)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'phone' => 'required|string|max:20',
            'emergency_contact' => 'nullable|string|max:20',
            'vehicle_type' => 'nullable|string|max:50',
            'vehicle_number' => 'nullable|string|max:50',
            'license_number' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive,on_delivery,off_duty',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['updated_by'] = auth()->id();

        $rider->update($validated);

        return redirect()->route('ecommerce.riders')
            ->with('success', 'Delivery rider updated successfully!');
    }

    /**
     * Delete rider
     */
    public function destroyRider(DeliveryRider $rider)
    {
        // Check if rider has pending deliveries
        $pendingDeliveries = $rider->deliveries()
            ->whereIn('status', ['assigned', 'in_transit'])
            ->count();

        if ($pendingDeliveries > 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete rider with pending deliveries!');
        }

        $rider->delete();

        return redirect()->route('ecommerce.riders')
            ->with('success', 'Delivery rider deleted successfully!');
    }

    public function export(Request $request)
    {
        $query = StoreItem::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('stock_status')) {
            match ($request->stock_status) {
                'in_stock' => $query->where('stock_quantity', '>', 0),
                'low_stock' => $query->whereColumn('stock_quantity', '<=', 'minimum_stock')->where('stock_quantity', '>', 0),
                'out_of_stock' => $query->where('stock_quantity', 0),
                default => null,
            };
        }

        $query->latest('id');

        return $this->exportFromQuery($request, $query, [
            'SKU' => 'sku',
            'Name' => 'name',
            'Category' => 'category',
            'Price' => 'price',
            'Stock Quantity' => 'stock_quantity',
            'Minimum Stock' => 'minimum_stock',
            'Active' => fn ($i) => $i->is_active ? 'Yes' : 'No',
        ], 'ecommerce-items', 'view_store_items');
    }

    public function exportOrders(Request $request)
    {
        $query = StoreOrder::with(['patient', 'branch']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($pq) => $pq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('patient_number', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('delivery_method')) {
            $query->where('delivery_method', $request->delivery_method);
        }
        if ($request->filled('start_date')) {
            $query->where('order_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('order_date', '<=', $request->end_date);
        }

        $query->latest('id');

        return $this->exportFromQuery($request, $query, [
            'Order #' => 'order_number',
            'Patient' => fn ($o) => $o->patient?->full_name ?? '',
            'Patient Number' => fn ($o) => $o->patient?->patient_number ?? '',
            'Branch' => fn ($o) => $o->branch?->name ?? '',
            'Total Amount' => 'total_amount',
            'Status' => 'status',
            'Delivery Method' => 'delivery_method',
            'Order Date' => fn ($o) => $this->formatExportDate($o->order_date),
        ], 'ecommerce-orders', 'view_store_items');
    }
}