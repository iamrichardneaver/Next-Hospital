<?php

namespace App\Services;

use App\Models\Drug;
use App\Models\DrugStock;
use App\Models\LabInventoryStock;
use App\Models\RadiologyInventoryStock;
use App\Models\StockCount;
use App\Models\StockCountItem;
use Illuminate\Support\Facades\DB;

class StockCountService
{
    public function createDraft(int $branchId, string $department, int $userId, ?string $notes = null): StockCount
    {
        return DB::transaction(function () use ($branchId, $department, $userId, $notes) {
            $stockCount = StockCount::create([
                'branch_id' => $branchId,
                'department' => $department,
                'counted_by' => $userId,
                'status' => 'draft',
                'notes' => $notes,
            ]);

            $this->seedItemsFromSystem($stockCount);

            return $stockCount->load(['items', 'counter', 'branch']);
        });
    }

    public function seedItemsFromSystem(StockCount $stockCount): void
    {
        $stockCount->items()->delete();

        if ($stockCount->department === 'pharmacy') {
            $stocks = DrugStock::with('drug')
                ->where('branch_id', $stockCount->branch_id)
                ->where('is_active', true)
                ->get();

            foreach ($stocks as $stock) {
                StockCountItem::create([
                    'stock_count_id' => $stockCount->id,
                    'item_id' => $stock->drug_id,
                    'item_type' => Drug::class,
                    'item_name' => $stock->drug?->name ?? 'Drug #' . $stock->drug_id,
                    'system_qty' => $stock->current_stock,
                ]);
            }

            return;
        }

        if ($stockCount->department === 'lab') {
            $stocks = LabInventoryStock::query()
                ->where('branch_id', $stockCount->branch_id)
                ->get();

            foreach ($stocks as $stock) {
                StockCountItem::create([
                    'stock_count_id' => $stockCount->id,
                    'item_id' => $stock->id,
                    'item_type' => LabInventoryStock::class,
                    'item_name' => method_exists($stock, 'getItemName') ? $stock->getItemName() : ('Lab Item #' . $stock->id),
                    'system_qty' => $stock->quantity ?? 0,
                ]);
            }

            return;
        }

        if ($stockCount->department === 'radiology') {
            $stocks = RadiologyInventoryStock::with('item')
                ->where('branch_id', $stockCount->branch_id)
                ->get();

            foreach ($stocks as $stock) {
                StockCountItem::create([
                    'stock_count_id' => $stockCount->id,
                    'item_id' => $stock->radiology_inventory_item_id,
                    'item_type' => RadiologyInventoryStock::class,
                    'item_name' => $stock->getItemName(),
                    'system_qty' => $stock->quantity ?? 0,
                ]);
            }
        }
    }

    public function updateItemCounts(StockCount $stockCount, array $items): StockCount
    {
        if ($stockCount->status !== 'draft') {
            throw new \RuntimeException('Only draft stock counts can be edited.');
        }

        foreach ($items as $itemData) {
            $item = $stockCount->items()->where('id', $itemData['id'])->first();
            if (!$item) {
                continue;
            }

            $countedQty = $itemData['counted_qty'];
            $item->update([
                'counted_qty' => $countedQty,
                'variance' => $countedQty !== null ? ($countedQty - $item->system_qty) : null,
            ]);
        }

        return $stockCount->fresh(['items']);
    }

    public function complete(StockCount $stockCount): StockCount
    {
        if ($stockCount->status !== 'draft') {
            throw new \RuntimeException('Stock count is already completed.');
        }

        $incomplete = $stockCount->items()->whereNull('counted_qty')->exists();
        if ($incomplete) {
            throw new \RuntimeException('All items must have counted quantities before submission.');
        }

        $stockCount->update([
            'status' => 'completed',
            'counted_at' => now(),
        ]);

        return $stockCount->fresh(['items', 'counter', 'branch']);
    }

    public function varianceSummary(StockCount $stockCount): array
    {
        $items = $stockCount->items;

        return [
            'total_items' => $items->count(),
            'counted_items' => $items->whereNotNull('counted_qty')->count(),
            'variance_items' => $items->filter(fn ($i) => $i->variance !== null && (float) $i->variance !== 0.0)->count(),
            'total_variance' => $items->sum('variance'),
            'positive_variance' => $items->filter(fn ($i) => $i->variance > 0)->sum('variance'),
            'negative_variance' => $items->filter(fn ($i) => $i->variance < 0)->sum('variance'),
        ];
    }
}
