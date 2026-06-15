<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Traits\HasIdPrefix;

class Teleconsultation extends Model
{
    use HasFactory, HasIdPrefix;


    protected $fillable = [
        'id',
        'teleconsultation_number',
        'uuid',
        'appointment_id',
        'consultation_id',
        'patient_id',
        'doctor_id',
        'branch_id',
        'meeting_id',
        'meeting_password',
        'meeting_url',
        'status',
        'consultation_type',
        'consultation_notes',
        'technical_notes',
        'scheduled_at',
        'started_at',
        'ended_at',
        'duration_minutes',
        'connection_quality',
        'video_enabled',
        'audio_enabled',
        'screen_sharing_enabled',
        'recording_enabled',
        'recording_url',
        'patient_consent_given',
        'consent_given_at',
        'patient_preferences',
        'emergency_contact_notified',
        'emergency_notes',
        'safety_check_completed',
        'requires_follow_up',
        'follow_up_notes',
        'follow_up_scheduled_at',
        'outcome',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'consent_given_at' => 'datetime',
        'follow_up_scheduled_at' => 'datetime',
        'technical_notes' => 'array',
        'patient_preferences' => 'array',
        'video_enabled' => 'boolean',
        'audio_enabled' => 'boolean',
        'screen_sharing_enabled' => 'boolean',
        'recording_enabled' => 'boolean',
        'patient_consent_given' => 'boolean',
        'emergency_contact_notified' => 'boolean',
        'safety_check_completed' => 'boolean',
        'requires_follow_up' => 'boolean',
    ];

    /**
     * The field where the generated ID should be stored
     */
    protected $idField = 'teleconsultation_number';

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        return 'teleconsultation';
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            if (empty($model->meeting_id)) {
                $model->meeting_id = 'TC-' . Str::random(10) . '-' . time();
            }
        });
    }

    /**
     * Get the appointment that owns the teleconsultation.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the consultation that owns the teleconsultation.
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * Get the patient that owns the teleconsultation.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the doctor that owns the teleconsultation.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the branch that owns the teleconsultation.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the teleconsultation.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the teleconsultation.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all chat messages for this teleconsultation.
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(TeleconsultationChat::class);
    }

    /**
     * Get all files shared during this teleconsultation.
     */
    public function sharedFiles(): HasMany
    {
        return $this->hasMany(TeleconsultationFile::class);
    }

    /**
     * Scope to get teleconsultations by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get teleconsultations by doctor.
     */
    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope to get teleconsultations by patient.
     */
    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope to get teleconsultations by branch.
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to get scheduled teleconsultations.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope to get in-progress teleconsultations.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope to get completed teleconsultations.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get today's teleconsultations.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', now()->toDateString());
    }

    /**
     * Scope to get upcoming teleconsultations.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
                    ->whereIn('status', ['scheduled', 'waiting']);
    }

    /**
     * Check if teleconsultation is ready to start.
     */
    public function isReadyToStart(): bool
    {
        return $this->status === 'scheduled' && 
               $this->scheduled_at <= now()->addMinutes(15) && 
               $this->patient_consent_given;
    }

    /**
     * Check if teleconsultation can be started.
     */
    public function canStart(): bool
    {
        return in_array($this->status, ['scheduled', 'waiting']) && 
               $this->patient_consent_given;
    }

    /**
     * Check if teleconsultation is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if teleconsultation is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get the duration in minutes.
     */
    public function getDurationInMinutes(): ?int
    {
        if ($this->started_at && $this->ended_at) {
            return $this->started_at->diffInMinutes($this->ended_at);
        }
        return null;
    }

    /**
     * Start the teleconsultation.
     */
    public function start(): bool
    {
        if (!$this->canStart()) {
            return false;
        }

        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return true;
    }

    /**
     * End the teleconsultation.
     */
    public function end(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $this->update([
            'status' => 'completed',
            'ended_at' => now(),
            'duration_minutes' => $this->getDurationInMinutes(),
        ]);

        return true;
    }

    /**
     * Cancel the teleconsultation.
     */
    public function cancel(): bool
    {
        if ($this->isCompleted()) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
        ]);

        return true;
    }

    /**
     * Get additional attributes for API response.
     */
    protected $appends = ['patient_name', 'patient_phone', 'patient_avatar', 'doctor_name', 'doctor_specialization', 'doctor_avatar', 'has_consent', 'is_video_call_enabled', 'reason'];

    /**
     * Get patient name accessor.
     */
    public function getPatientNameAttribute()
    {
        return $this->patient ? $this->patient->first_name . ' ' . $this->patient->last_name : '';
    }

    /**
     * Get patient phone accessor.
     */
    public function getPatientPhoneAttribute()
    {
        return $this->patient ? $this->patient->phone : null;
    }

    /**
     * Get patient avatar accessor.
     */
    public function getPatientAvatarAttribute()
    {
        return $this->patient && $this->patient->photo ? url('storage/' . $this->patient->photo) : null;
    }

    /**
     * Get doctor name accessor.
     */
    public function getDoctorNameAttribute()
    {
        return $this->doctor ? $this->doctor->first_name . ' ' . $this->doctor->last_name : '';
    }

    /**
     * Get doctor specialization accessor.
     */
    public function getDoctorSpecializationAttribute()
    {
        return $this->doctor && $this->doctor->staff_profile ? $this->doctor->staff_profile->specialization : null;
    }

    /**
     * Get doctor avatar accessor.
     */
    public function getDoctorAvatarAttribute()
    {
        return $this->doctor && $this->doctor->avatar ? url('storage/' . $this->doctor->avatar) : null;
    }

    /**
     * Get has consent accessor.
     */
    public function getHasConsentAttribute()
    {
        return $this->patient_consent_given ?? false;
    }

    /**
     * Get is video call enabled accessor.
     */
    public function getIsVideoCallEnabledAttribute()
    {
        return $this->video_enabled ?? false;
    }

    /**
     * Get reason accessor.
     */
    public function getReasonAttribute()
    {
        return $this->consultation_notes;
    }

    /**
     * Get duration accessor (mobile app compatible).
     */
    public function getDurationAttribute()
    {
        return $this->duration_minutes;
    }

    /**
     * Get meeting code accessor.
     */
    public function getMeetingCodeAttribute()
    {
        return $this->meeting_password;
    }
}
