<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientMedicalHistory extends Model
{
    protected $table = 'patient_medical_history';
    
    protected $fillable = [
        'patient_id',
        'condition',
        'diagnosis_date',
        'status',
        'notes',
        'created_by',
        'updated_by'
    ];
    
    protected $casts = [
        'diagnosis_date' => 'date'
    ];
    
    /**
     * Get the patient that owns this medical history.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
