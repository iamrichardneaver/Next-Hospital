<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\LabConsumable;
use App\Models\LabPurchaseOrder;
use App\Models\LabReagent;
use App\Models\Supplier;
use App\Services\LabPurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabPurchaseController extends Controller
{
    use ResolvesUserBranch;

    public function __construct(private LabPurchaseService $purchaseService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranchFilter($request);

        $query = LabPurchaseOrder::with(['supplier:id,name', 'branch:id,name', 'creator:id,first_name,last_name'])
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

    public function show(LabPurchaseOrder $purchase): JsonResponse
    {
        $this->assertPurchaseAccess($purchase);
        $purchase->load(['items', 'supplier', 'branch', 'creator', 'orderer']);

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
            'items.*.item_type' => 'required|in:reagent,consumable',
            'items.*.item_id' => 'required|integer|min:1',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        foreach ($validated['items'] as $index => $item) {
            $table = $item['item_type'] === 'reagent' ? 'lab_reagents' : 'lab_consumables';
            if (!DB::table($table)->where('id', $item['item_id'])->exists()) {
                return response()->json(['message' => 'Invalid lab item on line ' . ($index + 1) . '.'], 422);
            }
        }

        $branchId = $request->user()->hasRole('super_admin')
            ? (int) $validated['branch_id']
            : $this->resolveUserBranchId(['create_lab_purchases', 'view_lab_inventory']);

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
            'message' => 'Lab purchase order created successfully.',
            'data' => $order->load(['items', 'supplier', 'branch']),
        ], 201);
    }

    public function markOrdered(LabPurchaseOrder $purchase): JsonResponse
    {
        $this->assertPurchaseAccess($purchase);

        try {
            $this->purchaseService->markOrdered($purchase, auth()->id());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Purchase order marked as ordered.', 'data' => $purchase->fresh()]);
    }

    public function cancel(LabPurchaseOrder $purchase): JsonResponse
    {
        $this->assertPurchaseAccess($purchase);

        try {
            $this->purchaseService->cancelOrder($purchase, auth()->id());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Purchase order cancelled.', 'data' => $purchase->fresh()]);
    }

    public function receive(Request $request, LabPurchaseOrder $purchase): JsonResponse
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
            'message' => 'Goods received — lab inventory updated and expense recorded.',
            'data' => $purchase->fresh(['items', 'supplier', 'branch']),
        ]);
    }

    public function formData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'suppliers' => Supplier::active()->forLaboratory()->orderBy('name')->get(['id', 'name']),
                'reagents' => LabReagent::active()->orderBy('name')->get(['id', 'name', 'unit']),
                'consumables' => LabConsumable::active()->orderBy('name')->get(['id', 'name', 'unit']),
            ],
        ]);
    }

    protected function resolveBranchFilter(Request $request): ?int
    {
        if ($request->user()->hasRole('super_admin')) {
            return $request->filled('branch_id') ? (int) $request->branch_id : null;
        }

        return $this->resolveUserBranchId(['view_lab_purchases', 'create_lab_purchases', 'receive_lab_purchases', 'view_lab_inventory']);
    }

    protected function assertPurchaseAccess(LabPurchaseOrder $purchase): void
    {
        if (!auth()->user()->hasRole('super_admin')) {
            $this->assertResourceInUserBranch(
                (int) $purchase->branch_id,
                ['view_lab_purchases', 'create_lab_purchases', 'receive_lab_purchases', 'view_lab_inventory']
            );
        }
    }
}
