<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientDependent extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'first_name',
        'last_name',
        'relationship',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'address',
        'emergency_contact',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'emergency_contact' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
    
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}

