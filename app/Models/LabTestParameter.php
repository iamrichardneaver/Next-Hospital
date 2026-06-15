<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabTestParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'parameter_code',
        'parameter_name',
        'description',
        'data_type',
        'input_type',
        'input_options',
        'unit',
        'decimal_places',
        'is_required',
        'is_critical',
        'allows_delta_check',
        'validation_rules',
        'reference_ranges',
        'critical_values',
        'flagging_rules',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'input_options' => 'array',
        'validation_rules' => 'array',
        'reference_ranges' => 'array',
        'critical_values' => 'array',
        'flagging_rules' => 'array',
        'is_required' => 'boolean',
        'is_critical' => 'boolean',
        'allows_delta_check' => 'boolean',
        'is_active' => 'boolean',
        'decimal_places' => 'integer',
        'sort_order' => 'integer'
    ];

    /**
     * Get the template that owns this parameter.
     */
    public function template()
    {
        return $this->belongsTo(LabTestTemplate::class, 'template_id');
    }

    /**
     * Get the test results for this parameter.
     */
    public function testResults()
    {
        return $this->hasMany(LabTestResult::class, 'parameter_id');
    }

    /**
     * Get the reference ranges for this parameter.
     */
    public function referenceRanges()
    {
        return $this->hasMany(LabReferenceRange::class, 'parameter_id');
    }

    /**
     * Get the critical values for this parameter.
     */
    public function criticalValues()
    {
        return $this->hasMany(LabCriticalValue::class, 'parameter_id');
    }

    /**
     * Get the quality control records for this parameter.
     */
    public function qualityControl()
    {
        return $this->hasMany(LabQualityControl::class, 'parameter_id');
    }

    /**
     * Get the delta check rules for this parameter.
     */
    public function deltaCheckRules()
    {
        return $this->hasMany(LabDeltaCheckRule::class, 'parameter_id');
    }

    /**
     * Scope a query to only include active parameters.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include required parameters.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope a query to only include critical parameters.
     */
    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    /**
     * Scope a query to filter by data type.
     */
    public function scopeByDataType($query, $dataType)
    {
        return $query->where('data_type', $dataType);
    }

    /**
     * Scope a query to filter by input type.
     */
    public function scopeByInputType($query, $inputType)
    {
        return $query->where('input_type', $inputType);
    }

    /**
     * Get the appropriate reference range for a patient.
     */
    public function getReferenceRange($ageGroup = null, $gender = null, $isPregnant = false, $pregnancyTrimester = null)
    {
        $query = $this->referenceRanges()->active();
        
        if ($ageGroup) {
            $query->where('age_group', $ageGroup);
        }
        
        if ($gender) {
            $query->where(function($q) use ($gender) {
                $q->where('gender', $gender)
                  ->orWhere('gender', 'Both');
            });
        }
        
        if ($isPregnant) {
            $query->where('is_pregnant', true);
            if ($pregnancyTrimester) {
                $query->where('pregnancy_trimester', $pregnancyTrimester);
            }
        } else {
            $query->where('is_pregnant', false);
        }
        
        return $query->first();
    }

    /**
     * Get critical values for a patient.
     */
    public function getCriticalValues($ageGroup = null, $gender = null, $isPregnant = false)
    {
        $query = $this->criticalValues()->active();
        
        if ($ageGroup) {
            $query->where('age_group', $ageGroup);
        }
        
        if ($gender) {
            $query->where(function($q) use ($gender) {
                $q->where('gender', $gender)
                  ->orWhere('gender', 'Both');
            });
        }
        
        if ($isPregnant) {
            $query->where('is_pregnant', true);
        } else {
            $query->where('is_pregnant', false);
        }
        
        return $query->first();
    }

    /**
     * Check if a value is critical.
     */
    public function isCriticalValue($value, $ageGroup = null, $gender = null, $isPregnant = false)
    {
        $criticalValues = $this->getCriticalValues($ageGroup, $gender, $isPregnant);
        
        if (!$criticalValues) {
            return false;
        }
        
        $numericValue = floatval($value);
        
        // Check critical low
        if ($criticalValues->critical_low && $numericValue <= $criticalValues->critical_low) {
            return true;
        }
        
        // Check critical high
        if ($criticalValues->critical_high && $numericValue >= $criticalValues->critical_high) {
            return true;
        }
        
        // Check panic low
        if ($criticalValues->panic_low && $numericValue <= $criticalValues->panic_low) {
            return true;
        }
        
        // Check panic high
        if ($criticalValues->panic_high && $numericValue >= $criticalValues->panic_high) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if a value is within reference range.
     */
    public function isWithinRange($value, $ageGroup = null, $gender = null, $isPregnant = false, $pregnancyTrimester = null)
    {
        $referenceRange = $this->getReferenceRange($ageGroup, $gender, $isPregnant, $pregnancyTrimester);
        
        if (!$referenceRange) {
            return null; // No reference range available
        }
        
        $numericValue = floatval($value);
        
        // Check minimum value
        if ($referenceRange->min_value !== null) {
            $minValue = floatval($referenceRange->min_value);
            if ($referenceRange->min_operator === '>') {
                if ($numericValue <= $minValue) return false;
            } else { // >=
                if ($numericValue < $minValue) return false;
            }
        }
        
        // Check maximum value
        if ($referenceRange->max_value !== null) {
            $maxValue = floatval($referenceRange->max_value);
            if ($referenceRange->max_operator === '<') {
                if ($numericValue >= $maxValue) return false;
            } else { // <=
                if ($numericValue > $maxValue) return false;
            }
        }
        
        return true;
    }

    /**
     * Get the appropriate flag for a value.
     */
    public function getFlag($value, $ageGroup = null, $gender = null, $isPregnant = false, $pregnancyTrimester = null)
    {
        if ($this->isCriticalValue($value, $ageGroup, $gender, $isPregnant)) {
            return 'CRITICAL';
        }
        
        if (!$this->isWithinRange($value, $ageGroup, $gender, $isPregnant, $pregnancyTrimester)) {
            $referenceRange = $this->getReferenceRange($ageGroup, $gender, $isPregnant, $pregnancyTrimester);
            
            if ($referenceRange) {
                $numericValue = floatval($value);
                
                if ($referenceRange->min_value && $numericValue < floatval($referenceRange->min_value)) {
                    return 'L'; // Low
                }
                
                if ($referenceRange->max_value && $numericValue > floatval($referenceRange->max_value)) {
                    return 'H'; // High
                }
            }
        }
        
        return null; // Normal
    }

    /**
     * Format a value according to the parameter's settings.
     */
    public function formatValue($value)
    {
        if ($this->data_type === 'numeric' && $this->decimal_places > 0) {
            return number_format(floatval($value), $this->decimal_places);
        }
        
        return $value;
    }

    /**
     * Get the input options for select/radio/checkbox inputs.
     */
    public function getInputOptions()
    {
        return $this->input_options ?? [];
    }

    /**
     * Check if parameter supports delta checking.
     */
    public function supportsDeltaCheck()
    {
        return $this->allows_delta_check && $this->data_type === 'numeric';
    }

    /**
     * Get the data type display name.
     */
    public function getDataTypeDisplayName()
    {
        return ucfirst($this->data_type);
    }

    /**
     * Get the input type display name.
     */
    public function getInputTypeDisplayName()
    {
        return ucfirst(str_replace('_', ' ', $this->input_type));
    }
}
