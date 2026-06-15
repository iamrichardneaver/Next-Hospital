<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabEquipmentMaintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_equipment_id',
        'maintenance_type',
        'maintenance_date',
        'next_maintenance_date',
        'performed_by',
        'service_provider',
        'description',
        'issues_found',
        'actions_taken',
        'parts_replaced',
        'cost',
        'status',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'cost' => 'decimal:2',
        'issues_found' => 'array',
        'actions_taken' => 'array',
        'parts_replaced' => 'array'
    ];

    /**
     * Get the equipment that this maintenance record belongs to.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(LabEquipment::class);
    }

    /**
     * Get the user who performed the maintenance.
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the user who created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get scheduled maintenance.
     */
    public function scopeScheduled($query)
    {
        return $query->where('maintenance_type', 'scheduled');
    }

    /**
     * Scope to get emergency maintenance.
     */
    public function scopeEmergency($query)
    {
        return $query->where('maintenance_type', 'emergency');
    }

    /**
     * Scope to get preventive maintenance.
     */
    public function scopePreventive($query)
    {
        return $query->where('maintenance_type', 'preventive');
    }

    /**
     * Scope to get completed maintenance.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get pending maintenance.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
