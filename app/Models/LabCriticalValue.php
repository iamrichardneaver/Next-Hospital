<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabCriticalValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'parameter_id',
        'age_group',
        'gender',
        'is_pregnant',
        'critical_low',
        'critical_high',
        'panic_low',
        'panic_high',
        'unit',
        'alert_message',
        'notification_recipients',
        'escalation_time_minutes',
        'is_active'
    ];

    protected $casts = [
        'is_pregnant' => 'boolean',
        'is_active' => 'boolean',
        'critical_low' => 'decimal:4',
        'critical_high' => 'decimal:4',
        'panic_low' => 'decimal:4',
        'panic_high' => 'decimal:4',
        'notification_recipients' => 'array',
        'escalation_time_minutes' => 'integer'
    ];

    /**
     * Get the parameter that owns this critical value.
     */
    public function parameter()
    {
        return $this->belongsTo(LabTestParameter::class, 'parameter_id');
    }

    /**
     * Scope a query to only include active critical values.
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
     * Check if a value is critical.
     */
    public function isCriticalValue($value)
    {
        $numericValue = floatval($value);
        
        // Check critical low
        if ($this->critical_low && $numericValue <= $this->critical_low) {
            return true;
        }
        
        // Check critical high
        if ($this->critical_high && $numericValue >= $this->critical_high) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if a value is panic.
     */
    public function isPanicValue($value)
    {
        $numericValue = floatval($value);
        
        // Check panic low
        if ($this->panic_low && $numericValue <= $this->panic_low) {
            return true;
        }
        
        // Check panic high
        if ($this->panic_high && $numericValue >= $this->panic_high) {
            return true;
        }
        
        return false;
    }

    /**
     * Get the critical level for a value.
     */
    public function getCriticalLevel($value)
    {
        if ($this->isPanicValue($value)) {
            return 'panic';
        }
        
        if ($this->isCriticalValue($value)) {
            return 'critical';
        }
        
        return 'normal';
    }

    /**
     * Get the appropriate flag for a value.
     */
    public function getFlag($value)
    {
        if ($this->isPanicValue($value)) {
            return 'PANIC';
        }
        
        if ($this->isCriticalValue($value)) {
            return 'CRITICAL';
        }
        
        return null;
    }

    /**
     * Get the alert message for a value.
     */
    public function getAlertMessage($value)
    {
        $level = $this->getCriticalLevel($value);
        
        if ($level === 'panic') {
            return $this->alert_message ?: 'PANIC VALUE: ' . $value . ' ' . $this->unit;
        }
        
        if ($level === 'critical') {
            return $this->alert_message ?: 'CRITICAL VALUE: ' . $value . ' ' . $this->unit;
        }
        
        return null;
    }

    /**
     * Get the notification recipients.
     */
    public function getNotificationRecipients()
    {
        return $this->notification_recipients ?? [];
    }

    /**
     * Get the escalation time in minutes.
     */
    public function getEscalationTimeMinutes()
    {
        return $this->escalation_time_minutes ?? 15;
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
        return $this->is_pregnant ? 'Pregnant' : 'Non-pregnant';
    }
}
