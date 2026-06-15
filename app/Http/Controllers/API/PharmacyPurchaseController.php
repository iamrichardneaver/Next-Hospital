<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\Branch;
use App\Models\Drug;
use App\Models\PharmacyPurchaseOrder;
use App\Models\Supplier;
use App\Services\PharmacyPurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PharmacyPurchaseController extends Controller
{
    use ResolvesUserBranch;

    public function __construct(private PharmacyPurchaseService $purchaseService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranchFilter($request);

        $query = PharmacyPurchaseOrder::with(['supplier:id,name', 'branch:id,name', 'creator:id,first_name,last_name'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
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

    public function show(PharmacyPurchaseOrder $purchase): JsonResponse
    {
        $this->assertPurchaseAccess($purchase);
        $purchase->load(['items.drug', 'supplier', 'branch', 'creator', 'orderer']);

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
            'items.*.drug_id' => 'required|exists:drugs,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        $branchId = $request->user()->hasRole('super_admin')
            ? (int) $validated['branch_id']
            : $this->resolveUserBranchId(['create_pharmacy_purchases', 'manage_pharmacy_inventory']);

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
            'message' => 'Pharmacy purchase order created successfully.',
            'data' => $order->load(['items.drug', 'supplier', 'branch']),
        ], 201);
    }

    public function markOrdered(PharmacyPurchaseOrder $purchase): JsonResponse
    {
        $this->assertPurchaseAccess($purchase);

        try {
            $this->purchaseService->markOrdered($purchase, auth()->id());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Purchase order marked as ordered.', 'data' => $purchase->fresh()]);
    }

    public function cancel(PharmacyPurchaseOrder $purchase): JsonResponse
    {
        $this->assertPurchaseAccess($purchase);

        try {
            $this->purchaseService->cancelOrder($purchase, auth()->id());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Purchase order cancelled.', 'data' => $purchase->fresh()]);
    }

    public function receive(Request $request, PharmacyPurchaseOrder $purchase): JsonResponse
    {
        $this->assertPurchaseAccess($purchase);

        $validated = $request->validate([
            'receipts' => 'required|array',
            'receipts.*.quantity' => 'nullable|integer|min:0',
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
            'message' => 'Goods received — pharmacy inventory updated and expense recorded.',
            'data' => $purchase->fresh(['items.drug', 'supplier', 'branch']),
        ]);
    }

    public function formData(): JsonResponse
    {
        $user = auth()->user();
        $defaultBranchId = $user->hasRole('super_admin')
            ? null
            : $this->resolveUserBranchId(['view_pharmacy_purchases', 'create_pharmacy_purchases', 'manage_pharmacy_inventory']);

        return response()->json([
            'success' => true,
            'data' => [
                'suppliers' => Supplier::active()->forPharmacy()->orderBy('name')->get(['id', 'name']),
                'drugs' => Drug::where('is_active', true)->orderBy('name')->get(['id', 'name', 'generic_name', 'unit']),
                'branches' => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'default_branch_id' => $defaultBranchId,
            ],
        ]);
    }

    protected function resolveBranchFilter(Request $request): ?int
    {
        if ($request->user()->hasRole('super_admin')) {
            return $request->filled('branch_id') ? (int) $request->branch_id : null;
        }

        return $this->resolveUserBranchId([
            'view_pharmacy_purchases',
            'create_pharmacy_purchases',
            'receive_pharmacy_purchases',
            'manage_pharmacy_inventory',
        ]);
    }

    protected function assertPurchaseAccess(PharmacyPurchaseOrder $purchase): void
    {
        if (!auth()->user()->hasRole('super_admin')) {
            $this->assertResourceInUserBranch(
                (int) $purchase->branch_id,
                ['view_pharmacy_purchases', 'create_pharmacy_purchases', 'receive_pharmacy_purchases', 'manage_pharmacy_inventory']
            );
        }
    }
}
