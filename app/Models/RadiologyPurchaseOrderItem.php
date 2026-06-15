<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyPurchaseOrderItem extends Model
{
    protected $fillable = [
        'radiology_purchase_order_id',
        'radiology_inventory_item_id',
        'quantity_ordered',
        'quantity_received',
        'unit_cost',
        'batch_number',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(RadiologyPurchaseOrder::class, 'radiology_purchase_order_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(RadiologyInventoryItem::class, 'radiology_inventory_item_id');
    }

    public function getItemName(): string
    {
        return $this->inventoryItem?->name ?? RadiologyInventoryItem::find($this->radiology_inventory_item_id)?->name ?? 'Unknown item';
    }

    public function remainingQuantity(): float
    {
        return max(0, (float) $this->quantity_ordered - (float) $this->quantity_received);
    }

    public function isFullyReceived(): bool
    {
        return (float) $this->quantity_received >= (float) $this->quantity_ordered;
    }
}
