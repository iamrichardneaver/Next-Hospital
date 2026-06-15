<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LabInventoryMovement extends Model
{
    protected $fillable = [
        'branch_id',
        'item_type',
        'item_id',
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
        if ($this->item_type === 'reagent') {
            return LabReagent::find($this->item_id)?->name ?? 'Unknown reagent';
        }

        return LabConsumable::find($this->item_id)?->name ?? 'Unknown consumable';
    }

    public function isOutgoing(): bool
    {
        return in_array($this->movement_type, ['test_consumption', 'waste'], true)
            || (float) $this->quantity < 0;
    }
}
