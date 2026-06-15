<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasIdPrefix;

class EmergencyVisit extends Model
{
    use HasFactory, HasIdPrefix;


    /**
     * The field where the generated ID should be stored
     */
    protected $idField = 'visit_number';

    protected $fillable = [
        'visit_id',
        'patient_id',
        'branch_id',
        'visit_number',
        'arrival_time',
        'chief_complaint',
        'arrival_mode',
        'accompanied_by',
        'referral_source',
        'vital_signs',
        'triage_level',
        'triage_notes',
        'assigned_doctor_id',
        'assigned_nurse_id',
        'priority',
        'status',
        'notes',
        'discharge_time',
        'discharge_diagnosis',
        'discharge_instructions',
        'transfer_destination',
        'transfer_reason',
        'created_by'
    ];

    protected $casts = [
        'vital_signs' => 'array',
        'arrival_time' => 'datetime',
        'discharge_time' => 'datetime'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedDoctor()
    {
        return $this->belongsTo(User::class, 'assigned_doctor_id');
    }

    public function assignedNurse()
    {
        return $this->belongsTo(User::class, 'assigned_nurse_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function triageAssessment()
    {
        return $this->hasOne(TriageAssessment::class);
    }

    public function alerts()
    {
        return $this->hasMany(EmergencyAlert::class);
    }

    public function interventions()
    {
        return $this->hasMany(EmergencyIntervention::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }
}
