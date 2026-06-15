<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasIdPrefix;

class Consultation extends Model
{
    use HasFactory, HasIdPrefix;

    /**
     * The field where the generated ID should be stored
     */
    protected $idField = 'consultation_number';

    protected $fillable = [
        'id',
        'consultation_number',
        'patient_id',
        'doctor_id',
        'visit_id',
        'branch_id',
        'consultation_date',
        'consultation_time',
        'consultation_type',
        'chief_complaint',
        'presenting_complaints',
        'history_of_present_illness',
        'on_direct_questioning',
        'past_medical_history',
        'family_history',
        'social_history',
        'drug_history',
        'allergy_history',
        'past_medical_history_details',
        'past_medical_history_others',
        'current_medications',
        'drug_allergies',
        'past_drug_usage',
        'social_history_details',
        'physical_examination',
        'general_examination',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'pulse_rate',
        'temperature',
        'respiratory_rate',
        'oxygen_saturation',
        'height',
        'weight',
        'bmi',
        'cardiovascular_examination',
        'respiratory_examination',
        'abdominal_examination',
        'neurological_examination',
        'vitals',
        'diagnoses',
        'treatment_plan',
        'consultation_status',
        'is_draft',
        'template_used',
        'subjective',
        'objective',
        'assessment',
        'doctors_impression',
        'plan',
        'icd_10_code',
        'icd_10_codes',
        'severity',
        'urgency',
        'nhis_eligible',
        'requires_referral',
        'referral_specialty',
        'referral_reason',
        'referral_notes',
        'reception_notes',
        'doctor_remarks',
        'medications_prescribed',
        'investigations_ordered',
        'referrals_made',
        'attached_files',
        'follow_up_instructions',
        'next_appointment_date',
        'next_appointment_notes',
        'clinical_notes',
        'workflow_steps',
        'next_stage',
        'started_at',
        'completed_at',
        'created_by',
        'updated_by',
        'billing_status',
        'invoice_id',
        'billing_amount',
        'billed_at',
        'workflow_instance_id',
        'called_at',
        'called_by',
        'cancelled_at',
        'cancellation_reason',
        'completion_notes',
        'completion_type',
        'amended_at',
        'amended_by',
        'amendment_notes',
    ];

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        return null; // Don't generate ID prefix for primary key
    }

    /**
     * Get the field name where the generated ID should be stored
     */
    protected function getIdField()
    {
        return 'consultation_number'; // Generate ID for consultation_number field
    }

    protected $casts = [
        'consultation_date' => 'date',
        'consultation_time' => 'datetime:H:i',
        'presenting_complaints' => 'array',
        'past_medical_history_details' => 'array',
        'current_medications' => 'array',
        'drug_allergies' => 'array',
        'social_history_details' => 'array',
        'physical_examination' => 'array',
        'vitals' => 'array',
        'diagnoses' => 'array',
        'treatment_plan' => 'array',
        'icd_10_codes' => 'array',
        'attached_files' => 'array',
        'workflow_steps' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'amended_at' => 'datetime',
        'next_appointment_date' => 'date',
        'nhis_eligible' => 'boolean',
        'requires_referral' => 'boolean',
        'is_draft' => 'boolean',
        'blood_pressure_systolic' => 'decimal:2',
        'blood_pressure_diastolic' => 'decimal:2',
        'temperature' => 'decimal:2',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'bmi' => 'decimal:2',
        'billing_amount' => 'decimal:2',
        'billed_at' => 'datetime',
        'called_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the patient that owns the consultation.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the doctor that owns the consultation.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the visit that owns the consultation.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * Get the branch that owns the consultation.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the consultation.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the consultation.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who last amended the consultation.
     */
    public function amendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'amended_by');
    }

    /**
     * Get the user who called the consultation.
     */
    public function calledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'called_by');
    }

    /**
     * Get all diagnoses for this consultation.
     */
    public function consultationDiagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class);
    }

    /**
     * Get all vitals for this consultation.
     */
    public function vitals(): HasMany
    {
        return $this->hasMany(Vital::class);
    }

    /**
     * Get all notes for this consultation.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Get all interventions for this consultation.
     */
    public function interventions(): HasMany
    {
        return $this->hasMany(ConsultationIntervention::class);
    }

    /**
     * Get all follow-ups for this consultation.
     */
    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    /**
     * Get all referrals for this consultation.
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    /**
     * Get all prescriptions for this consultation.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Get all lab requests for this consultation.
     */
    public function labRequests(): HasMany
    {
        return $this->hasMany(LabRequest::class);
    }

    /**
     * Get all radiology requests for this consultation.
     */
    public function radiologyRequests(): HasMany
    {
        return $this->hasMany(RadiologyRequest::class);
    }

    /**
     * Get all scans for this consultation.
     */
    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    /**
     * Get the workflow instance for this consultation.
     */
    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    /**
     * Scope to get consultations by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('consultation_status', $status);
    }

    /**
     * Scope to get consultations by date.
     */
    public function scopeByDate($query, $date)
    {
        return $query->where('consultation_date', $date);
    }

    /**
     * Scope to get consultations by doctor.
     */
    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope to get today's consultations.
     */
    public function scopeToday($query)
    {
        return $query->where('consultation_date', now()->toDateString());
    }

    /**
     * Scope to get ongoing consultations.
     */
    public function scopeOngoing($query)
    {
        return $query->where('consultation_status', 'ongoing');
    }

    /**
     * Scope to get draft consultations.
     */
    public function scopeDraft($query)
    {
        return $query->where('is_draft', true);
    }

    /**
     * Scope to get completed consultations.
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_draft', false);
    }

    /**
     * Calculate BMI automatically when height and weight are set.
     */
    public function calculateBmi()
    {
        if ($this->height && $this->weight) {
            $heightInMeters = $this->height / 100; // Convert cm to meters
            $this->bmi = round($this->weight / ($heightInMeters * $heightInMeters), 2);
            return $this->bmi;
        }
        return null;
    }

    /**
     * Get BMI category.
     */
    public function getBmiCategoryAttribute()
    {
        if (!$this->bmi) {
            return 'Unknown';
        }

        if ($this->bmi < 18.5) {
            return 'Underweight';
        } elseif ($this->bmi >= 18.5 && $this->bmi < 25) {
            return 'Normal weight';
        } elseif ($this->bmi >= 25 && $this->bmi < 30) {
            return 'Overweight';
        } else {
            return 'Obese';
        }
    }

    /**
     * Get blood pressure category.
     */
    public function getBloodPressureCategoryAttribute()
    {
        if (!$this->blood_pressure_systolic || !$this->blood_pressure_diastolic) {
            return 'Unknown';
        }

        $systolic = $this->blood_pressure_systolic;
        $diastolic = $this->blood_pressure_diastolic;

        if ($systolic < 120 && $diastolic < 80) {
            return 'Normal';
        } elseif ($systolic < 130 && $diastolic < 80) {
            return 'Elevated';
        } elseif ($systolic < 140 || $diastolic < 90) {
            return 'Stage 1 Hypertension';
        } elseif ($systolic < 180 || $diastolic < 120) {
            return 'Stage 2 Hypertension';
        } else {
            return 'Hypertensive Crisis';
        }
    }

    /**
     * Check if consultation is completed.
     */
    public function isCompleted()
    {
        return $this->consultation_status === 'completed' && !$this->is_draft;
    }

    /**
     * Check if consultation is a draft.
     */
    public function isDraft()
    {
        return $this->is_draft;
    }

    /**
     * Mark consultation as completed.
     */
    public function markAsCompleted()
    {
        $this->update([
            'consultation_status' => 'completed',
            'is_draft' => false,
            'completed_at' => now()
        ]);
    }

    /**
     * Save as draft.
     */
    public function saveAsDraft()
    {
        $this->update(['is_draft' => true]);
    }
}