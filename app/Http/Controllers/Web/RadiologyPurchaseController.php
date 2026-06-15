<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\Branch;
use App\Models\RadiologyInventoryItem;
use App\Models\RadiologyPurchaseOrder;
use App\Models\Supplier;
use App\Services\RadiologyPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RadiologyPurchaseController extends Controller
{
    use ResolvesUserBranch;

    public function __construct(private RadiologyPurchaseService $purchaseService)
    {
    }

    public function index(Request $request)
    {
        $branchId = $this->resolveBranchFilter($request);

        $query = RadiologyPurchaseOrder::with(['supplier', 'branch', 'creator'])
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

        $orders = $query->latest()->paginate(20)->withQueryString();
        $branches = $this->getBranches();

        return view('radiology.purchases.index', compact('orders', 'branches', 'branchId'));
    }

    public function create()
    {
        $suppliers = Supplier::active()->forRadiology()->orderBy('name')->get();
        $items = RadiologyInventoryItem::active()->orderBy('name')->get();
        $branches = $this->getBranches();
        $defaultBranchId = auth()->user()->hasRole('super_admin')
            ? null
            : $this->resolveUserBranchId(['view_radiology_purchases', 'create_radiology_purchases', 'view_radiology_inventory']);

        return view('radiology.purchases.create', compact('suppliers', 'items', 'branches', 'defaultBranchId'));
    }

    public function store(Request $request)
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
                return back()->withInput()->with('error', 'Invalid radiology inventory item on line ' . ($index + 1) . '.');
            }
        }

        $branchId = auth()->user()->hasRole('super_admin')
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
            auth()->id()
        );

        return redirect()
            ->route('radiology.purchases.show', $order)
            ->with('success', 'Radiology supplies purchase order created successfully.');
    }

    public function show(RadiologyPurchaseOrder $purchase)
    {
        $this->assertPurchaseAccess($purchase);
        $purchase->load(['items.inventoryItem', 'supplier', 'branch', 'creator', 'orderer']);

        return view('radiology.purchases.show', ['order' => $purchase]);
    }

    public function receiveForm(RadiologyPurchaseOrder $purchase)
    {
        $this->assertPurchaseAccess($purchase);

        if (!$purchase->canReceive()) {
            return redirect()
                ->route('radiology.purchases.show', $purchase)
                ->with('error', 'This purchase order is not open for receiving.');
        }

        $purchase->load(['items.inventoryItem', 'supplier', 'branch']);

        return view('radiology.purchases.receive', ['order' => $purchase]);
    }

    public function receive(Request $request, RadiologyPurchaseOrder $purchase)
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
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('radiology.purchases.show', $purchase)
            ->with('success', 'Goods received — radiology inventory updated and expense recorded in accounting.');
    }

    public function markOrdered(RadiologyPurchaseOrder $purchase)
    {
        $this->assertPurchaseAccess($purchase);

        try {
            $this->purchaseService->markOrdered($purchase, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Purchase order marked as ordered.');
    }

    public function cancel(RadiologyPurchaseOrder $purchase)
    {
        $this->assertPurchaseAccess($purchase);

        try {
            $this->purchaseService->cancelOrder($purchase, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Purchase order cancelled.');
    }

    protected function resolveBranchFilter(Request $request): ?int
    {
        if (auth()->user()->hasRole('super_admin')) {
            return $request->filled('branch_id') ? (int) $request->branch_id : null;
        }

        return $this->resolveUserBranchId(['view_radiology_purchases', 'create_radiology_purchases', 'receive_radiology_purchases', 'view_radiology_inventory']);
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

    protected function getBranches()
    {
        return Branch::where('is_active', true)->orderBy('name')->get();
    }
}
