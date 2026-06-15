<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RadiologyEquipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'model',
        'manufacturer',
        'serial_number',
        'modality_id',
        'department_id',
        'installation_date',
        'last_maintenance_date',
        'next_maintenance_date',
        'status',
        'specifications',
        'is_active'
    ];

    protected $casts = [
        'installation_date' => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'specifications' => 'array',
        'is_active' => 'boolean'
    ];

    public function modality(): BelongsTo
    {
        return $this->belongsTo(ImagingModality::class, 'modality_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(RadiologyDepartment::class, 'department_id');
    }

    public function studies(): HasMany
    {
        return $this->hasMany(RadiologyStudy::class, 'equipment_id');
    }

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(RadiologyScheduleSlot::class, 'equipment_id');
    }

    public function qcChecks(): HasMany
    {
        return $this->hasMany(RadiologyQcCheck::class, 'equipment_id');
    }

    public function isOperational(): bool
    {
        return $this->status === 'operational';
    }

    public function needsMaintenance(): bool
    {
        return $this->next_maintenance_date && $this->next_maintenance_date <= now()->addDays(7);
    }
}
