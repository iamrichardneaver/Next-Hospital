<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PatientCart;
use App\Models\StoreItem;
use App\Models\Drug;
use App\Models\Patient;
use App\Services\StoreInventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PatientCartController extends Controller
{
    protected StoreInventoryService $inventoryService;

    public function __construct(StoreInventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    protected function resolvePatient($user): ?Patient
    {
        if ($user->patient) {
            return $user->patient;
        }

        return Patient::where('user_id', $user->id)->first();
    }

    /**
     * Get patient's cart with full item details
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $patient = $this->resolvePatient($user);
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient record not found'
                ], 404);
            }

            $cartItems = PatientCart::where('patient_id', $patient->id)
                ->with(['storeItem', 'drug'])
                ->get();

            // Transform cart items to match mobile CartItemModel: id = cart row id (for update/remove), store_item_id, quantity, unit_price, total_price, name, store_item
            $transformedItems = $cartItems->map(function($cartItem) {
                $unitPrice = null;
                $name = null;
                $storeItemPayload = null;

                if ($cartItem->item_type === 'store_item' && $cartItem->storeItem) {
                    $unitPrice = (float) $cartItem->storeItem->price;
                    $name = $cartItem->storeItem->name;
                    $storeItemPayload = [
                        'id' => $cartItem->storeItem->id,
                        'name' => $cartItem->storeItem->name,
                        'price' => (float) $cartItem->storeItem->price,
                        'image_url' => $cartItem->storeItem->image_url,
                        'category' => $cartItem->storeItem->category,
                        'description' => $cartItem->storeItem->description,
                    ];
                } elseif ($cartItem->item_type === 'drug' && $cartItem->drug) {
                    $unitPrice = (float) $cartItem->drug->selling_price;
                    $name = $cartItem->drug->name;
                    $storeItemPayload = null; // drug not a store_item
                }

                $qty = (int) $cartItem->quantity;
                $storeItemId = (int) (isset($cartItem->store_item_id) ? $cartItem->store_item_id : ($cartItem->storeItem->id ?? 0));

                return [
                    'id' => $cartItem->id, // cart row id for PUT /patient/cart/{id}/quantity and DELETE
                    'store_item_id' => $storeItemId,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice !== null ? $unitPrice * $qty : null,
                    'name' => $name,
                    'price' => $unitPrice,
                    'selling_price' => $unitPrice,
                    'store_item' => $storeItemPayload,
                ];
            })->filter();

            return response()->json([
                'success' => true,
                'data' => $transformedItems->values(),
                'message' => 'Cart retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving cart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add item to cart
     */
    public function addItem(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $patient = $this->resolvePatient($user);
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient record not found'
                ], 404);
            }

            $branchId = $patient->branch_id;

            $validator = Validator::make($request->all(), [
                'item_type' => 'required|in:drug,store_item',
                'item_id' => 'required|integer',
                'quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate stock availability for store items (matching web implementation)
            if ($request->item_type === 'store_item') {
                $storeItem = StoreItem::find($request->item_id);
                if (!$storeItem) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Store item not found'
                    ], 404);
                }
                
                $available = $this->inventoryService->getAvailableQuantity($storeItem, $branchId);
                if ($available < $request->quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock available'
                    ], 400);
                }
            }

            // Check if item already exists in cart
            $existingCart = PatientCart::where('patient_id', $patient->id)
                ->where('item_type', $request->item_type)
                ->where(function($query) use ($request) {
                    if ($request->item_type === 'drug') {
                        $query->where('drug_id', $request->item_id);
                    } else {
                        $query->where('store_item_id', $request->item_id);
                    }
                })
                ->first();

            if ($existingCart) {
                // For store items, check stock before updating quantity
                if ($request->item_type === 'store_item') {
                    $storeItem = StoreItem::find($request->item_id);
                    $newQuantity = $existingCart->quantity + $request->quantity;
                    
                    $available = $storeItem
                        ? $this->inventoryService->getAvailableQuantity($storeItem, $branchId)
                        : 0;
                    if ($available < $newQuantity) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot add more. Maximum available: ' . $available
                        ], 400);
                    }
                }
                
                // Update quantity
                $existingCart->quantity += $request->quantity;
                $existingCart->save();
                $existingCart->load(['storeItem', 'drug']);

                return response()->json([
                    'success' => true,
                    'data' => $this->transformCartItemForMobile($existingCart),
                    'message' => 'Cart quantity updated'
                ]);
            } else {
                // Create new cart item
                $cartData = [
                    'patient_id' => $patient->id,
                    'item_type' => $request->item_type,
                    'quantity' => $request->quantity,
                ];

                if ($request->item_type === 'drug') {
                    $cartData['drug_id'] = $request->item_id;
                } else {
                    $cartData['store_item_id'] = $request->item_id;
                }

                $cartItem = PatientCart::create($cartData);
                $cartItem->load(['storeItem', 'drug']);

                return response()->json([
                    'success' => true,
                    'data' => $this->transformCartItemForMobile($cartItem),
                    'message' => 'Item added to cart'
                ], 201);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding to cart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateQuantity(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $patient = $this->resolvePatient($user);

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient record not found'
                ], 404);
            }
            
            $cartItem = PatientCart::where('patient_id', $patient->id)
                ->with(['storeItem'])
                ->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check stock availability for store items (matching web implementation)
            if ($cartItem->item_type === 'store_item' && $cartItem->storeItem) {
                $available = $this->inventoryService->getAvailableQuantity(
                    $cartItem->storeItem,
                    $patient->branch_id
                );
                if ($available < $request->quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock available'
                    ], 400);
                }
            }

            $cartItem->update(['quantity' => $request->quantity]);
            $cartItem->load(['storeItem', 'drug']);

            return response()->json([
                'success' => true,
                'data' => $this->transformCartItemForMobile($cartItem),
                'message' => 'Cart updated'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating cart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeItem(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $patient = $this->resolvePatient($user);

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient record not found'
                ], 404);
            }
            
            $cartItem = PatientCart::where('patient_id', $patient->id)
                ->findOrFail($id);

            $cartItem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear entire cart
     */
    public function clearCart(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $patient = $this->resolvePatient($user);
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient record not found'
                ], 404);
            }

            PatientCart::where('patient_id', $patient->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error clearing cart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cart summary with totals.
     * Tax rate and delivery fee come from system settings (dynamic).
     * Optional query: delivery_method = pickup | delivery. If pickup, delivery_fee is 0; if delivery or omitted, delivery_fee from settings.
     */
    public function getCartSummary(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $patient = $this->resolvePatient($user);

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient record not found'
                ], 404);
            }

            $deliveryMethod = $request->query('delivery_method', 'delivery');
            if (!in_array($deliveryMethod, ['pickup', 'delivery'], true)) {
                $deliveryMethod = 'delivery';
            }

            $cartItems = PatientCart::where('patient_id', $patient->id)
                ->with(['storeItem', 'drug'])
                ->get();

            $subtotal = 0;
            foreach ($cartItems as $cartItem) {
                if ($cartItem->item_type === 'store_item' && $cartItem->storeItem) {
                    $subtotal += $cartItem->storeItem->price * $cartItem->quantity;
                } elseif ($cartItem->item_type === 'drug' && $cartItem->drug) {
                    $subtotal += $cartItem->drug->selling_price * $cartItem->quantity;
                }
            }

            // Tax rate and delivery fee from system settings (no hardcoding)
            $settings = \App\Models\SystemSetting::current();
            $taxRate = (float) ($settings->tax_rate ?? 0.15);
            $deliveryFee = $deliveryMethod === 'pickup'
                ? 0.0
                : (float) ($settings->delivery_fee ?? 10.00);

            $tax = $subtotal * $taxRate;
            $total = $subtotal + $tax + $deliveryFee;

            $totalQuantity = (int) $cartItems->sum('quantity');

            return response()->json([
                'success' => true,
                'data' => [
                    'items_count' => $cartItems->count(),
                    'item_count' => $totalQuantity,
                    'total_items' => $totalQuantity,
                    'total_quantity' => $totalQuantity,
                    'subtotal' => round($subtotal, 2),
                    'tax_rate' => round($taxRate, 4),
                    'tax' => round($tax, 2),
                    'delivery_fee' => round($deliveryFee, 2),
                    'total' => round($total, 2),
                    'delivery_method' => $deliveryMethod,
                ],
                'message' => 'Cart summary retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting cart summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transform a single PatientCart model to mobile CartItemModel-compatible array.
     * Same shape as index() response items for consistent parsing.
     */
    private function transformCartItemForMobile(PatientCart $cartItem): array
    {
        $unitPrice = null;
        $name = null;
        $storeItemPayload = null;

        if ($cartItem->item_type === 'store_item' && $cartItem->storeItem) {
            $unitPrice = (float) $cartItem->storeItem->price;
            $name = $cartItem->storeItem->name;
            $storeItemPayload = [
                'id' => $cartItem->storeItem->id,
                'name' => $cartItem->storeItem->name,
                'price' => (float) $cartItem->storeItem->price,
                'image_url' => $cartItem->storeItem->image_url,
                'category' => $cartItem->storeItem->category,
                'description' => $cartItem->storeItem->description,
            ];
        } elseif ($cartItem->item_type === 'drug' && $cartItem->drug) {
            $unitPrice = (float) $cartItem->drug->selling_price;
            $name = $cartItem->drug->name;
        }

        $qty = (int) $cartItem->quantity;
        $storeItemId = (int) (isset($cartItem->store_item_id) ? $cartItem->store_item_id : ($cartItem->storeItem->id ?? 0));

        return [
            'id' => $cartItem->id,
            'store_item_id' => $storeItemId,
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice !== null ? $unitPrice * $qty : null,
            'name' => $name,
            'price' => $unitPrice,
            'selling_price' => $unitPrice,
            'store_item' => $storeItemPayload,
        ];
    }
}

