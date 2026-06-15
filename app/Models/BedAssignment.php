<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasIdPrefix;

class BedAssignment extends Model
{
    use HasIdPrefix;
    protected $fillable = [
        'visit_id',
        'patient_id',
        'bed_id',
        'ward_id',
        'admission_date',
        'discharge_date',
        'assigned_by',
        'status',
        'notes'
    ];

    protected $casts = [
        'admission_date' => 'date',
        'discharge_date' => 'date'
    ];

    /**
     * Get the patient for the bed assignment.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the bed for the bed assignment.
     */
    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    /**
     * Get the user who assigned the bed.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the visit for the bed assignment.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * Get the ward for the bed assignment.
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

}
