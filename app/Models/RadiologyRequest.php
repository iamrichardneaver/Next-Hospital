<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\HasIdPrefix;

class RadiologyRequest extends Model
{
    use HasFactory, HasIdPrefix;

    protected $fillable = [
        'request_number',
        'patient_id',
        'doctor_id',
        'consultation_id',
        'branch_id',
        'modality_id',
        'department_id',
        'clinical_history',
        'clinical_question',
        'indication',
        'priority',
        'status',
        'requested_date',
        'scheduled_date',
        'scheduled_time',
        'technician_id',
        'radiologist_id',
        'technician_notes',
        'rejection_reason',
        'billing_status',
        'invoice_id',
        'billing_amount',
        'billed_at'
    ];

    protected $casts = [
        'requested_date' => 'date',
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime:H:i',
        'billing_amount' => 'decimal:2',
        'billed_at' => 'datetime'
    ];

    /**
     * Override the ID field to use request_number instead of id
     * The id column is auto-incrementing, but we want the custom ID in request_number
     */
    protected function getIdField()
    {
        return 'request_number';
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function modality(): BelongsTo
    {
        return $this->belongsTo(ImagingModality::class, 'modality_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(RadiologyDepartment::class, 'department_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(RadiologyTechnician::class, 'technician_id');
    }

    public function radiologist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'radiologist_id');
    }

    public function study(): HasOne
    {
        return $this->hasOne(RadiologyStudy::class, 'request_id');
    }

    public function studies(): HasMany
    {
        return $this->hasMany(RadiologyStudy::class, 'request_id');
    }

    public function reports(): HasManyThrough
    {
        return $this->hasManyThrough(RadiologyReport::class, RadiologyStudy::class, 'request_id', 'study_id');
    }

    /**
     * Modality name used by billing/cashier integrations.
     */
    public function getStudyTypeAttribute(): ?string
    {
        return $this->modality?->name ?? $this->modality?->code;
    }

    public function isUrgent(): bool
    {
        return in_array($this->priority, ['urgent', 'stat', 'emergency']);
    }

    public function isOverdue(): bool
    {
        return $this->scheduled_date && $this->scheduled_date < now()->toDateString() && $this->status !== 'completed';
    }

    public function canBeScheduled(): bool
    {
        return $this->status === 'requested';
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'in_progress';
    }
}
