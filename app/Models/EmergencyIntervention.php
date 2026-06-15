<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyIntervention extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_visit_id',
        'intervention_type',
        'description',
        'medication_id',
        'dosage',
        'frequency',
        'procedure_code',
        'lab_tests',
        'imaging_type',
        'consultation_specialty',
        'transfer_destination',
        'priority',
        'status',
        'notes',
        'ordered_by',
        'ordered_at'
    ];

    protected $casts = [
        'lab_tests' => 'array',
        'ordered_at' => 'datetime'
    ];

    public function emergencyVisit()
    {
        return $this->belongsTo(EmergencyVisit::class);
    }

    public function medication()
    {
        return $this->belongsTo(Drug::class, 'medication_id');
    }

    public function orderedBy()
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }
}
