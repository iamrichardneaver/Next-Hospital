<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasIdPrefix;

class LabResult extends Model
{
    use HasFactory, HasIdPrefix;
    
    protected $idField = 'lab_result_number';

    protected $fillable = [
        'lab_request_id',
        'test_type_id',
        'parameter_name',
        'parameter_code',
        'result_value',
        'unit',
        'reference_range',
        'age_group',
        'gender',
        'result_status',
        'abnormal_flag',
        'delta_check_value',
        'notes',
        'clinical_notes',
        'technical_notes',
        'quality_control_data',
        'calibration_data',
        'methodology',
        'equipment_used',
        'reagent_lot',
        'reagent_expiry',
        'test_performed_at',
        'result_entered_at',
        'performed_by',
        'verified_by',
        'verified_at',
        'approved_by',
        'approved_at',
        'requires_repeat',
        'repeat_reason',
        'repeat_requested_by',
        'repeat_requested_at'
    ];

    protected $casts = [
        'quality_control_data' => 'array',
        'calibration_data' => 'array',
        'test_performed_at' => 'datetime',
        'result_entered_at' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'reagent_expiry' => 'date',
        'repeat_requested_at' => 'datetime',
        'requires_repeat' => 'boolean',
        'delta_check_value' => 'decimal:4'
    ];

    /**
     * Get the lab request that owns this result.
     */
    public function labRequest()
    {
        return $this->belongsTo(LabRequest::class);
    }

    /**
     * Get the test type for this result through the lab request.
     */
    public function testType()
    {
        return $this->hasOneThrough(LabTestType::class, LabRequest::class, 'id', 'id', 'lab_request_id', 'test_type_id');
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
        return !is_null($this->verified_at);
    }

    /**
     * Check if this result is approved.
     */
    public function isApproved()
    {
        return !is_null($this->approved_at);
    }

    /**
     * Get the formatted result value with unit.
     */
    public function getFormattedValue()
    {
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
        if (!$this->result_entered_at || !$this->verified_at) {
            return null;
        }
        
        return $this->result_entered_at->diffInHours($this->verified_at);
    }

    /**
     * Check if result is within reference range.
     */
    public function isWithinRange()
    {
        if (!$this->reference_range) {
            return null;
        }
        
        $value = floatval($this->result_value);
        $range = $this->parseReferenceRange($this->reference_range);
        
        if (!$range) {
            return null;
        }
        
        return $value >= $range['low'] && $value <= $range['high'];
    }

    /**
     * Parse reference range string to extract low and high values.
     */
    private function parseReferenceRange($referenceRange)
    {
        if (preg_match('/(\d+\.?\d*)\s*-\s*(\d+\.?\d*)/', $referenceRange, $matches)) {
            return [
                'low' => floatval($matches[1]),
                'high' => floatval($matches[2])
            ];
        }
        
        return null;
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
}