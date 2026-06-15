<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IcuLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'visit_id',
        'bed_id',
        'admission_time',
        'discharge_time',
        'admission_type',
        'admission_diagnosis',
        'chief_complaint',
        'temperature',
        'heart_rate',
        'respiratory_rate',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'oxygen_saturation',
        'glucose_level',
        'on_ventilator',
        'ventilator_mode',
        'ventilator_rate',
        'fio2',
        'peep',
        'on_dialysis',
        'dialysis_type',
        'on_vasopressors',
        'vasopressor_details',
        'fluid_intake',
        'fluid_output',
        'fluid_balance',
        'gcs_eye',
        'gcs_verbal',
        'gcs_motor',
        'gcs_total',
        'medications',
        'procedures_performed',
        'interventions',
        'nursing_notes',
        'doctor_notes',
        'progress_notes',
        'attending_doctor_id',
        'assigned_nurse_id',
        'recorded_by',
        'patient_condition',
        'status',
        'discharge_notes',
        'discharge_destination',
        'branch_id',
        'recorded_at',
    ];

    protected $casts = [
        'admission_time' => 'datetime',
        'discharge_time' => 'datetime',
        'temperature' => 'decimal:2',
        'glucose_level' => 'decimal:2',
        'fluid_balance' => 'decimal:2',
        'on_ventilator' => 'boolean',
        'on_dialysis' => 'boolean',
        'on_vasopressors' => 'boolean',
        'fluid_intake' => 'array',
        'fluid_output' => 'array',
        'medications' => 'array',
        'procedures_performed' => 'array',
        'interventions' => 'array',
        'recorded_at' => 'datetime',
    ];

    // Relationships
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function attendingDoctor()
    {
        return $this->belongsTo(User::class, 'attending_doctor_id');
    }

    public function assignedNurse()
    {
        return $this->belongsTo(User::class, 'assigned_nurse_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCritical($query)
    {
        return $query->where('patient_condition', 'critical');
    }

    public function scopeOnVentilator($query)
    {
        return $query->where('on_ventilator', true);
    }

    // Accessors
    public function getBloodPressureAttribute()
    {
        if ($this->blood_pressure_systolic && $this->blood_pressure_diastolic) {
            return "{$this->blood_pressure_systolic}/{$this->blood_pressure_diastolic}";
        }
        return null;
    }

    public function getLengthOfStayHoursAttribute()
    {
        $endTime = $this->discharge_time ?? now();
        return $this->admission_time->diffInHours($endTime);
    }

    public function getGcsScoreAttribute()
    {
        return ($this->gcs_eye ?? 0) + ($this->gcs_verbal ?? 0) + ($this->gcs_motor ?? 0);
    }
}

