<?php

namespace App\Services;

use App\Models\DrugStock;
use App\Models\StoreItem;
use Illuminate\Support\Facades\Log;

class StoreInventoryService
{
    /**
     * Effective sellable quantity (store item stock capped by linked pharmacy stock at branch).
     */
    public function getAvailableQuantity(StoreItem $item, ?int $branchId = null): int
    {
        $qty = (int) $item->stock_quantity;

        if ($item->drug_id && $branchId) {
            $drugStock = DrugStock::where('drug_id', $item->drug_id)
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->first();

            if ($drugStock) {
                $qty = min($qty, (int) $drugStock->current_stock);
            }
        }

        return max(0, $qty);
    }

    /**
     * Deduct stock for a store order line item.
     */
    public function deductStock(StoreItem $item, int $quantity, ?int $branchId = null): void
    {
        StoreItem::where('id', $item->id)->decrement('stock_quantity', $quantity);

        if ($item->drug_id && $branchId) {
            $drugStock = DrugStock::where('drug_id', $item->drug_id)
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->first();

            if ($drugStock) {
                if (!$drugStock->decrementStock($quantity)) {
                    Log::warning('Store order: pharmacy stock below ordered quantity after store deduction', [
                        'store_item_id' => $item->id,
                        'drug_id' => $item->drug_id,
                        'branch_id' => $branchId,
                        'quantity' => $quantity,
                    ]);
                }
            }
        }
    }

    /**
     * Restore stock when an order is cancelled.
     */
    public function restoreStock(StoreItem $item, int $quantity, ?int $branchId = null): void
    {
        StoreItem::where('id', $item->id)->increment('stock_quantity', $quantity);

        if ($item->drug_id && $branchId) {
            $drugStock = DrugStock::where('drug_id', $item->drug_id)
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->first();

            if ($drugStock) {
                $drugStock->incrementStock($quantity);
            }
        }
    }
}
