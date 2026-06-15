<?php

namespace App\Models;

use App\Traits\HasIdPrefix;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory, HasIdPrefix;

    protected $entityType = 'supplier';

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'website',
        'tax_id',
        'supplier_type',
        'payment_terms',
        'delivery_terms',
        'rating',
        'notes',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rating' => 'decimal:1'
    ];

    /**
     * Get equipment supplied by this supplier.
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(LabEquipment::class);
    }

    /**
     * Get reagents supplied by this supplier.
     */
    public function reagents(): HasMany
    {
        return $this->hasMany(LabReagent::class);
    }

    /**
     * Get consumables supplied by this supplier.
     */
    public function consumables(): HasMany
    {
        return $this->hasMany(LabConsumable::class);
    }

    /**
     * Get inventory transactions with this supplier.
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(LabInventoryTransaction::class);
    }

    /**
     * Scope to get active suppliers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get suppliers by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('supplier_type', $type);
    }

    /**
     * Suppliers eligible for pharmacy purchase orders.
     */
    public function scopeForPharmacy($query)
    {
        return $query->whereIn('supplier_type', ['pharmacy', 'both', 'general']);
    }

    /**
     * Suppliers eligible for laboratory purchase orders.
     */
    public function scopeForLaboratory($query)
    {
        return $query->whereIn('supplier_type', ['laboratory', 'both', 'general', 'equipment', 'reagent', 'consumable']);
    }

    /**
     * Suppliers eligible for radiology purchase orders.
     */
    public function scopeForRadiology($query)
    {
        return $query->whereIn('supplier_type', ['radiology', 'both', 'general']);
    }
}
