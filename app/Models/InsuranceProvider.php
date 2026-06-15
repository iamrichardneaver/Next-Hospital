<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class InsuranceProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'contact_person',
        'phone',
        'email',
        'address',
        'website',
        'api_endpoints',
        'api_credentials',
        'default_coverage_percentage',
        'default_co_pay_percentage',
        'requires_pre_authorization',
        'supports_electronic_claims',
        'supports_real_time_verification',
        'claim_settings',
        'coverage_limits',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'api_endpoints' => 'array',
        'api_credentials' => 'encrypted:array',
        'claim_settings' => 'array',
        'coverage_limits' => 'array',
        'is_active' => 'boolean',
        'requires_pre_authorization' => 'boolean',
        'supports_electronic_claims' => 'boolean',
        'supports_real_time_verification' => 'boolean',
        'default_coverage_percentage' => 'decimal:2',
        'default_co_pay_percentage' => 'decimal:2'
    ];

    /**
     * Get all insurance policies for this provider.
     */
    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class);
    }

    /**
     * Get all insurance coverage for this provider.
     */
    public function insuranceCoverage(): HasMany
    {
        return $this->hasMany(InsuranceCoverage::class);
    }

    /**
     * Get all coverage policies for this provider.
     */
    public function coveragePolicies(): HasMany
    {
        return $this->hasMany(InsuranceCoveragePolicy::class);
    }

    /**
     * Get all insurance claims for this provider.
     */
    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    /**
     * Get all pre-authorizations for this provider.
     */
    public function preAuthorizations(): HasMany
    {
        return $this->hasMany(PreAuthorization::class);
    }

    /**
     * Get the user who created the insurance provider.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active providers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}