<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LabEquipment;
use App\Models\LabReagent;
use App\Models\LabConsumable;
use App\Models\LabEquipmentMaintenance;
use App\Models\LabInventoryTransaction;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class LabInventoryController extends Controller
{
    // ========================================
    // EQUIPMENT MANAGEMENT
    // ========================================

    /**
     * Get all lab equipment with filters
     */
    public function getEquipment(Request $request): JsonResponse
    {
        $query = LabEquipment::with(['supplier', 'creator', 'updater'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by equipment type
        if ($request->has('equipment_type')) {
            $query->where('equipment_type', $request->equipment_type);
        }

        // Filter by location
        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Filter by supplier
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('model', 'like', '%' . $search . '%')
                  ->orWhere('manufacturer', 'like', '%' . $search . '%')
                  ->orWhere('serial_number', 'like', '%' . $search . '%');
            });
        }

        $equipment = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $equipment,
            'message' => 'Lab equipment retrieved successfully'
        ]);
    }

    /**
     * Get equipment statistics
     */
    public function getEquipmentStats(): JsonResponse
    {
        $stats = [
            'total_equipment' => LabEquipment::count(),
            'operational' => LabEquipment::where('status', 'operational')->count(),
            'maintenance' => LabEquipment::where('status', 'maintenance')->count(),
            'out_of_service' => LabEquipment::where('status', 'out_of_service')->count(),
            'needs_maintenance' => LabEquipment::where('next_maintenance_date', '<=', now()->addDays(7))->count(),
            'under_warranty' => LabEquipment::where('warranty_expiry', '>', now())->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Equipment statistics retrieved successfully'
        ]);
    }


    // ========================================
    // REAGENT MANAGEMENT
    // ========================================

    /**
     * Get all lab reagents with filters
     */
    public function getReagents(Request $request): JsonResponse
    {
        $query = LabReagent::with(['supplier', 'creator', 'updater'])
            ->orderBy('created_at', 'desc');

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by supplier
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by stock status
        if ($request->has('stock_status')) {
            switch ($request->stock_status) {
                case 'low_stock':
                    $query->whereRaw('current_stock <= minimum_stock');
                    break;
                case 'expiring_soon':
                    $query->where('expiry_date', '<=', now()->addDays(30));
                    break;
                case 'expired':
                    $query->where('expiry_date', '<', now());
                    break;
            }
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('catalog_number', 'like', '%' . $search . '%')
                  ->orWhere('manufacturer', 'like', '%' . $search . '%');
            });
        }

        $reagents = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $reagents,
            'message' => 'Lab reagents retrieved successfully'
        ]);
    }

    /**
     * Get reagent statistics
     */
    public function getReagentStats(): JsonResponse
    {
        $stats = [
            'total_reagents' => LabReagent::count(),
            'low_stock' => LabReagent::whereRaw('current_stock <= minimum_stock')->count(),
            'expiring_soon' => LabReagent::where('expiry_date', '<=', now()->addDays(30))->count(),
            'expired' => LabReagent::where('expiry_date', '<', now())->count(),
            'needs_reorder' => LabReagent::whereRaw('current_stock <= reorder_level')->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Reagent statistics retrieved successfully'
        ]);
    }

    // ========================================
    // CONSUMABLES MANAGEMENT
    // ========================================

    /**
     * Get all lab consumables with filters
     */
    public function getConsumables(Request $request): JsonResponse
    {
        $query = LabConsumable::with(['supplier', 'creator', 'updater'])
            ->orderBy('created_at', 'desc');

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by supplier
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by stock status
        if ($request->has('stock_status')) {
            switch ($request->stock_status) {
                case 'low_stock':
                    $query->whereRaw('current_stock <= minimum_stock');
                    break;
                case 'expiring_soon':
                    $query->where('expiry_date', '<=', now()->addDays(30));
                    break;
                case 'expired':
                    $query->where('expiry_date', '<', now());
                    break;
            }
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('catalog_number', 'like', '%' . $search . '%')
                  ->orWhere('manufacturer', 'like', '%' . $search . '%');
            });
        }

        $consumables = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $consumables,
            'message' => 'Lab consumables retrieved successfully'
        ]);
    }

    /**
     * Get consumable statistics
     */
    public function getConsumableStats(): JsonResponse
    {
        $stats = [
            'total_consumables' => LabConsumable::count(),
            'low_stock' => LabConsumable::whereRaw('current_stock <= minimum_stock')->count(),
            'expiring_soon' => LabConsumable::where('expiry_date', '<=', now()->addDays(30))->count(),
            'expired' => LabConsumable::where('expiry_date', '<', now())->count(),
            'needs_reorder' => LabConsumable::whereRaw('current_stock <= reorder_level')->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Consumable statistics retrieved successfully'
        ]);
    }

    // ========================================
    // SUPPLIERS MANAGEMENT
    // ========================================

    /**
     * Get all suppliers
     */
    public function getSuppliers(Request $request): JsonResponse
    {
        $query = Supplier::with(['creator', 'updater'])->orderBy('name');

        // Filter by type
        if ($request->has('supplier_type')) {
            $query->where('supplier_type', $request->supplier_type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        } else {
            $query->where('is_active', true); // Default to active suppliers
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('contact_person', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        $suppliers = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $suppliers,
            'message' => 'Suppliers retrieved successfully'
        ]);
    }

    /**
     * Create a new supplier
     */
    public function createSupplier(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'tax_id' => 'nullable|string|max:255',
            'supplier_type' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string',
            'delivery_terms' => 'nullable|string',
            'rating' => 'nullable|numeric|min:0|max:5',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $supplier = Supplier::create([
                ...$request->all(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'is_active' => $request->is_active ?? true,
            ]);

            $supplier->load(['creator', 'updater']);

            return response()->json([
                'success' => true,
                'data' => $supplier,
                'message' => 'Supplier created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific supplier
     */
    public function getSupplier($id): JsonResponse
    {
        try {
            $supplier = Supplier::with(['creator', 'updater', 'equipment'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $supplier,
                'message' => 'Supplier retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found'
            ], 404);
        }
    }

    /**
     * Update a supplier
     */
    public function updateSupplier(Request $request, $id): JsonResponse
    {
        try {
            $supplier = Supplier::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'contact_person' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:20',
                'website' => 'nullable|url|max:255',
                'tax_id' => 'nullable|string|max:255',
                'supplier_type' => 'nullable|string|max:255',
                'payment_terms' => 'nullable|string',
                'delivery_terms' => 'nullable|string',
                'rating' => 'nullable|numeric|min:0|max:5',
                'notes' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $supplier->update([
                ...$request->all(),
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $supplier->fresh()->load(['creator', 'updater']),
                'message' => 'Supplier updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a supplier
     */
    public function deleteSupplier($id): JsonResponse
    {
        try {
            $supplier = Supplier::findOrFail($id);

            // Check if supplier is used by any equipment
            if ($supplier->equipment()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete supplier. It is associated with equipment.'
                ], 400);
            }

            $supplier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // INVENTORY TRANSACTIONS
    // ========================================

    /**
     * Get inventory transactions
     */
    public function getTransactions(Request $request): JsonResponse
    {
        $query = LabInventoryTransaction::with(['supplier', 'creator'])
            ->orderBy('transaction_date', 'desc');

        // Filter by item type
        if ($request->has('item_type')) {
            $query->where('item_type', $request->item_type);
        }

        // Filter by transaction type
        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'message' => 'Inventory transactions retrieved successfully'
        ]);
    }

    // ========================================
    // DASHBOARD STATISTICS
    // ========================================

    /**
     * Get comprehensive inventory statistics
     */
    public function getInventoryStats(): JsonResponse
    {
        $stats = [
            'equipment' => [
                'total' => LabEquipment::count(),
                'operational' => LabEquipment::where('status', 'operational')->count(),
                'maintenance' => LabEquipment::where('status', 'maintenance')->count(),
                'needs_maintenance' => LabEquipment::where('next_maintenance_date', '<=', now()->addDays(7))->count()
            ],
            'reagents' => [
                'total' => LabReagent::count(),
                'low_stock' => LabReagent::whereRaw('current_stock <= minimum_stock')->count(),
                'expiring_soon' => LabReagent::where('expiry_date', '<=', now()->addDays(30))->count(),
                'expired' => LabReagent::where('expiry_date', '<', now())->count()
            ],
            'consumables' => [
                'total' => LabConsumable::count(),
                'low_stock' => LabConsumable::whereRaw('current_stock <= minimum_stock')->count(),
                'expiring_soon' => LabConsumable::where('expiry_date', '<=', now()->addDays(30))->count(),
                'expired' => LabConsumable::where('expiry_date', '<', now())->count()
            ],
            'suppliers' => [
                'total' => Supplier::count(),
                'active' => Supplier::where('is_active', true)->count()
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Inventory statistics retrieved successfully'
        ]);
    }

    // ========================================
    // EQUIPMENT CRUD OPERATIONS
    // ========================================

    /**
     * Create new equipment
     */
    public function createEquipment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'serial_number' => 'required|string|max:255|unique:lab_equipment',
            'equipment_type' => 'required|in:analyzer,microscope,centrifuge,incubator,refrigerator,freezer,other',
            'location' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'installation_date' => 'nullable|date',
            'last_maintenance_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date',
            'status' => 'required|in:operational,maintenance,out_of_service,retired',
            'specifications' => 'nullable|json',
            'warranty_expiry' => 'nullable|date',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $equipment = LabEquipment::create([
                ...$request->all(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ]);

            $equipment->load(['supplier', 'creator', 'updater']);

            return response()->json([
                'success' => true,
                'data' => $equipment,
                'message' => 'Equipment created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create equipment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update equipment
     */
    public function updateEquipment(Request $request, $id): JsonResponse
    {
        $equipment = LabEquipment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'model' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'serial_number' => 'sometimes|required|string|max:255|unique:lab_equipment,serial_number,' . $id,
            'equipment_type' => 'sometimes|required|in:analyzer,microscope,centrifuge,incubator,refrigerator,freezer,other',
            'location' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'installation_date' => 'nullable|date',
            'last_maintenance_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date',
            'status' => 'sometimes|required|in:operational,maintenance,out_of_service,retired',
            'specifications' => 'nullable|json',
            'warranty_expiry' => 'nullable|date',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $equipment->update([
                ...$request->all(),
                'updated_by' => auth()->id()
            ]);

            $equipment->load(['supplier', 'creator', 'updater']);

            return response()->json([
                'success' => true,
                'data' => $equipment,
                'message' => 'Equipment updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update equipment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete equipment
     */
    public function deleteEquipment($id): JsonResponse
    {
        try {
            $equipment = LabEquipment::findOrFail($id);
            $equipment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Equipment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete equipment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // REAGENT CRUD OPERATIONS
    // ========================================

    /**
     * Create new reagent
     */
    public function createReagent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'catalog_number' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'category' => 'nullable|string|max:255',
            'subcategory' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unit_of_measure' => 'required|string|max:50',
            'current_stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'maximum_stock' => 'required|numeric|min:0',
            'reorder_level' => 'required|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:255',
            'storage_requirements' => 'nullable|json',
            'storage_temperature' => 'nullable|numeric',
            'storage_humidity' => 'nullable|numeric',
            'light_sensitive' => 'boolean',
            'hazardous' => 'boolean',
            'safety_notes' => 'nullable|string',
            'usage_instructions' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reagent = LabReagent::create([
                ...$request->all(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ]);

            $reagent->load(['supplier', 'creator', 'updater']);

            return response()->json([
                'success' => true,
                'data' => $reagent,
                'message' => 'Reagent created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create reagent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update reagent
     */
    public function updateReagent(Request $request, $id): JsonResponse
    {
        $reagent = LabReagent::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'catalog_number' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'category' => 'nullable|string|max:255',
            'subcategory' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unit_of_measure' => 'sometimes|required|string|max:50',
            'current_stock' => 'sometimes|required|numeric|min:0',
            'minimum_stock' => 'sometimes|required|numeric|min:0',
            'maximum_stock' => 'sometimes|required|numeric|min:0',
            'reorder_level' => 'sometimes|required|numeric|min:0',
            'unit_cost' => 'sometimes|required|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:255',
            'storage_requirements' => 'nullable|json',
            'storage_temperature' => 'nullable|numeric',
            'storage_humidity' => 'nullable|numeric',
            'light_sensitive' => 'boolean',
            'hazardous' => 'boolean',
            'safety_notes' => 'nullable|string',
            'usage_instructions' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reagent->update([
                ...$request->all(),
                'updated_by' => auth()->id()
            ]);

            $reagent->load(['supplier', 'creator', 'updater']);

            return response()->json([
                'success' => true,
                'data' => $reagent,
                'message' => 'Reagent updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reagent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete reagent
     */
    public function deleteReagent($id): JsonResponse
    {
        try {
            $reagent = LabReagent::findOrFail($id);
            $reagent->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reagent deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete reagent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // CONSUMABLE CRUD OPERATIONS
    // ========================================

    /**
     * Create new consumable
     */
    public function createConsumable(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'catalog_number' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'category' => 'nullable|string|max:255',
            'subcategory' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unit_of_measure' => 'required|string|max:50',
            'current_stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'maximum_stock' => 'required|numeric|min:0',
            'reorder_level' => 'required|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:255',
            'storage_requirements' => 'nullable|json',
            'disposable' => 'boolean',
            'sterile' => 'boolean',
            'single_use' => 'boolean',
            'usage_instructions' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $consumable = LabConsumable::create([
                ...$request->all(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ]);

            $consumable->load(['supplier', 'creator', 'updater']);

            return response()->json([
                'success' => true,
                'data' => $consumable,
                'message' => 'Consumable created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create consumable',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update consumable
     */
    public function updateConsumable(Request $request, $id): JsonResponse
    {
        $consumable = LabConsumable::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'catalog_number' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'category' => 'nullable|string|max:255',
            'subcategory' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unit_of_measure' => 'sometimes|required|string|max:50',
            'current_stock' => 'sometimes|required|numeric|min:0',
            'minimum_stock' => 'sometimes|required|numeric|min:0',
            'maximum_stock' => 'sometimes|required|numeric|min:0',
            'reorder_level' => 'sometimes|required|numeric|min:0',
            'unit_cost' => 'sometimes|required|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:255',
            'storage_requirements' => 'nullable|json',
            'disposable' => 'boolean',
            'sterile' => 'boolean',
            'single_use' => 'boolean',
            'usage_instructions' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $consumable->update([
                ...$request->all(),
                'updated_by' => auth()->id()
            ]);

            $consumable->load(['supplier', 'creator', 'updater']);

            return response()->json([
                'success' => true,
                'data' => $consumable,
                'message' => 'Consumable updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update consumable',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete consumable
     */
    public function deleteConsumable($id): JsonResponse
    {
        try {
            $consumable = LabConsumable::findOrFail($id);
            $consumable->delete();

            return response()->json([
                'success' => true,
                'message' => 'Consumable deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete consumable',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
