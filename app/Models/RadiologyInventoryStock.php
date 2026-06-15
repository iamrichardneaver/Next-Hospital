<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyInventoryStock extends Model
{
    protected $table = 'radiology_inventory_stock';

    protected $fillable = [
        'branch_id',
        'radiology_inventory_item_id',
        'quantity',
        'batch_number',
        'expiry_date',
        'unit_cost',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(RadiologyInventoryItem::class, 'radiology_inventory_item_id');
    }

    public function getItemName(): string
    {
        return $this->item?->name ?? ('Radiology Item #' . $this->radiology_inventory_item_id);
    }
}
