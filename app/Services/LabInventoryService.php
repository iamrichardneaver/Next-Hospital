<?php

namespace App\Services;

use App\Models\LabConsumable;
use App\Models\LabInventoryMovement;
use App\Models\LabInventoryStock;
use App\Models\LabInventoryTransaction;
use App\Models\LabReagent;
use App\Models\LabRequest;
use App\Models\LabTestType;
use App\Models\LabTestTypeItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LabInventoryService
{
    /**
     * @return array{available: float, branch_stock: float, catalog_stock: float}
     */
    public function getStockLevel(string $itemType, int $itemId, int $branchId): array
    {
        $branchStock = (float) LabInventoryStock::where('branch_id', $branchId)
            ->where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->sum('quantity');

        $catalogStock = $this->getCatalogStock($itemType, $itemId);

        return [
            'available' => $branchStock > 0 ? $branchStock : $catalogStock,
            'branch_stock' => $branchStock,
            'catalog_stock' => $catalogStock,
        ];
    }

    /**
     * @return array{sufficient: bool, shortages: array<int, array<string, mixed>>}
     */
    public function canFulfillTest(int $labTestTypeId, int $branchId): array
    {
        $requirements = $this->getRequirementsForTestTypes([$labTestTypeId]);
        $shortages = [];

        foreach ($requirements as $key => $req) {
            $stock = $this->getStockLevel($req['item_type'], $req['item_id'], $branchId);
            if ($req['is_optional']) {
                continue;
            }
            if ($stock['available'] < $req['quantity']) {
                $shortages[] = [
                    'item_type' => $req['item_type'],
                    'item_id' => $req['item_id'],
                    'item_name' => $req['item_name'],
                    'required' => $req['quantity'],
                    'available' => $stock['available'],
                ];
            }
        }

        return [
            'sufficient' => $shortages === [],
            'shortages' => $shortages,
        ];
    }

    /**
     * Deduct inventory for a completed lab request (idempotent).
     *
     * @return array{deducted: bool, warnings: array<string>}
     */
    public function deductForLabRequest(LabRequest $request, ?int $performedBy = null): array
    {
        if ($request->inventory_deducted_at) {
            return ['deducted' => false, 'warnings' => []];
        }

        $branchId = (int) $request->branch_id;
        if (!$branchId) {
            return ['deducted' => false, 'warnings' => ['Lab request has no branch — inventory not deducted.']];
        }

        $testTypeIds = $this->resolveTestTypeIds($request);
        if ($testTypeIds === []) {
            return ['deducted' => false, 'warnings' => []];
        }

        $requirements = $this->getRequirementsForTestTypes($testTypeIds);
        if ($requirements->isEmpty()) {
            return ['deducted' => false, 'warnings' => []];
        }

        $performedBy = $performedBy ?? (int) ($request->technician_id ?? auth()->id() ?? $request->updated_by ?? $request->created_by);
        $warnings = [];

        DB::transaction(function () use ($request, $branchId, $requirements, $performedBy, &$warnings) {
            $fresh = LabRequest::lockForUpdate()->find($request->id);
            if ($fresh->inventory_deducted_at) {
                return;
            }

            foreach ($requirements as $req) {
                $stock = $this->getStockLevel($req['item_type'], $req['item_id'], $branchId);
                if (!$req['is_optional'] && $stock['available'] < $req['quantity']) {
                    $warnings[] = "Low stock: {$req['item_name']} needs {$req['quantity']}, available {$stock['available']}.";
                    Log::warning('Lab inventory shortage on test consumption', [
                        'lab_request_id' => $request->id,
                        'item_type' => $req['item_type'],
                        'item_id' => $req['item_id'],
                        'required' => $req['quantity'],
                        'available' => $stock['available'],
                    ]);
                }

                $this->deductStock(
                    $req['item_type'],
                    $req['item_id'],
                    $branchId,
                    (float) $req['quantity'],
                    $performedBy
                );

                LabInventoryMovement::create([
                    'branch_id' => $branchId,
                    'item_type' => $req['item_type'],
                    'item_id' => $req['item_id'],
                    'quantity' => -abs((float) $req['quantity']),
                    'movement_type' => 'test_consumption',
                    'reference_type' => LabRequest::class,
                    'reference_id' => $request->id,
                    'performed_by' => $performedBy,
                    'notes' => "Consumed for lab request {$request->lab_request_number}",
                ]);

                LabInventoryTransaction::create([
                    'item_id' => $req['item_id'],
                    'item_type' => $req['item_type'],
                    'transaction_type' => 'usage',
                    'quantity' => $req['quantity'],
                    'reference_number' => $request->lab_request_number,
                    'location' => "branch:{$branchId}",
                    'notes' => 'Auto-deducted on test completion',
                    'transaction_date' => now()->toDateString(),
                    'created_by' => $performedBy,
                ]);
            }

            $fresh->update(['inventory_deducted_at' => now()]);
        });

        return ['deducted' => true, 'warnings' => $warnings];
    }

    /**
     * Reverse a prior deduction when a request is cancelled/voided.
     */
    public function reverseDeduction(LabRequest $request, ?int $performedBy = null): bool
    {
        if (!$request->inventory_deducted_at) {
            return false;
        }

        $branchId = (int) $request->branch_id;
        $performedBy = $performedBy ?? (int) (auth()->id() ?? $request->updated_by ?? $request->created_by);

        $movements = LabInventoryMovement::where('reference_type', LabRequest::class)
            ->where('reference_id', $request->id)
            ->where('movement_type', 'test_consumption')
            ->get();

        if ($movements->isEmpty()) {
            $request->update(['inventory_deducted_at' => null]);
            return false;
        }

        DB::transaction(function () use ($request, $branchId, $movements, $performedBy) {
            foreach ($movements as $movement) {
                $qty = abs((float) $movement->quantity);

                $this->restoreStock(
                    $movement->item_type,
                    (int) $movement->item_id,
                    $branchId,
                    $qty,
                    $performedBy
                );

                LabInventoryMovement::create([
                    'branch_id' => $branchId,
                    'item_type' => $movement->item_type,
                    'item_id' => $movement->item_id,
                    'quantity' => $qty,
                    'movement_type' => 'consumption_reversal',
                    'reference_type' => LabRequest::class,
                    'reference_id' => $request->id,
                    'performed_by' => $performedBy,
                    'notes' => "Reversed consumption for cancelled request {$request->lab_request_number}",
                ]);
            }

            $request->update(['inventory_deducted_at' => null]);
        });

        return true;
    }

    /**
     * @return list<int>
     */
    public function resolveTestTypeIds(LabRequest $request): array
    {
        if ($request->test_type_id) {
            return [(int) $request->test_type_id];
        }

        $templateIds = $request->templates()->pluck('lab_test_templates.id')->all();
        if ($request->template_id) {
            $templateIds[] = (int) $request->template_id;
        }
        $templateIds = array_values(array_unique(array_filter($templateIds)));

        if ($templateIds !== []) {
            return LabTestType::whereIn('template_id', $templateIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        return [];
    }

    /**
     * @param  list<int>  $testTypeIds
     */
    protected function getRequirementsForTestTypes(array $testTypeIds): Collection
    {
        $items = LabTestTypeItem::whereIn('lab_test_type_id', $testTypeIds)->get();

        return $items->groupBy(fn ($item) => $item->item_type . ':' . $item->item_id)
            ->map(function (Collection $group) {
                $first = $group->first();
                return [
                    'item_type' => $first->item_type,
                    'item_id' => (int) $first->item_id,
                    'item_name' => $first->getItemName(),
                    'quantity' => (float) $group->sum('quantity_per_test'),
                    'is_optional' => $group->every(fn ($i) => $i->is_optional),
                ];
            })
            ->values();
    }

    protected function deductStock(string $itemType, int $itemId, int $branchId, float $quantity, int $userId): void
    {
        $remaining = $quantity;

        $stocks = LabInventoryStock::where('branch_id', $branchId)
            ->where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($stocks as $stock) {
            if ($remaining <= 0) {
                break;
            }
            $deduct = min((float) $stock->quantity, $remaining);
            $stock->update([
                'quantity' => (float) $stock->quantity - $deduct,
                'updated_by' => $userId,
            ]);
            $remaining -= $deduct;
        }

        $this->decrementCatalogStock($itemType, $itemId, $quantity, $userId);
    }

    protected function restoreStock(string $itemType, int $itemId, int $branchId, float $quantity, int $userId): void
    {
        $stock = LabInventoryStock::where('branch_id', $branchId)
            ->where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->whereNull('batch_number')
            ->first();

        if ($stock) {
            $stock->update([
                'quantity' => (float) $stock->quantity + $quantity,
                'updated_by' => $userId,
            ]);
        } else {
            LabInventoryStock::create([
                'branch_id' => $branchId,
                'item_type' => $itemType,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'created_by' => $userId,
            ]);
        }

        $this->incrementCatalogStock($itemType, $itemId, $quantity, $userId);
    }

    protected function decrementCatalogStock(string $itemType, int $itemId, float $quantity, int $userId): void
    {
        $item = $itemType === 'reagent'
            ? LabReagent::find($itemId)
            : LabConsumable::find($itemId);

        if ($item) {
            $item->update([
                'current_stock' => max(0, (float) $item->current_stock - $quantity),
                'updated_by' => $userId,
            ]);
        }
    }

    protected function incrementCatalogStock(string $itemType, int $itemId, float $quantity, int $userId): void
    {
        $item = $itemType === 'reagent'
            ? LabReagent::find($itemId)
            : LabConsumable::find($itemId);

        if ($item) {
            $item->update([
                'current_stock' => (float) $item->current_stock + $quantity,
                'updated_by' => $userId,
            ]);
        }
    }

    protected function getCatalogStock(string $itemType, int $itemId): float
    {
        if ($itemType === 'reagent') {
            return (float) (LabReagent::find($itemId)?->current_stock ?? 0);
        }

        return (float) (LabConsumable::find($itemId)?->current_stock ?? 0);
    }
}
