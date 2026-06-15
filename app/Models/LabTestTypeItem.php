<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabTestTypeItem extends Model
{
    protected $fillable = [
        'lab_test_type_id',
        'item_type',
        'item_id',
        'quantity_per_test',
        'is_optional',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity_per_test' => 'decimal:2',
        'is_optional' => 'boolean',
    ];

    public function testType(): BelongsTo
    {
        return $this->belongsTo(LabTestType::class, 'lab_test_type_id');
    }

    public function getItemName(): string
    {
        if ($this->item_type === 'reagent') {
            return LabReagent::find($this->item_id)?->name ?? 'Unknown reagent';
        }

        return LabConsumable::find($this->item_id)?->name ?? 'Unknown consumable';
    }
}
