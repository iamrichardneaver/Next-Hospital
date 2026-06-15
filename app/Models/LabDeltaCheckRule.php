<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabDeltaCheckRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'parameter_id',
        'rule_name',
        'delta_percentage',
        'delta_absolute',
        'time_window_hours',
        'is_active',
        'description'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'delta_percentage' => 'decimal:2',
        'delta_absolute' => 'decimal:4',
        'time_window_hours' => 'integer'
    ];

    /**
     * Get the parameter that owns this delta check rule.
     */
    public function parameter()
    {
        return $this->belongsTo(LabTestParameter::class, 'parameter_id');
    }

    /**
     * Scope a query to only include active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if a value change triggers a delta check alert.
     */
    public function isDeltaCheckTriggered($currentValue, $previousValue)
    {
        if (!$previousValue || !$currentValue) {
            return false;
        }
        
        $current = floatval($currentValue);
        $previous = floatval($previousValue);
        
        // Check percentage change
        if ($this->delta_percentage) {
            $percentageChange = abs(($current - $previous) / $previous) * 100;
            if ($percentageChange >= $this->delta_percentage) {
                return true;
            }
        }
        
        // Check absolute change
        if ($this->delta_absolute) {
            $absoluteChange = abs($current - $previous);
            if ($absoluteChange >= $this->delta_absolute) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Calculate the delta percentage between two values.
     */
    public function calculateDeltaPercentage($currentValue, $previousValue)
    {
        if (!$previousValue || !$currentValue) {
            return null;
        }
        
        $current = floatval($currentValue);
        $previous = floatval($previousValue);
        
        if ($previous == 0) {
            return null; // Cannot calculate percentage change from zero
        }
        
        return abs(($current - $previous) / $previous) * 100;
    }

    /**
     * Calculate the delta absolute between two values.
     */
    public function calculateDeltaAbsolute($currentValue, $previousValue)
    {
        if (!$previousValue || !$currentValue) {
            return null;
        }
        
        $current = floatval($currentValue);
        $previous = floatval($previousValue);
        
        return abs($current - $previous);
    }

    /**
     * Get the delta check message.
     */
    public function getDeltaCheckMessage($currentValue, $previousValue)
    {
        $deltaPercentage = $this->calculateDeltaPercentage($currentValue, $previousValue);
        $deltaAbsolute = $this->calculateDeltaAbsolute($currentValue, $previousValue);
        
        $message = "Delta Check Alert for {$this->parameter->parameter_name}: ";
        $message .= "Current: {$currentValue}, Previous: {$previousValue}";
        
        if ($deltaPercentage !== null) {
            $message .= " (Change: {$deltaPercentage}%)";
        }
        
        if ($deltaAbsolute !== null) {
            $message .= " (Absolute Change: {$deltaAbsolute})";
        }
        
        return $message;
    }

    /**
     * Get the rule type (percentage or absolute).
     */
    public function getRuleType()
    {
        if ($this->delta_percentage && $this->delta_absolute) {
            return 'both';
        } elseif ($this->delta_percentage) {
            return 'percentage';
        } elseif ($this->delta_absolute) {
            return 'absolute';
        }
        
        return 'none';
    }

    /**
     * Get the rule description with values.
     */
    public function getRuleDescription()
    {
        $description = $this->description ?: "Delta check rule for {$this->parameter->parameter_name}";
        
        if ($this->delta_percentage) {
            $description .= " (Percentage: {$this->delta_percentage}%)";
        }
        
        if ($this->delta_absolute) {
            $description .= " (Absolute: {$this->delta_absolute})";
        }
        
        $description .= " (Time window: {$this->time_window_hours} hours)";
        
        return $description;
    }
}
