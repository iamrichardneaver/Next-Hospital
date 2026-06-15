<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasIdPrefix;


class Appointment extends Model
{
    use HasFactory, HasIdPrefix;

    protected $entityType = 'appointment';
    protected $idField = 'appointment_number';

    protected $fillable = [
        'id',
        'appointment_number',
        'patient_id',
        'doctor_id',
        'branch_id',
        'appointment_date',
        'appointment_time',
        'reason',
        'status',
        'billing_status',
        'appointment_type',
        'notes',
        'is_teleconsultation',
        'teleconsultation_id',
        'meeting_url',
        'meeting_password',
        'slot_id',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        return 'appointment';
    }

    /**
     * The field where the generated ID should be stored
     */
    protected function getIdField()
    {
        return 'appointment_number';
    }

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'datetime:H:i',
        'is_teleconsultation' => 'boolean',
    ];

    /**
     * Attributes appended to array/JSON when slot is loaded. Ensures mobile gets fee/currency for slot-based appointments.
     */
    protected $appends = ['fee', 'currency'];

    /**
     * Fee from linked slot (when slot relation is loaded). Used by API so mobile can show correct in-person vs teleconsultation fee.
     */
    public function getFeeAttribute(): ?float
    {
        if (!$this->relationLoaded('slot') || !$this->slot) {
            return null;
        }
        $fee = $this->slot->fee;
        return $fee !== null && $fee !== '' ? (float) $fee : null;
    }

    /**
     * Currency from linked slot (when slot relation is loaded).
     */
    public function getCurrencyAttribute(): ?string
    {
        if (!$this->relationLoaded('slot') || !$this->slot) {
            return null;
        }
        return $this->slot->currency ?? 'GHS';
    }

    /**
     * Get the patient that owns the appointment.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the doctor that owns the appointment.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the branch that owns the appointment.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the appointment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the appointment.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the teleconsultation associated with this appointment.
     */
    public function teleconsultation(): BelongsTo
    {
        return $this->belongsTo(Teleconsultation::class);
    }

    /**
     * Get the appointment slot associated with this appointment.
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(AppointmentSlot::class, 'slot_id');
    }

    public function doctorReview(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DoctorReview::class);
    }

    /**
     * Scope to get appointments by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get appointments by date.
     */
    public function scopeByDate($query, $date)
    {
        return $query->where('appointment_date', $date);
    }

    /**
     * Scope to get appointments by doctor.
     */
    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope to get upcoming appointments.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now()->toDateString())
                    ->where('status', 'scheduled');
    }

    /**
     * Scope to get today's appointments.
     */
    public function scopeToday($query)
    {
        return $query->where('appointment_date', now()->toDateString());
    }
}