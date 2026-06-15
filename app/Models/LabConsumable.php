<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabConsumable extends Model
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
        'disposable',
        'sterile',
        'single_use',
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
        'disposable' => 'boolean',
        'sterile' => 'boolean',
        'single_use' => 'boolean',
        'is_active' => 'boolean',
        'storage_requirements' => 'array'
    ];

    /**
     * Get the supplier that provides this consumable.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who created this consumable.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this consumable.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get usage transactions for this consumable.
     */
    public function usageTransactions(): HasMany
    {
        return $this->hasMany(LabInventoryTransaction::class, 'item_id')
            ->where('item_type', 'consumable');
    }

    /**
     * Scope to get active consumables.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get low stock consumables.
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('current_stock <= minimum_stock');
    }

    /**
     * Scope to get expiring soon consumables.
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days));
    }

    /**
     * Scope to get expired consumables.
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    /**
     * Check if consumable is low in stock.
     */
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->minimum_stock;
    }

    /**
     * Check if consumable is expiring soon.
     */
    public function isExpiringSoon($days = 30): bool
    {
        return $this->expiry_date && $this->expiry_date <= now()->addDays($days);
    }

    /**
     * Check if consumable is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date < now();
    }

    /**
     * Check if consumable needs reordering.
     */
    public function needsReorder(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }
}
