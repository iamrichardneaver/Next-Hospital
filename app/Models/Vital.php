<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vital extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'pulse_rate',
        'respiratory_rate',
        'temperature',
        'oxygen_saturation',
        'height',
        'weight',
        'bmi',
        'recorded_at',
        'recorded_by'
    ];

    protected $casts = [
        'blood_pressure_systolic' => 'integer',
        'blood_pressure_diastolic' => 'integer',
        'pulse_rate' => 'integer',
        'respiratory_rate' => 'integer',
        'temperature' => 'decimal:1',
        'oxygen_saturation' => 'integer',
        'height' => 'decimal:1',
        'weight' => 'decimal:1',
        'bmi' => 'decimal:1',
        'recorded_at' => 'datetime'
    ];

    /**
     * Get the consultation that owns the vital.
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * Get the patient through the consultation.
     */
    public function patient()
    {
        return $this->hasOneThrough(
            Patient::class,
            Consultation::class,
            'id', // Foreign key on consultations table
            'id', // Foreign key on patients table
            'consultation_id', // Local key on vitals table
            'patient_id' // Local key on consultations table
        );
    }

    /**
     * Get the user who recorded the vital.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
    
    /**
     * Alias for recorder relationship (for backwards compatibility).
     */
    public function recordedBy(): BelongsTo
    {
        return $this->recorder();
    }

    /**
     * Get the formatted blood pressure.
     */
    public function getFormattedBloodPressureAttribute()
    {
        if ($this->blood_pressure_systolic && $this->blood_pressure_diastolic) {
            return $this->blood_pressure_systolic . '/' . $this->blood_pressure_diastolic . ' mmHg';
        }
        return null;
    }

    /**
     * Get the formatted temperature.
     */
    public function getFormattedTemperatureAttribute()
    {
        if ($this->temperature) {
            return $this->temperature . '°C';
        }
        return null;
    }

    /**
     * Get the formatted oxygen saturation.
     */
    public function getFormattedOxygenSaturationAttribute()
    {
        if ($this->oxygen_saturation) {
            return $this->oxygen_saturation . '%';
        }
        return null;
    }

    /**
     * Get the formatted height.
     */
    public function getFormattedHeightAttribute()
    {
        if ($this->height) {
            return $this->height . ' cm';
        }
        return null;
    }

    /**
     * Get the formatted weight.
     */
    public function getFormattedWeightAttribute()
    {
        if ($this->weight) {
            return $this->weight . ' kg';
        }
        return null;
    }

    /**
     * Get the formatted BMI.
     */
    public function getFormattedBmiAttribute()
    {
        if ($this->bmi) {
            return $this->bmi . ' kg/m²';
        }
        return null;
    }

    /**
     * Check if blood pressure is normal.
     */
    public function isBloodPressureNormal()
    {
        if (!$this->blood_pressure_systolic || !$this->blood_pressure_diastolic) {
            return null;
        }

        return $this->blood_pressure_systolic >= 90 && $this->blood_pressure_systolic <= 140 &&
               $this->blood_pressure_diastolic >= 60 && $this->blood_pressure_diastolic <= 90;
    }

    /**
     * Check if pulse rate is normal.
     */
    public function isPulseRateNormal()
    {
        if (!$this->pulse_rate) {
            return null;
        }

        return $this->pulse_rate >= 60 && $this->pulse_rate <= 100;
    }

    /**
     * Check if temperature is normal.
     */
    public function isTemperatureNormal()
    {
        if (!$this->temperature) {
            return null;
        }

        return $this->temperature >= 36.1 && $this->temperature <= 37.2;
    }

    /**
     * Check if oxygen saturation is normal.
     */
    public function isOxygenSaturationNormal()
    {
        if (!$this->oxygen_saturation) {
            return null;
        }

        return $this->oxygen_saturation >= 95;
    }

    /**
     * Check if BMI is normal.
     */
    public function isBmiNormal()
    {
        if (!$this->bmi) {
            return null;
        }

        return $this->bmi >= 18.5 && $this->bmi <= 24.9;
    }

    /**
     * Get BMI category.
     */
    public function getBmiCategory()
    {
        if (!$this->bmi) {
            return null;
        }

        if ($this->bmi < 18.5) {
            return 'Underweight';
        } elseif ($this->bmi >= 18.5 && $this->bmi <= 24.9) {
            return 'Normal';
        } elseif ($this->bmi >= 25 && $this->bmi <= 29.9) {
            return 'Overweight';
        } else {
            return 'Obese';
        }
    }

    /**
     * Calculate BMI from height and weight.
     */
    public static function calculateBmi($height, $weight)
    {
        if (!$height || !$weight || $height <= 0 || $weight <= 0) {
            return null;
        }

        $heightInMeters = $height / 100; // Convert cm to meters
        return round($weight / ($heightInMeters * $heightInMeters), 1);
    }
}
