<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceCoverage extends Model
{
    use HasFactory;

    protected $table = 'insurance_coverage';

    protected $fillable = [
        'service_id',
        'patient_id',
        'insurance_provider_id',
        'policy_number',
        'coverage_percentage',
        'max_coverage_amount',
        'co_pay_percentage',
        'requires_pre_authorization',
        'is_active',
        'valid_from',
        'valid_until',
        'coverage_details',
        'created_by'
    ];

    protected $casts = [
        'coverage_details' => 'array',
        'is_active' => 'boolean',
        'requires_pre_authorization' => 'boolean',
        'coverage_percentage' => 'decimal:2',
        'max_coverage_amount' => 'decimal:2',
        'co_pay_percentage' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date'
    ];

    /**
     * Get the patient that owns the insurance coverage.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the insurance provider.
     */
    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    /**
     * Get the user who created the insurance coverage.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active coverage.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by service ID.
     */
    public function scopeForService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    /**
     * Scope a query to filter by patient.
     */
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope a query to only include currently valid coverage.
     */
    public function scopeCurrentlyValid($query)
    {
        $now = now()->toDateString();
        return $query->where(function($q) use ($now) {
            $q->whereNull('valid_from')
              ->orWhere('valid_from', '<=', $now);
        })->where(function($q) use ($now) {
            $q->whereNull('valid_until')
              ->orWhere('valid_until', '>=', $now);
        });
    }

    /**
     * Check if coverage is currently valid.
     */
    public function isCurrentlyValid()
    {
        $now = now()->toDateString();
        
        if ($this->valid_from && $this->valid_from > $now) {
            return false;
        }
        
        if ($this->valid_until && $this->valid_until < $now) {
            return false;
        }
        
        return true;
    }

    /**
     * Calculate coverage amount for given service amount.
     */
    public function calculateCoverage($serviceAmount)
    {
        if (!$this->is_active || !$this->isCurrentlyValid()) {
            return [
                'covered_amount' => 0,
                'co_pay_amount' => $serviceAmount,
                'coverage_percentage' => 0
            ];
        }

        $coveredAmount = ($serviceAmount * $this->coverage_percentage) / 100;
        
        // Apply maximum coverage limit
        if ($this->max_coverage_amount && $coveredAmount > $this->max_coverage_amount) {
            $coveredAmount = $this->max_coverage_amount;
        }

        $coPayAmount = $serviceAmount - $coveredAmount;

        return [
            'covered_amount' => $coveredAmount,
            'co_pay_amount' => $coPayAmount,
            'coverage_percentage' => $this->coverage_percentage,
            'requires_pre_authorization' => $this->requires_pre_authorization
        ];
    }

    /**
     * Check if pre-authorization is required.
     */
    public function requiresPreAuthorization()
    {
        return $this->requires_pre_authorization;
    }

    /**
     * Get coverage details for specific service.
     */
    public function getServiceCoverageDetails($serviceId)
    {
        $details = $this->coverage_details ?? [];
        return $details[$serviceId] ?? [];
    }

    /**
     * Set coverage details for specific service.
     */
    public function setServiceCoverageDetails($serviceId, $details)
    {
        $coverageDetails = $this->coverage_details ?? [];
        $coverageDetails[$serviceId] = $details;
        $this->coverage_details = $coverageDetails;
    }
}
