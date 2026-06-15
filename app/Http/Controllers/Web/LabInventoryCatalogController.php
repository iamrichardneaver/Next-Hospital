<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\LabConsumable;
use App\Models\LabInventoryMovement;
use App\Models\LabInventoryStock;
use App\Models\LabReagent;
use App\Services\LabInventoryService;
use Illuminate\Http\Request;

class LabInventoryCatalogController extends Controller
{
    use ResolvesUserBranch;

    public function __construct(private LabInventoryService $inventoryService)
    {
    }

    public function index(Request $request)
    {
        $branchId = $this->resolveBranchFilter($request);

        $reagents = LabReagent::with('supplier')->active()->orderBy('name')->get();
        $consumables = LabConsumable::with('supplier')->active()->orderBy('name')->get();

        $branchStock = collect();
        if ($branchId) {
            $branchStock = LabInventoryStock::where('branch_id', $branchId)->get()
                ->groupBy(fn ($s) => $s->item_type . ':' . $s->item_id);
        }

        $lowStockItems = [];
        if ($branchId) {
            foreach ($reagents as $item) {
                $level = $this->inventoryService->getStockLevel('reagent', $item->id, $branchId);
                if ($level['available'] <= (float) $item->reorder_level) {
                    $lowStockItems[] = ['name' => $item->name, 'type' => 'reagent', 'available' => $level['available']];
                }
            }
            foreach ($consumables as $item) {
                $level = $this->inventoryService->getStockLevel('consumable', $item->id, $branchId);
                if ($level['available'] <= (float) $item->reorder_level) {
                    $lowStockItems[] = ['name' => $item->name, 'type' => 'consumable', 'available' => $level['available']];
                }
            }
        }

        $stats = [
            'reagents' => $reagents->count(),
            'consumables' => $consumables->count(),
            'low_stock' => count($lowStockItems),
        ];

        return view('lab.inventory.index', compact('reagents', 'consumables', 'branchStock', 'branchId', 'stats', 'lowStockItems'));
    }

    public function movements(Request $request)
    {
        $branchId = $this->resolveBranchFilter($request);

        $query = LabInventoryMovement::with(['performer', 'branch'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->filled('item_type') && $request->filled('item_id')) {
            $query->where('item_type', $request->item_type)
                ->where('item_id', (int) $request->item_id);
        }

        $movements = $query->latest()->paginate(30)->withQueryString();

        return view('lab.inventory.movements', compact('movements', 'branchId'));
    }

    protected function resolveBranchFilter(Request $request): ?int
    {
        if (auth()->user()->hasRole('super_admin')) {
            return $request->filled('branch_id') ? (int) $request->branch_id : null;
        }

        return $this->resolveUserBranchId(['view_lab_inventory', 'view_lab_purchases']);
    }
}
