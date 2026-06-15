<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasIdPrefix;

class SurgerySchedule extends Model
{
    use HasFactory, HasIdPrefix;

    protected $fillable = [
        'patient_id',
        'surgeon_id',
        'theatre_id',
        'procedure_id',
        'surgery_number',
        'surgery_date',
        'surgery_time',
        'estimated_duration',
        'actual_start_time',
        'actual_end_time',
        'anesthesia_start_time',
        'anesthesia_end_time',
        'incision_time',
        'closure_time',
        'recovery_room_time',
        'priority',
        'surgery_type',
        'anesthesia_type',
        'pre_op_instructions',
        'post_op_instructions',
        'special_requirements',
        'equipment_required',
        'pre_op_checklist',
        'post_op_notes',
        'complications',
        'blood_loss',
        'vital_signs',
        'discharge_instructions',
        'status',
        'notes',
        'started_by',
        'completed_by',
        'created_by',
        'branch_id',
    ];

    protected $casts = [
        'surgery_date' => 'date',
        'surgery_time' => 'datetime',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'anesthesia_start_time' => 'datetime',
        'anesthesia_end_time' => 'datetime',
        'incision_time' => 'datetime',
        'closure_time' => 'datetime',
        'recovery_room_time' => 'datetime',
        'equipment_required' => 'array',
        'pre_op_checklist' => 'array',
        'vital_signs' => 'array'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function surgeon()
    {
        return $this->belongsTo(User::class, 'surgeon_id');
    }

    public function theatre()
    {
        return $this->belongsTo(Theatre::class);
    }

    public function procedure()
    {
        return $this->belongsTo(SurgeryProcedure::class);
    }

    public function team()
    {
        return $this->hasMany(SurgeryTeam::class);
    }

    public function preOpChecklist()
    {
        return $this->hasMany(PreOpChecklist::class);
    }

    public function postOpNotes()
    {
        return $this->hasMany(PostOpNote::class);
    }
}
