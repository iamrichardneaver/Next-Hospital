<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabEquipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'model',
        'manufacturer',
        'serial_number',
        'equipment_type',
        'location',
        'department',
        'installation_date',
        'last_maintenance_date',
        'next_maintenance_date',
        'status',
        'specifications',
        'warranty_expiry',
        'supplier_id',
        'purchase_date',
        'purchase_cost',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'installation_date' => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'warranty_expiry' => 'date',
        'purchase_date' => 'date',
        'specifications' => 'array',
        'is_active' => 'boolean',
        'purchase_cost' => 'decimal:2'
    ];

    /**
     * Get the supplier that owns the equipment.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who created the equipment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the equipment.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get maintenance records for this equipment.
     */
    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(LabEquipmentMaintenance::class);
    }

    /**
     * Scope to get active equipment.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get operational equipment.
     */
    public function scopeOperational($query)
    {
        return $query->where('status', 'operational');
    }

    /**
     * Scope to get equipment needing maintenance.
     */
    public function scopeNeedsMaintenance($query)
    {
        return $query->where('next_maintenance_date', '<=', now()->addDays(7));
    }

    /**
     * Check if equipment is operational.
     */
    public function isOperational(): bool
    {
        return $this->status === 'operational';
    }

    /**
     * Check if equipment needs maintenance.
     */
    public function checkNeedsMaintenance(): bool
    {
        return $this->next_maintenance_date && $this->next_maintenance_date <= now()->addDays(7);
    }

    /**
     * Check if equipment is under warranty.
     */
    public function isUnderWarranty(): bool
    {
        return $this->warranty_expiry && $this->warranty_expiry > now();
    }
}
