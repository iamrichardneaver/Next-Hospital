<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientAllergy extends Model
{
    protected $table = 'patient_allergies';
    
    protected $fillable = [
        'patient_id',
        'allergen',
        'reaction',
        'severity',
        'recorded_at',
        'created_by',
        'updated_by'
    ];
    
    protected $casts = [
        'recorded_at' => 'datetime'
    ];
    
    /**
     * Get the patient that owns this allergy.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
