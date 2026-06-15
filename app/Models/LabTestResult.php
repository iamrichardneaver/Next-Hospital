<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabTestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_request_id',
        'template_id',
        'parameter_id',
        'parameter_code',
        'parameter_name',
        'result_value',
        'formatted_value',
        'unit',
        'reference_range',
        'age_group',
        'gender',
        'is_pregnant',
        'result_status',
        'verification_status',
        'abnormal_flag',
        'delta_check_value',
        'previous_value',
        'clinical_interpretation',
        'technical_notes',
        'quality_control_notes',
        'quality_control_data',
        'calibration_data',
        'methodology_used',
        'equipment_used',
        'reagent_lot_number',
        'reagent_expiry_date',
        'technician_notes',
        'test_performed_at',
        'result_entered_at',
        'result_verified_at',
        'result_approved_at',
        'performed_by',
        'verified_by',
        'approved_by',
        'requires_repeat',
        'repeat_reason',
        'repeat_requested_by',
        'repeat_requested_at',
        'is_critical_alert_sent',
        'critical_alert_sent_at'
    ];

    protected $casts = [
        'quality_control_data' => 'array',
        'calibration_data' => 'array',
        'test_performed_at' => 'datetime',
        'result_entered_at' => 'datetime',
        'result_verified_at' => 'datetime',
        'result_approved_at' => 'datetime',
        'repeat_requested_at' => 'datetime',
        'critical_alert_sent_at' => 'datetime',
        'reagent_expiry_date' => 'date',
        'is_pregnant' => 'boolean',
        'requires_repeat' => 'boolean',
        'is_critical_alert_sent' => 'boolean',
        'delta_check_value' => 'decimal:4',
        'previous_value' => 'decimal:4'
    ];

    /**
     * Get the lab request that owns this result.
     */
    public function labRequest()
    {
        return $this->belongsTo(LabRequest::class);
    }

    /**
     * Get the template for this result.
     */
    public function template()
    {
        return $this->belongsTo(LabTestTemplate::class, 'template_id');
    }

    /**
     * Get the parameter for this result.
     */
    public function parameter()
    {
        return $this->belongsTo(LabTestParameter::class, 'parameter_id');
    }

    /**
     * Get the technician who performed the test.
     */
    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the user who verified this result.
     */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the user who approved this result.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who requested repeat.
     */
    public function repeatRequestedBy()
    {
        return $this->belongsTo(User::class, 'repeat_requested_by');
    }

    /**
     * Get the comments for this result.
     */
    public function comments()
    {
        return $this->hasMany(LabResultComment::class, 'test_result_id');
    }

    /**
     * Scope a query to only include critical results.
     */
    public function scopeCritical($query)
    {
        return $query->where('result_status', 'critical');
    }

    /**
     * Scope a query to only include abnormal results.
     */
    public function scopeAbnormal($query)
    {
        return $query->where('result_status', 'abnormal');
    }

    /**
     * Scope a query to only include normal results.
     */
    public function scopeNormal($query)
    {
        return $query->where('result_status', 'normal');
    }

    /**
     * Scope a query to only include results requiring repeat.
     */
    public function scopeRequiringRepeat($query)
    {
        return $query->where('requires_repeat', true);
    }

    /**
     * Scope a query to filter by abnormal flag.
     */
    public function scopeWithFlag($query, $flag)
    {
        return $query->where('abnormal_flag', $flag);
    }

    /**
     * Scope a query to filter by pregnancy status.
     */
    public function scopePregnant($query)
    {
        return $query->where('is_pregnant', true);
    }

    /**
     * Scope a query to filter by gender.
     */
    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }

    /**
     * Scope a query to filter by age group.
     */
    public function scopeByAgeGroup($query, $ageGroup)
    {
        return $query->where('age_group', $ageGroup);
    }

    /**
     * Check if this result is critical.
     */
    public function isCritical()
    {
        return $this->result_status === 'critical';
    }

    /**
     * Check if this result is abnormal.
     */
    public function isAbnormal()
    {
        return $this->result_status === 'abnormal';
    }

    /**
     * Check if this result is normal.
     */
    public function isNormal()
    {
        return $this->result_status === 'normal';
    }

    /**
     * Check if this result requires repeat.
     */
    public function requiresRepeat()
    {
        return $this->requires_repeat;
    }

    /**
     * Check if this result is verified.
     */
    public function isVerified()
    {
        return !is_null($this->result_verified_at);
    }

    /**
     * Check if this result is approved.
     */
    public function isApproved()
    {
        return !is_null($this->result_approved_at);
    }

    /**
     * Check if this result is completed.
     */
    public function isCompleted()
    {
        return $this->result_status === 'normal' || 
               $this->result_status === 'abnormal' || 
               $this->result_status === 'critical';
    }

    /**
     * Get the formatted result value with unit.
     */
    public function getFormattedValue()
    {
        if ($this->formatted_value) {
            return $this->formatted_value;
        }
        
        $value = $this->result_value;
        $unit = $this->unit;
        
        if ($unit) {
            return $value . ' ' . $unit;
        }
        
        return $value;
    }

    /**
     * Get the result status with flag.
     */
    public function getStatusWithFlag()
    {
        $status = ucfirst($this->result_status);
        
        if ($this->abnormal_flag) {
            $status .= ' (' . $this->abnormal_flag . ')';
        }
        
        return $status;
    }

    /**
     * Get the turnaround time for this result.
     */
    public function getTurnaroundTime()
    {
        if (!$this->test_performed_at || !$this->result_entered_at) {
            return null;
        }
        
        return $this->test_performed_at->diffInHours($this->result_entered_at);
    }

    /**
     * Get the verification time for this result.
     */
    public function getVerificationTime()
    {
        if (!$this->result_entered_at || !$this->result_verified_at) {
            return null;
        }
        
        return $this->result_entered_at->diffInHours($this->result_verified_at);
    }

    /**
     * Get the approval time for this result.
     */
    public function getApprovalTime()
    {
        if (!$this->result_verified_at || !$this->result_approved_at) {
            return null;
        }
        
        return $this->result_verified_at->diffInHours($this->result_approved_at);
    }

    /**
     * Check if result is within reference range.
     */
    public function isWithinRange()
    {
        if (!$this->parameter) {
            return null;
        }
        
        return $this->parameter->isWithinRange(
            $this->result_value,
            $this->age_group,
            $this->gender,
            $this->is_pregnant
        );
    }

    /**
     * Get the delta check value if available.
     */
    public function getDeltaCheckValue()
    {
        return $this->delta_check_value;
    }

    /**
     * Get the quality control data.
     */
    public function getQualityControlData()
    {
        return $this->quality_control_data;
    }

    /**
     * Get the calibration data.
     */
    public function getCalibrationData()
    {
        return $this->calibration_data;
    }

    /**
     * Get the reference range text for this result.
     */
    public function getReferenceRangeText()
    {
        // If reference_range is already stored, use it
        if ($this->reference_range) {
            return $this->reference_range;
        }

        // Otherwise, get it from the parameter's reference ranges
        if (!$this->parameter) {
            return 'N/A';
        }

        $patient = $this->labRequest->patient;
        $age = $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age : null;
        $gender = $patient->gender;

        // Get appropriate reference range
        $referenceRange = $this->parameter->referenceRanges()
            ->active()
            ->byGender($gender)
            ->when($age, function($query, $age) {
                // Map age to age group
                $ageGroup = $this->getAgeGroup($age);
                return $query->where('age_group', $ageGroup);
            })
            ->first();

        if ($referenceRange) {
            return $referenceRange->getFormattedRange();
        }

        return 'N/A';
    }

    /**
     * Get age group from age in years.
     */
    private function getAgeGroup($age)
    {
        if ($age < 1) return 'Newborn';
        if ($age < 2) return 'Infant';
        if ($age < 18) return 'Child';
        if ($age < 65) return 'Adult';
        return 'Elderly';
    }

    /**
     * Get the status badge class for display.
     */
    public function getStatusBadgeClass()
    {
        switch ($this->result_status) {
            case 'normal':
                return 'badge-success';
            case 'abnormal':
                return 'badge-warning';
            case 'critical':
                return 'badge-danger';
            case 'pending':
                return 'badge-info';
            case 'cancelled':
                return 'badge-secondary';
            case 'repeated':
                return 'badge-primary';
            default:
                return 'badge-secondary';
        }
    }

    /**
     * Get the flag badge class for display.
     */
    public function getFlagBadgeClass()
    {
        switch ($this->abnormal_flag) {
            case 'H':
                return 'badge-warning';
            case 'L':
                return 'badge-info';
            case 'HH':
                return 'badge-danger';
            case 'LL':
                return 'badge-danger';
            case 'CRITICAL':
                return 'badge-danger';
            case 'DELTA':
                return 'badge-warning';
            case 'PANIC':
                return 'badge-danger';
            default:
                return 'badge-secondary';
        }
    }

    /**
     * Get clinical comments.
     */
    public function getClinicalComments()
    {
        return $this->comments()->where('comment_type', 'clinical')->get();
    }

    /**
     * Get technical comments.
     */
    public function getTechnicalComments()
    {
        return $this->comments()->where('comment_type', 'technical')->get();
    }

    /**
     * Get interpretation comments.
     */
    public function getInterpretationComments()
    {
        return $this->comments()->where('comment_type', 'interpretation')->get();
    }

    /**
     * Get public comments (visible to patient/doctor).
     */
    public function getPublicComments()
    {
        return $this->comments()->where('is_public', true)->get();
    }

    /**
     * Check if critical alert has been sent.
     */
    public function hasCriticalAlertBeenSent()
    {
        return $this->is_critical_alert_sent;
    }

    /**
     * Mark critical alert as sent.
     */
    public function markCriticalAlertSent()
    {
        $this->update([
            'is_critical_alert_sent' => true,
            'critical_alert_sent_at' => now()
        ]);
    }
}
