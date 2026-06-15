<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabPurchaseOrderItem extends Model
{
    protected $fillable = [
        'lab_purchase_order_id',
        'item_type',
        'item_id',
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
        return $this->belongsTo(LabPurchaseOrder::class, 'lab_purchase_order_id');
    }

    public function getItemName(): string
    {
        if ($this->item_type === 'reagent') {
            return LabReagent::find($this->item_id)?->name ?? 'Unknown reagent';
        }

        return LabConsumable::find($this->item_id)?->name ?? 'Unknown consumable';
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
