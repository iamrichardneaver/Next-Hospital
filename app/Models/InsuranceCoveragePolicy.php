<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceCoveragePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_provider_id',
        'service_category_id',
        'service_type',
        'service_code',
        'coverage_percentage',
        'co_pay_percentage',
        'max_coverage_amount',
        'min_coverage_amount',
        'deductible',
        'requires_pre_authorization',
        'pre_authorization_days',
        'coverage_conditions',
        'exclusions',
        'age_restrictions',
        'gender_restrictions',
        'is_active',
        'effective_from',
        'effective_until',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'coverage_percentage' => 'decimal:2',
        'co_pay_percentage' => 'decimal:2',
        'max_coverage_amount' => 'decimal:2',
        'min_coverage_amount' => 'decimal:2',
        'deductible' => 'decimal:2',
        'requires_pre_authorization' => 'boolean',
        'pre_authorization_days' => 'integer',
        'coverage_conditions' => 'array',
        'exclusions' => 'array',
        'age_restrictions' => 'array',
        'gender_restrictions' => 'array',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date'
    ];

    /**
     * Get the insurance provider.
     */
    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    /**
     * Get the service category.
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(InsuranceServiceCategory::class);
    }

    /**
     * Get the user who created the coverage policy.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active policies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include currently effective policies.
     */
    public function scopeCurrentlyEffective($query)
    {
        $now = now()->toDateString();
        return $query->where('effective_from', '<=', $now)
                    ->where(function($q) use ($now) {
                        $q->whereNull('effective_until')
                          ->orWhere('effective_until', '>=', $now);
                    });
    }

    /**
     * Scope a query to filter by service type.
     */
    public function scopeForServiceType($query, $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }

    /**
     * Scope a query to filter by service code.
     */
    public function scopeForServiceCode($query, $serviceCode)
    {
        return $query->where('service_code', $serviceCode);
    }

    /**
     * Check if policy covers a specific service.
     */
    public function coversService($serviceType, $serviceCode = null)
    {
        if ($this->service_type !== $serviceType) {
            return false;
        }

        if ($serviceCode && $this->service_code && $this->service_code !== $serviceCode) {
            return false;
        }

        return $this->is_active && $this->isCurrentlyEffective();
    }

    /**
     * Check if policy is currently effective.
     */
    public function isCurrentlyEffective()
    {
        $now = now()->toDateString();
        return $this->effective_from <= $now && 
               ($this->effective_until === null || $this->effective_until >= $now);
    }

    /**
     * Calculate coverage amount for a service.
     */
    public function calculateCoverage($serviceAmount, $patientAge = null, $patientGender = null)
    {
        // Check age restrictions
        if ($patientAge && $this->age_restrictions) {
            $ageRestrictions = $this->age_restrictions;
            if (isset($ageRestrictions['min_age']) && $patientAge < $ageRestrictions['min_age']) {
                return ['covered_amount' => 0, 'co_pay_amount' => $serviceAmount, 'reason' => 'Age restriction'];
            }
            if (isset($ageRestrictions['max_age']) && $patientAge > $ageRestrictions['max_age']) {
                return ['covered_amount' => 0, 'co_pay_amount' => $serviceAmount, 'reason' => 'Age restriction'];
            }
        }

        // Check gender restrictions
        if ($patientGender && $this->gender_restrictions) {
            $genderRestrictions = $this->gender_restrictions;
            if (isset($genderRestrictions['allowed_genders']) && 
                !in_array($patientGender, $genderRestrictions['allowed_genders'])) {
                return ['covered_amount' => 0, 'co_pay_amount' => $serviceAmount, 'reason' => 'Gender restriction'];
            }
        }

        // Calculate coverage
        $coveredAmount = ($serviceAmount * $this->coverage_percentage) / 100;
        $coPayAmount = $serviceAmount - $coveredAmount;

        // Apply minimum coverage amount
        if ($this->min_coverage_amount && $coveredAmount < $this->min_coverage_amount) {
            $coveredAmount = $this->min_coverage_amount;
            $coPayAmount = $serviceAmount - $coveredAmount;
        }

        // Apply maximum coverage amount
        if ($this->max_coverage_amount && $coveredAmount > $this->max_coverage_amount) {
            $coveredAmount = $this->max_coverage_amount;
            $coPayAmount = $serviceAmount - $coveredAmount;
        }

        return [
            'covered_amount' => $coveredAmount,
            'co_pay_amount' => $coPayAmount,
            'coverage_percentage' => $this->coverage_percentage,
            'requires_pre_authorization' => $this->requires_pre_authorization,
            'pre_authorization_days' => $this->pre_authorization_days
        ];
    }
}
