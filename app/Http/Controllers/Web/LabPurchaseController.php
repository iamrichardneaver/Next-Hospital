<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\Branch;
use App\Models\LabConsumable;
use App\Models\LabPurchaseOrder;
use App\Models\LabReagent;
use App\Models\Supplier;
use App\Services\LabPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabPurchaseController extends Controller
{
    use ResolvesUserBranch;

    public function __construct(private LabPurchaseService $purchaseService)
    {
    }

    public function index(Request $request)
    {
        $branchId = $this->resolveBranchFilter($request);

        $query = LabPurchaseOrder::with(['supplier', 'branch', 'creator'])
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

        return view('lab.purchases.index', compact('orders', 'branches', 'branchId'));
    }

    public function create()
    {
        $suppliers = Supplier::active()->forLaboratory()->orderBy('name')->get();
        $reagents = LabReagent::active()->orderBy('name')->get();
        $consumables = LabConsumable::active()->orderBy('name')->get();
        $branches = $this->getBranches();
        $defaultBranchId = auth()->user()->hasRole('super_admin')
            ? null
            : $this->resolveUserBranchId(['view_lab_purchases', 'create_lab_purchases', 'view_lab_inventory']);

        return view('lab.purchases.create', compact('suppliers', 'reagents', 'consumables', 'branches', 'defaultBranchId'));
    }

    public function store(Request $request)
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
                return back()->withInput()->with('error', "Invalid lab item on line " . ($index + 1) . ".");
            }
        }

        $branchId = auth()->user()->hasRole('super_admin')
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
            auth()->id()
        );

        return redirect()
            ->route('lab.purchases.show', $order)
            ->with('success', 'Lab supplies purchase order created successfully.');
    }

    public function show(LabPurchaseOrder $purchase)
    {
        $this->assertPurchaseAccess($purchase);
        $purchase->load(['items', 'supplier', 'branch', 'creator', 'orderer']);

        return view('lab.purchases.show', ['order' => $purchase]);
    }

    public function receiveForm(LabPurchaseOrder $purchase)
    {
        $this->assertPurchaseAccess($purchase);

        if (!$purchase->canReceive()) {
            return redirect()
                ->route('lab.purchases.show', $purchase)
                ->with('error', 'This purchase order is not open for receiving.');
        }

        $purchase->load(['items', 'supplier', 'branch']);

        return view('lab.purchases.receive', ['order' => $purchase]);
    }

    public function receive(Request $request, LabPurchaseOrder $purchase)
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
            ->route('lab.purchases.show', $purchase)
            ->with('success', 'Goods received — lab inventory updated and expense recorded in accounting.');
    }

    public function markOrdered(LabPurchaseOrder $purchase)
    {
        $this->assertPurchaseAccess($purchase);

        try {
            $this->purchaseService->markOrdered($purchase, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Purchase order marked as ordered.');
    }

    public function cancel(LabPurchaseOrder $purchase)
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

    protected function getBranches()
    {
        return Branch::where('is_active', true)->orderBy('name')->get();
    }
}
