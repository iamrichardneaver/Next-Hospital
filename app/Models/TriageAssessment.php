<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TriageAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_visit_id',
        'triage_level',
        'vital_signs',
        'chief_complaint',
        'assessment_notes',
        'assessed_by',
        'assessment_time',
        'reassessment_reason',
        'reassessed_by',
        'reassessment_time'
    ];

    protected $casts = [
        'vital_signs' => 'array',
        'assessment_time' => 'datetime',
        'reassessment_time' => 'datetime'
    ];

    public function emergencyVisit()
    {
        return $this->belongsTo(EmergencyVisit::class);
    }

    public function assessedBy()
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function reassessedBy()
    {
        return $this->belongsTo(User::class, 'reassessed_by');
    }
}
