<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabReferenceRange extends Model
{
    use HasFactory;

    protected $fillable = [
        'parameter_id',
        'age_group',
        'gender',
        'is_pregnant',
        'pregnancy_trimester',
        'ethnicity',
        'population',
        'min_value',
        'max_value',
        'min_operator',
        'max_operator',
        'unit',
        'notes',
        'source',
        'reference',
        'is_active'
    ];

    protected $casts = [
        'is_pregnant' => 'boolean',
        'is_active' => 'boolean',
        'min_value' => 'decimal:4',
        'max_value' => 'decimal:4'
    ];

    /**
     * Get the parameter that owns this reference range.
     */
    public function parameter()
    {
        return $this->belongsTo(LabTestParameter::class, 'parameter_id');
    }

    /**
     * Scope a query to only include active reference ranges.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by age group.
     */
    public function scopeByAgeGroup($query, $ageGroup)
    {
        return $query->where('age_group', $ageGroup);
    }

    /**
     * Scope a query to filter by gender.
     */
    public function scopeByGender($query, $gender)
    {
        return $query->where(function($q) use ($gender) {
            $q->where('gender', $gender)
              ->orWhere('gender', 'Both');
        });
    }

    /**
     * Scope a query to filter by pregnancy status.
     */
    public function scopeByPregnancyStatus($query, $isPregnant)
    {
        return $query->where('is_pregnant', $isPregnant);
    }

    /**
     * Scope a query to filter by pregnancy trimester.
     */
    public function scopeByPregnancyTrimester($query, $trimester)
    {
        return $query->where('pregnancy_trimester', $trimester);
    }

    /**
     * Get the formatted range string.
     */
    public function getFormattedRange()
    {
        $range = '';
        
        if ($this->min_value !== null) {
            $range .= $this->min_operator . ' ' . $this->min_value;
        }
        
        if ($this->min_value !== null && $this->max_value !== null) {
            $range .= ' - ';
        }
        
        if ($this->max_value !== null) {
            $range .= $this->max_operator . ' ' . $this->max_value;
        }
        
        if ($this->unit) {
            $range .= ' ' . $this->unit;
        }
        
        return $range;
    }

    /**
     * Check if a value is within this reference range.
     */
    public function isValueWithinRange($value)
    {
        $numericValue = floatval($value);
        
        // Check minimum value
        if ($this->min_value !== null) {
            $minValue = floatval($this->min_value);
            if ($this->min_operator === '>') {
                if ($numericValue <= $minValue) return false;
            } else { // >=
                if ($numericValue < $minValue) return false;
            }
        }
        
        // Check maximum value
        if ($this->max_value !== null) {
            $maxValue = floatval($this->max_value);
            if ($this->max_operator === '<') {
                if ($numericValue >= $maxValue) return false;
            } else { // <=
                if ($numericValue > $maxValue) return false;
            }
        }
        
        return true;
    }

    /**
     * Get the age group display name.
     */
    public function getAgeGroupDisplayName()
    {
        return ucfirst(str_replace('_', ' ', $this->age_group));
    }

    /**
     * Get the gender display name.
     */
    public function getGenderDisplayName()
    {
        return ucfirst($this->gender);
    }

    /**
     * Get the pregnancy status display name.
     */
    public function getPregnancyStatusDisplayName()
    {
        if ($this->is_pregnant) {
            return 'Pregnant' . ($this->pregnancy_trimester ? ' (' . ucfirst($this->pregnancy_trimester) . ' Trimester)' : '');
        }
        
        return 'Non-pregnant';
    }
}
