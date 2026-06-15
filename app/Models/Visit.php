<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\HasIdPrefix;

class Visit extends Model
{
    use HasFactory, HasIdPrefix;

    /** @see PaymentPolicyService — DB enum values for visits.visit_type */
    public const TYPE_OPD = 'OPD';
    public const TYPE_IPD = 'IPD';
    public const TYPE_EMERGENCY = 'Emergency';
    public const TYPE_LAB_ONLY = 'LabOnly';
    public const TYPE_PHARMACY_ONLY = 'PharmacyOnly';
    public const TYPE_RADIOLOGY_ONLY = 'RadiologyOnly';

    protected $fillable = [
        'id',
        'visit_token',
        'patient_id',
        'branch_id',
        'visit_type',
        'status',
        'check_in_time',
        'check_out_time',
        'assigned_doctor_id',
        'assigned_nurse_id',
        'chief_complaint',
        'visit_notes',
        'vital_signs',
        'priority',
        'referral_source',
        'referral_notes',
        'created_by',
        'updated_by',
        'workflow_instance_id',
    ];

    /**
     * The field where the generated ID should be stored
     */
    protected $idField = 'visit_token';

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        return 'visit';
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($visit) {
            // Generate visit_token using ID prefix service
            if (empty($visit->visit_token)) {
                $visit->visit_token = app(\App\Services\IdPrefixService::class)->generateId('visit');
            }
        });
    }

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'vital_signs' => 'array',
    ];

    /**
     * Get the patient that owns the visit.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the branch that owns the visit.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the assigned doctor for the visit.
     */
    public function assignedDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_doctor_id');
    }

    /**
     * Get the assigned nurse for the visit.
     */
    public function assignedNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_nurse_id');
    }

    /**
     * Get the user who created the visit.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the visit.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all queues for this visit.
     */
    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    /**
     * Get the consultation for this visit.
     */
    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
    }

    /**
     * Get all consultations for this visit.
     */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    /**
     * Get all lab requests for this visit (through consultations).
     */
    public function labRequests(): HasManyThrough
    {
        return $this->hasManyThrough(
            LabRequest::class,
            Consultation::class,
            'visit_id',      // Foreign key on consultations table
            'id',            // Local key on visits table
            'consultation_id', // Foreign key on lab_requests table
            'id'             // Local key on consultations table
        );
    }

    /**
     * Get all prescriptions for this visit (through consultations).
     */
    public function prescriptions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Prescription::class,
            Consultation::class,
            'visit_id',      // Foreign key on consultations table
            'id',            // Local key on visits table
            'consultation_id', // Foreign key on prescriptions table
            'id'             // Local key on consultations table
        );
    }

    /**
     * Get the emergency visit for this visit (if emergency type).
     */
    public function emergencyVisit(): HasOne
    {
        return $this->hasOne(EmergencyVisit::class);
    }

    /**
     * Get the bed assignment for this visit (if IPD type).
     */
    public function bedAssignment(): HasOne
    {
        return $this->hasOne(BedAssignment::class);
    }

    /**
     * Get the workflow instance for this visit.
     */
    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function isInpatient(): bool
    {
        return app(\App\Services\PaymentPolicyService::class)->isInpatient($this);
    }

    public function isOutpatient(): bool
    {
        return app(\App\Services\PaymentPolicyService::class)->isOutpatient($this);
    }

    /**
     * Check if visit is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if visit is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get visit duration in minutes.
     */
    public function getDurationInMinutes(): ?int
    {
        if (!$this->check_out_time) {
            return null;
        }
        
        return $this->check_in_time->diffInMinutes($this->check_out_time);
    }

    /**
     * Scope to get visits by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('visit_type', $type);
    }

    /**
     * Scope to get active visits.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get completed visits.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
