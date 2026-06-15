<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\RadiologyInventoryItem;
use App\Models\RadiologyPurchaseOrder;
use App\Models\Supplier;
use App\Services\RadiologyPurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RadiologyPurchaseController extends Controller
{
    use ResolvesUserBranch;

    public function __construct(private RadiologyPurchaseService $purchaseService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranchFilter($request);

        $query = RadiologyPurchaseOrder::with(['supplier:id,name', 'branch:id,name', 'creator:id,first_name,last_name'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->latest()->paginate(min((int) $request->get('per_page', 20), 50));

        return response()->json(['success' => true, 'data' => $orders]);
    }

    public function show(RadiologyPurchaseOrder $purchase): JsonResponse
    {
        $this->assertPurchaseAccess($purchase);
        $purchase->load(['items.inventoryItem', 'supplier', 'branch', 'creator', 'orderer']);

        return response()->json(['success' => true, 'data' => $purchase]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string|max:2000',
            'submit_action' => 'nullable|in:draft,order',
            'items' => 'required|array|min:1',
            'items.*.radiology_inventory_item_id' => 'required|integer|min:1',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        foreach ($validated['items'] as $index => $item) {
            if (!DB::table('radiology_inventory_items')->where('id', $item['radiology_inventory_item_id'])->where('is_active', true)->exists()) {
                return response()->json(['message' => 'Invalid radiology inventory item on line ' . ($index + 1) . '.'], 422);
            }
        }

        $branchId = $request->user()->hasRole('super_admin')
            ? (int) $validated['branch_id']
            : $this->resolveUserBranchId(['create_radiology_purchases', 'view_radiology_inventory']);

        $status = ($validated['submit_action'] ?? 'draft') === 'order' ? 'ordered' : 'draft';

        $order = $this->purchaseService->createOrder(
            [
                'supplier_id' => $validated['supplier_id'],
                'branch_id' => $branchId,
                'notes' => $validated['notes'] ?? null,
                'status' => $status,
            ],
            $validated['items'],
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Radiology purchase order created successfully.',
            'data' => $order->load(['items', 'supplier', 'branch']),
        ], 201);
    }

    public function markOrdered(RadiologyPurchaseOrder $purchase): JsonResponse
    {
        $this->assertPurchaseAccess($purchase);

        try {
            $this->purchaseService->markOrdered($purchase, auth()->id());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Purchase order marked as ordered.', 'data' => $purchase->fresh()]);
    }

    public function cancel(RadiologyPurchaseOrder $purchase): JsonResponse
    {
        $this->assertPurchaseAccess($purchase);

        try {
            $this->purchaseService->cancelOrder($purchase, auth()->id());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Purchase order cancelled.', 'data' => $purchase->fresh()]);
    }

    public function receive(Request $request, RadiologyPurchaseOrder $purchase): JsonResponse
    {
        $this->assertPurchaseAccess($purchase);

        $validated = $request->validate([
            'receipts' => 'required|array',
            'receipts.*.quantity' => 'nullable|numeric|min:0',
            'receipts.*.batch_number' => 'nullable|string|max:100',
            'receipts.*.expiry_date' => 'nullable|date',
        ]);

        try {
            $this->purchaseService->receiveGoods($purchase, $validated['receipts'], auth()->id());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Goods received — radiology inventory updated and expense recorded.',
            'data' => $purchase->fresh(['items', 'supplier', 'branch']),
        ]);
    }

    public function inventory(Request $request): JsonResponse
    {
        $items = RadiologyInventoryItem::active()
            ->orderBy('name')
            ->paginate(min((int) $request->get('per_page', 50), 100));

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function formData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'suppliers' => Supplier::active()->forRadiology()->orderBy('name')->get(['id', 'name']),
                'items' => RadiologyInventoryItem::active()->orderBy('name')->get(['id', 'name', 'unit', 'current_stock']),
            ],
        ]);
    }

    protected function resolveBranchFilter(Request $request): ?int
    {
        if ($request->user()->hasRole('super_admin')) {
            return $request->filled('branch_id') ? (int) $request->branch_id : null;
        }

        return $this->resolveUserBranchId([
            'view_radiology_purchases',
            'create_radiology_purchases',
            'receive_radiology_purchases',
            'view_radiology_inventory',
        ]);
    }

    protected function assertPurchaseAccess(RadiologyPurchaseOrder $purchase): void
    {
        if (!auth()->user()->hasRole('super_admin')) {
            $this->assertResourceInUserBranch(
                (int) $purchase->branch_id,
                ['view_radiology_purchases', 'create_radiology_purchases', 'receive_radiology_purchases', 'view_radiology_inventory']
            );
        }
    }
}
