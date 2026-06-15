<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyQcCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_id',
        'technician_id',
        'check_date',
        'check_type',
        'test_results',
        'passed',
        'notes',
        'reviewed_by'
    ];

    protected $casts = [
        'check_date' => 'date',
        'test_results' => 'array',
        'passed' => 'boolean'
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(RadiologyEquipment::class, 'equipment_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(RadiologyTechnician::class, 'technician_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
