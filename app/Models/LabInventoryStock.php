<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabInventoryStock extends Model
{
    protected $table = 'lab_inventory_stock';

    protected $fillable = [
        'branch_id',
        'item_type',
        'item_id',
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

    public function getItemName(): string
    {
        if ($this->item_type === 'reagent') {
            return LabReagent::find($this->item_id)?->name ?? 'Unknown reagent';
        }

        return LabConsumable::find($this->item_id)?->name ?? 'Unknown consumable';
    }
}
