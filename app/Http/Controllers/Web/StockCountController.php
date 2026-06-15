<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\StockCount;
use App\Services\StockCountService;
use Illuminate\Http\Request;

class StockCountController extends Controller
{
    use ResolvesUserBranch;

    public function __construct(protected StockCountService $stockCountService)
    {
    }

    public function index(Request $request)
    {
        $branchId = $this->resolveUserBranchId('view_pharmacy');

        $counts = StockCount::with(['counter', 'branch'])
            ->where('branch_id', $branchId)
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->department))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('stock-count.index', compact('counts', 'branchId'));
    }

    public function create()
    {
        $branchId = $this->resolveUserBranchId('manage_pharmacy_inventory');

        return view('stock-count.create', compact('branchId'));
    }

    public function store(Request $request)
    {
        $branchId = $this->resolveUserBranchId('manage_pharmacy_inventory');

        $validated = $request->validate([
            'department' => 'required|in:pharmacy,lab,radiology',
            'notes' => 'nullable|string|max:2000',
        ]);

        $stockCount = $this->stockCountService->createDraft(
            $branchId,
            $validated['department'],
            auth()->id(),
            $validated['notes'] ?? null
        );

        return redirect()->route('stock-count.show', $stockCount)
            ->with('success', 'Stock count started. Enter physical counts for each item.');
    }

    public function show(StockCount $stockCount)
    {
        $this->authorizeBranch($stockCount);
        $stockCount->load(['items', 'counter', 'branch']);
        $summary = $this->stockCountService->varianceSummary($stockCount);

        return view('stock-count.show', compact('stockCount', 'summary'));
    }

    public function updateCounts(Request $request, StockCount $stockCount)
    {
        $this->authorizeBranch($stockCount);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:stock_count_items,id',
            'items.*.counted_qty' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->stockCountService->updateItemCounts($stockCount, $validated['items']);

            return back()->with('success', 'Counts saved.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function complete(StockCount $stockCount)
    {
        $this->authorizeBranch($stockCount);

        try {
            $this->stockCountService->complete($stockCount);

            return redirect()->route('stock-count.show', $stockCount)
                ->with('success', 'Stock count completed. Variance report is available.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    protected function authorizeBranch(StockCount $stockCount): void
    {
        $branchId = $this->resolveUserBranchId(['view_pharmacy', 'manage_pharmacy_inventory']);
        if ((int) $stockCount->branch_id !== (int) $branchId && !auth()->user()->hasRole('super_admin')) {
            abort(403);
        }
    }
}
