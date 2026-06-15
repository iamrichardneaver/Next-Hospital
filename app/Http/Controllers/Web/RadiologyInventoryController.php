<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\RadiologyInventoryItem;
use App\Models\RadiologyInventoryMovement;
use App\Models\RadiologyInventoryStock;
use App\Services\RadiologyInventoryService;
use Illuminate\Http\Request;

class RadiologyInventoryController extends Controller
{
    use ResolvesUserBranch;

    public function __construct(private RadiologyInventoryService $inventoryService)
    {
    }

    public function index(Request $request)
    {
        $branchId = $this->resolveBranchFilter($request);

        $items = RadiologyInventoryItem::with('supplier')->active()->orderBy('category')->orderBy('name')->get();
        $itemsByCategory = $items->groupBy('category');

        $branchStock = collect();
        if ($branchId) {
            $branchStock = RadiologyInventoryStock::where('branch_id', $branchId)->get()
                ->groupBy('radiology_inventory_item_id');
        }

        $lowStockItems = [];
        if ($branchId) {
            foreach ($items as $item) {
                $level = $this->inventoryService->getStockLevel($item->id, $branchId);
                if ($level['available'] <= (float) $item->reorder_level) {
                    $lowStockItems[] = [
                        'name' => $item->name,
                        'category' => $item->category,
                        'available' => $level['available'],
                    ];
                }
            }
        }

        $stats = [
            'total_items' => $items->count(),
            'contrast' => $items->where('category', 'contrast')->count(),
            'film' => $items->where('category', 'film')->count(),
            'consumable' => $items->where('category', 'consumable')->count(),
            'supply' => $items->where('category', 'supply')->count(),
            'low_stock' => count($lowStockItems),
        ];

        return view('radiology.inventory.index', compact('items', 'itemsByCategory', 'branchStock', 'branchId', 'stats', 'lowStockItems'));
    }

    public function movements(Request $request)
    {
        $branchId = $this->resolveBranchFilter($request);

        $query = RadiologyInventoryMovement::with(['performer', 'branch', 'item'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->filled('item_id')) {
            $query->where('radiology_inventory_item_id', (int) $request->item_id);
        }

        $movements = $query->latest()->paginate(30)->withQueryString();

        return view('radiology.inventory.movements', compact('movements', 'branchId'));
    }

    protected function resolveBranchFilter(Request $request): ?int
    {
        if (auth()->user()->hasRole('super_admin')) {
            return $request->filled('branch_id') ? (int) $request->branch_id : null;
        }

        return $this->resolveUserBranchId(['view_radiology_inventory', 'view_radiology_purchases']);
    }
}
