<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\StockCount;
use App\Services\StockCountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockCountController extends Controller
{
    use ResolvesUserBranch;

    public function __construct(protected StockCountService $stockCountService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $branchId = $this->resolveUserBranchId(['view_pharmacy', 'manage_pharmacy_inventory']);

        $counts = StockCount::with(['counter:id,first_name,last_name', 'branch:id,name'])
            ->where('branch_id', $branchId)
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->department))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json(['success' => true, 'data' => $counts]);
    }

    public function store(Request $request): JsonResponse
    {
        $branchId = $this->resolveUserBranchId('manage_pharmacy_inventory');

        $validated = $request->validate([
            'department' => 'required|in:pharmacy,lab,radiology',
            'notes' => 'nullable|string|max:2000',
        ]);

        $stockCount = $this->stockCountService->createDraft(
            $branchId,
            $validated['department'],
            $request->user()->id,
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock count created',
            'data' => $stockCount,
        ], 201);
    }

    public function show(StockCount $stockCount): JsonResponse
    {
        $stockCount->load(['items', 'counter', 'branch']);
        $summary = $this->stockCountService->varianceSummary($stockCount);

        return response()->json([
            'success' => true,
            'data' => $stockCount,
            'summary' => $summary,
        ]);
    }

    public function updateCounts(Request $request, StockCount $stockCount): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:stock_count_items,id',
            'items.*.counted_qty' => 'nullable|numeric|min:0',
        ]);

        try {
            $updated = $this->stockCountService->updateItemCounts($stockCount, $validated['items']);

            return response()->json(['success' => true, 'data' => $updated]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function complete(StockCount $stockCount): JsonResponse
    {
        try {
            $completed = $this->stockCountService->complete($stockCount);

            return response()->json([
                'success' => true,
                'message' => 'Stock count completed',
                'data' => $completed,
                'summary' => $this->stockCountService->varianceSummary($completed),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
