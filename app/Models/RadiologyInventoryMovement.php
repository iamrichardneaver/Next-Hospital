<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RadiologyInventoryMovement extends Model
{
    protected $fillable = [
        'branch_id',
        'radiology_inventory_item_id',
        'quantity',
        'movement_type',
        'reference_type',
        'reference_id',
        'performed_by',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(RadiologyInventoryItem::class, 'radiology_inventory_item_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    public function getItemName(): string
    {
        return $this->item?->name ?? RadiologyInventoryItem::find($this->radiology_inventory_item_id)?->name ?? 'Unknown item';
    }
}
