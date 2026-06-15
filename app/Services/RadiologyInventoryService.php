<?php

namespace App\Services;

use App\Models\RadiologyInventoryItem;
use App\Models\RadiologyInventoryStock;

class RadiologyInventoryService
{
    /**
     * @return array{available: float, branch_stock: float, catalog_stock: float}
     */
    public function getStockLevel(int $itemId, int $branchId): array
    {
        $branchStock = (float) RadiologyInventoryStock::where('branch_id', $branchId)
            ->where('radiology_inventory_item_id', $itemId)
            ->sum('quantity');

        $catalogStock = (float) (RadiologyInventoryItem::find($itemId)?->current_stock ?? 0);

        return [
            'available' => $branchStock > 0 ? $branchStock : $catalogStock,
            'branch_stock' => $branchStock,
            'catalog_stock' => $catalogStock,
        ];
    }
}
