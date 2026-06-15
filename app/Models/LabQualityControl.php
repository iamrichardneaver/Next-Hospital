<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabQualityControl extends Model
{
    use HasFactory;

    protected $fillable = [
        'parameter_id',
        'qc_type',
        'qc_level',
        'qc_material',
        'lot_number',
        'expiry_date',
        'target_value',
        'acceptable_range_low',
        'acceptable_range_high',
        'measured_value',
        'is_acceptable',
        'notes',
        'performed_at',
        'performed_by'
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'performed_at' => 'datetime',
        'is_acceptable' => 'boolean',
        'target_value' => 'decimal:4',
        'acceptable_range_low' => 'decimal:4',
        'acceptable_range_high' => 'decimal:4',
        'measured_value' => 'decimal:4'
    ];

    /**
     * Get the parameter that owns this quality control record.
     */
    public function parameter()
    {
        return $this->belongsTo(LabTestParameter::class, 'parameter_id');
    }

    /**
     * Get the user who performed this QC.
     */
    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Scope a query to filter by QC type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('qc_type', $type);
    }

    /**
     * Scope a query to filter by QC level.
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('qc_level', $level);
    }

    /**
     * Scope a query to only include acceptable QC results.
     */
    public function scopeAcceptable($query)
    {
        return $query->where('is_acceptable', true);
    }

    /**
     * Scope a query to only include unacceptable QC results.
     */
    public function scopeUnacceptable($query)
    {
        return $query->where('is_acceptable', false);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $dateFrom, $dateTo)
    {
        return $query->whereBetween('performed_at', [$dateFrom, $dateTo]);
    }

    /**
     * Check if the measured value is within acceptable range.
     */
    public function isWithinAcceptableRange()
    {
        if (!$this->acceptable_range_low || !$this->acceptable_range_high) {
            return null; // No range defined
        }
        
        $measuredValue = floatval($this->measured_value);
        $lowValue = floatval($this->acceptable_range_low);
        $highValue = floatval($this->acceptable_range_high);
        
        return $measuredValue >= $lowValue && $measuredValue <= $highValue;
    }

    /**
     * Calculate the bias (difference from target).
     */
    public function getBias()
    {
        if (!$this->target_value || !$this->measured_value) {
            return null;
        }
        
        return floatval($this->measured_value) - floatval($this->target_value);
    }

    /**
     * Calculate the bias percentage.
     */
    public function getBiasPercentage()
    {
        $bias = $this->getBias();
        
        if ($bias === null || !$this->target_value) {
            return null;
        }
        
        return ($bias / floatval($this->target_value)) * 100;
    }

    /**
     * Get the QC type display name.
     */
    public function getQcTypeDisplayName()
    {
        return ucfirst(str_replace('_', ' ', $this->qc_type));
    }

    /**
     * Get the QC level display name.
     */
    public function getQcLevelDisplayName()
    {
        return ucfirst(str_replace('_', ' ', $this->qc_level));
    }

    /**
     * Check if QC material is expired.
     */
    public function isExpired()
    {
        if (!$this->expiry_date) {
            return false;
        }
        
        return $this->expiry_date->isPast();
    }

    /**
     * Get the days until expiry.
     */
    public function getDaysUntilExpiry()
    {
        if (!$this->expiry_date) {
            return null;
        }
        
        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get the QC status badge class.
     */
    public function getStatusBadgeClass()
    {
        if ($this->isExpired()) {
            return 'badge-danger';
        }
        
        if ($this->is_acceptable) {
            return 'badge-success';
        }
        
        return 'badge-warning';
    }

    /**
     * Get the QC status text.
     */
    public function getStatusText()
    {
        if ($this->isExpired()) {
            return 'Expired';
        }
        
        if ($this->is_acceptable) {
            return 'Acceptable';
        }
        
        return 'Unacceptable';
    }
}
