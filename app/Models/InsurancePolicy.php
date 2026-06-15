<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasIdPrefix;

class InsurancePolicy extends Model
{
    use HasFactory, HasIdPrefix;

    protected $fillable = [
        'patient_id',
        'insurance_provider_id',
        'policy_number',
        'coverage_type',
        'policy_holder_name',
        'policy_holder_relationship',
        'start_date',
        'end_date',
        'coverage_percentage',
        'co_pay_percentage',
        'annual_limit',
        'lifetime_limit',
        'deductible',
        'co_pay_amount',
        'covered_services',
        'excluded_services',
        'special_conditions',
        'requires_pre_authorization',
        'is_primary',
        'is_active',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'coverage_percentage' => 'decimal:2',
        'co_pay_percentage' => 'decimal:2',
        'annual_limit' => 'decimal:2',
        'lifetime_limit' => 'decimal:2',
        'deductible' => 'decimal:2',
        'co_pay_amount' => 'decimal:2',
        'covered_services' => 'array',
        'excluded_services' => 'array',
        'special_conditions' => 'array',
        'requires_pre_authorization' => 'boolean'
    ];

    /**
     * Get the patient that owns the insurance policy.
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
     * Get the user who created the insurance policy.
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
     * Scope a query to only include currently valid policies.
     */
    public function scopeCurrentlyValid($query)
    {
        $now = now()->toDateString();
        return $query->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
    }

    /**
     * Get all claims for this policy.
     */
    public function claims()
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    /**
     * Get all pre-authorizations for this policy.
     */
    public function preAuthorizations()
    {
        return $this->hasMany(PreAuthorization::class);
    }

    /**
     * Check if policy covers a specific service.
     */
    public function coversService($serviceType, $serviceCode = null)
    {
        // Check if service is explicitly excluded
        if ($this->excluded_services) {
            foreach ($this->excluded_services as $excluded) {
                if ($excluded['service_type'] === $serviceType) {
                    if (!$serviceCode || !isset($excluded['service_code']) || $excluded['service_code'] === $serviceCode) {
                        return false;
                    }
                }
            }
        }

        // Check if service is explicitly covered
        if ($this->covered_services) {
            foreach ($this->covered_services as $covered) {
                if ($covered['service_type'] === $serviceType) {
                    if (!$serviceCode || !isset($covered['service_code']) || $covered['service_code'] === $serviceCode) {
                        return true;
                    }
                }
            }
        }

        // If no specific coverage rules, use default coverage
        return true;
    }

    /**
     * Calculate coverage for a service.
     */
    public function calculateCoverage($serviceAmount, $serviceType, $serviceCode = null)
    {
        if (!$this->coversService($serviceType, $serviceCode)) {
            return [
                'covered_amount' => 0,
                'co_pay_amount' => $serviceAmount,
                'reason' => 'Service not covered by policy'
            ];
        }

        $coveredAmount = ($serviceAmount * $this->coverage_percentage) / 100;
        $coPayAmount = $serviceAmount - $coveredAmount;

        // Check annual limit
        if ($this->annual_limit) {
            $usedAmount = $this->getUsedAnnualAmount();
            $remainingLimit = $this->annual_limit - $usedAmount;
            
            if ($coveredAmount > $remainingLimit) {
                $coveredAmount = max(0, $remainingLimit);
                $coPayAmount = $serviceAmount - $coveredAmount;
            }
        }

        return [
            'covered_amount' => $coveredAmount,
            'co_pay_amount' => $coPayAmount,
            'coverage_percentage' => $this->coverage_percentage,
            'requires_pre_authorization' => $this->requires_pre_authorization
        ];
    }

    /**
     * Get used annual amount for this policy.
     */
    public function getUsedAnnualAmount()
    {
        return $this->claims()
            ->where('status', 'approved')
            ->whereYear('submitted_date', now()->year)
            ->sum('processed_amount');
    }

    /**
     * Check if policy is currently valid.
     */
    public function isValid()
    {
        $now = now()->toDateString();
        return $this->is_active && 
               $this->start_date <= $now && 
               $this->end_date >= $now;
    }
}