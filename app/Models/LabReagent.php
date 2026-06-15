<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabReagent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'catalog_number',
        'manufacturer',
        'supplier_id',
        'category',
        'subcategory',
        'description',
        'unit_of_measure',
        'current_stock',
        'minimum_stock',
        'maximum_stock',
        'reorder_level',
        'unit_cost',
        'expiry_date',
        'batch_number',
        'storage_requirements',
        'storage_temperature',
        'storage_humidity',
        'light_sensitive',
        'hazardous',
        'safety_notes',
        'usage_instructions',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'current_stock' => 'decimal:2',
        'minimum_stock' => 'decimal:2',
        'maximum_stock' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'storage_temperature' => 'decimal:2',
        'storage_humidity' => 'decimal:2',
        'light_sensitive' => 'boolean',
        'hazardous' => 'boolean',
        'is_active' => 'boolean',
        'storage_requirements' => 'array'
    ];

    /**
     * Get the supplier that provides this reagent.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who created this reagent.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this reagent.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get usage transactions for this reagent.
     */
    public function usageTransactions(): HasMany
    {
        return $this->hasMany(LabInventoryTransaction::class, 'item_id')
            ->where('item_type', 'reagent');
    }

    /**
     * Scope to get active reagents.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get low stock reagents.
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('current_stock <= minimum_stock');
    }

    /**
     * Scope to get expiring soon reagents.
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days));
    }

    /**
     * Scope to get expired reagents.
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    /**
     * Check if reagent is low in stock.
     */
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->minimum_stock;
    }

    /**
     * Check if reagent is expiring soon.
     */
    public function isExpiringSoon($days = 30): bool
    {
        return $this->expiry_date && $this->expiry_date <= now()->addDays($days);
    }

    /**
     * Check if reagent is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date < now();
    }

    /**
     * Check if reagent needs reordering.
     */
    public function needsReorder(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }
}
