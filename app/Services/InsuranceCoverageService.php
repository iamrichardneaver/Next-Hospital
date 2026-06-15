<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\InsurancePolicy;
use App\Models\InsuranceCoveragePolicy;
use App\Models\PreAuthorization;
use App\Models\InsuranceClaim;
use Illuminate\Support\Facades\DB;

class InsuranceCoverageService
{
    /**
     * Calculate insurance coverage for a patient's service.
     */
    public function calculateCoverage($patientId, $serviceType, $serviceCode, $serviceAmount, $serviceDate = null)
    {
        $serviceDate = $serviceDate ?: now()->toDateString();
        $patient = Patient::findOrFail($patientId);
        
        // Get active policies for the patient
        $activePolicies = $patient->insurancePolicies()
            ->where('is_active', true)
            ->where('start_date', '<=', $serviceDate)
            ->where('end_date', '>=', $serviceDate)
            ->orderBy('is_primary', 'desc')
            ->get();

        if ($activePolicies->isEmpty()) {
            return [
                'has_coverage' => false,
                'covered_amount' => 0,
                'co_pay_amount' => $serviceAmount,
                'reason' => 'No active insurance policy found',
                'policies' => []
            ];
        }

        $coverageResults = [];
        $totalCoveredAmount = 0;
        $remainingAmount = $serviceAmount;

        foreach ($activePolicies as $policy) {
            if ($remainingAmount <= 0) break;

            $coverage = $this->calculatePolicyCoverage($policy, $serviceType, $serviceCode, $remainingAmount, $patient);
            
            if ($coverage['covered_amount'] > 0) {
                $coverageResults[] = [
                    'policy_id' => $policy->id,
                    'policy_number' => $policy->policy_number,
                    'provider_name' => $policy->insuranceProvider->name,
                    'coverage_percentage' => $coverage['coverage_percentage'],
                    'covered_amount' => $coverage['covered_amount'],
                    'co_pay_amount' => $coverage['co_pay_amount'],
                    'requires_pre_authorization' => $coverage['requires_pre_authorization'],
                    'reason' => $coverage['reason'] ?? null
                ];

                $totalCoveredAmount += $coverage['covered_amount'];
                $remainingAmount -= $coverage['covered_amount'];
            }
        }

        $finalCoPayAmount = $serviceAmount - $totalCoveredAmount;

        return [
            'has_coverage' => $totalCoveredAmount > 0,
            'covered_amount' => $totalCoveredAmount,
            'co_pay_amount' => $finalCoPayAmount,
            'coverage_percentage' => $serviceAmount > 0 ? round(($totalCoveredAmount / $serviceAmount) * 100, 2) : 0,
            'policies' => $coverageResults,
            'requires_pre_authorization' => collect($coverageResults)->contains('requires_pre_authorization', true)
        ];
    }

    /**
     * Calculate coverage for a specific policy.
     */
    private function calculatePolicyCoverage($policy, $serviceType, $serviceCode, $serviceAmount, $patient)
    {
        // Check if policy covers this service
        if (!$policy->coversService($serviceType, $serviceCode)) {
            return [
                'covered_amount' => 0,
                'co_pay_amount' => $serviceAmount,
                'coverage_percentage' => 0,
                'requires_pre_authorization' => false,
                'reason' => 'Service not covered by policy'
            ];
        }

        // Get provider-specific coverage policy
        $coveragePolicy = InsuranceCoveragePolicy::where('insurance_provider_id', $policy->insurance_provider_id)
            ->where('service_type', $serviceType)
            ->where(function($query) use ($serviceCode) {
                $query->whereNull('service_code')
                      ->orWhere('service_code', $serviceCode);
            })
            ->where('is_active', true)
            ->where('effective_from', '<=', now()->toDateString())
            ->where(function($query) {
                $query->whereNull('effective_until')
                      ->orWhere('effective_until', '>=', now()->toDateString());
            })
            ->first();

        if ($coveragePolicy) {
            $coverage = $coveragePolicy->calculateCoverage(
                $serviceAmount, 
                $patient->age, 
                $patient->gender
            );
            
            return array_merge($coverage, [
                'coverage_percentage' => $coveragePolicy->coverage_percentage,
                'requires_pre_authorization' => $coveragePolicy->requires_pre_authorization
            ]);
        }

        // Use policy default coverage
        return $policy->calculateCoverage($serviceAmount, $serviceType, $serviceCode);
    }

    /**
     * Check if pre-authorization is required.
     */
    public function requiresPreAuthorization($patientId, $serviceType, $serviceCode, $serviceAmount)
    {
        $coverage = $this->calculateCoverage($patientId, $serviceType, $serviceCode, $serviceAmount);
        
        return $coverage['requires_pre_authorization'] ?? false;
    }

    /**
     * Create pre-authorization request.
     */
    public function createPreAuthorization($patientId, $serviceType, $serviceCode, $serviceDescription, $requestedAmount, $urgency = 'routine')
    {
        $patient = Patient::findOrFail($patientId);
        $activePolicy = $patient->insurancePolicies()
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('is_primary', true)
            ->first();

        if (!$activePolicy) {
            throw new \Exception('No active primary insurance policy found');
        }

        $preAuth = PreAuthorization::create([
            'patient_id' => $patientId,
            'insurance_provider_id' => $activePolicy->insurance_provider_id,
            'policy_id' => $activePolicy->id,
            'pre_auth_number' => PreAuthorization::generatePreAuthNumber(),
            'service_type' => $serviceType,
            'service_code' => $serviceCode,
            'service_description' => $serviceDescription,
            'requested_amount' => $requestedAmount,
            'status' => 'pending',
            'urgency' => $urgency,
            'request_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(30)->toDateString(),
            'requested_by' => auth()->id()
        ]);

        return $preAuth;
    }

    /**
     * Process insurance claim.
     */
    public function processClaim($patientId, $serviceType, $serviceCode, $serviceDescription, $serviceAmount, $serviceDate, $claimItems = [])
    {
        $patient = Patient::findOrFail($patientId);
        $activePolicy = $patient->insurancePolicies()
            ->where('is_active', true)
            ->where('start_date', '<=', $serviceDate)
            ->where('end_date', '>=', $serviceDate)
            ->where('is_primary', true)
            ->first();

        if (!$activePolicy) {
            throw new \Exception('No active primary insurance policy found');
        }

        $coverage = $this->calculateCoverage($patientId, $serviceType, $serviceCode, $serviceAmount, $serviceDate);

        $claim = InsuranceClaim::create([
            'patient_id' => $patientId,
            'insurance_provider_id' => $activePolicy->insurance_provider_id,
            'policy_id' => $activePolicy->id,
            'claim_number' => $this->generateClaimNumber(),
            'service_type' => $serviceType,
            'service_date' => $serviceDate,
            'total_amount' => $serviceAmount,
            'covered_amount' => $coverage['covered_amount'],
            'co_pay_amount' => $coverage['co_pay_amount'],
            'status' => 'draft',
            'claim_items' => $claimItems,
            'created_by' => auth()->id()
        ]);

        // Create claim items
        foreach ($claimItems as $item) {
            $claim->claimItems()->create([
                'service_type' => $item['service_type'],
                'service_code' => $item['service_code'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $item['unit_price'],
                'total_amount' => $item['total_amount'],
                'covered_amount' => $item['covered_amount'] ?? 0,
                'co_pay_amount' => $item['co_pay_amount'] ?? 0,
                'service_details' => $item['service_details'] ?? null
            ]);
        }

        return $claim;
    }

    /**
     * Get insurance statistics for a patient.
     */
    public function getPatientInsuranceStats($patientId, $year = null)
    {
        $year = $year ?: now()->year;
        
        $patient = Patient::findOrFail($patientId);
        
        $policies = $patient->insurancePolicies()
            ->where('is_active', true)
            ->with('insuranceProvider')
            ->get();

        $claims = InsuranceClaim::where('patient_id', $patientId)
            ->whereYear('submitted_date', $year)
            ->get();

        $totalClaimAmount = $claims->sum('total_amount');
        $totalCoveredAmount = $claims->sum('covered_amount');
        $totalCoPayAmount = $claims->sum('co_pay_amount');

        return [
            'active_policies' => $policies->count(),
            'total_claims' => $claims->count(),
            'total_claim_amount' => $totalClaimAmount,
            'total_covered_amount' => $totalCoveredAmount,
            'total_co_pay_amount' => $totalCoPayAmount,
            'coverage_percentage' => $totalClaimAmount > 0 ? round(($totalCoveredAmount / $totalClaimAmount) * 100, 2) : 0,
            'policies' => $policies->map(function($policy) use ($year) {
                $policyClaims = $policy->claims()->whereYear('submitted_date', $year)->get();
                return [
                    'id' => $policy->id,
                    'policy_number' => $policy->policy_number,
                    'provider_name' => $policy->insuranceProvider->name,
                    'coverage_percentage' => $policy->coverage_percentage,
                    'annual_limit' => $policy->annual_limit,
                    'used_amount' => $policyClaims->sum('processed_amount'),
                    'remaining_limit' => $policy->annual_limit ? $policy->annual_limit - $policyClaims->sum('processed_amount') : null,
                    'claims_count' => $policyClaims->count()
                ];
            })
        ];
    }

    /**
     * Generate claim number.
     */
    private function generateClaimNumber()
    {
        $prefix = 'CLM';
        $date = now()->format('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $date . $random;
    }

    /**
     * Validate insurance coverage before service.
     */
    public function validateCoverage($patientId, $serviceType, $serviceCode, $serviceAmount, $serviceDate = null)
    {
        $coverage = $this->calculateCoverage($patientId, $serviceType, $serviceCode, $serviceAmount, $serviceDate);
        
        if (!$coverage['has_coverage']) {
            return [
                'valid' => false,
                'message' => 'No insurance coverage available for this service',
                'coverage' => $coverage
            ];
        }

        if ($coverage['requires_pre_authorization']) {
            return [
                'valid' => false,
                'message' => 'Pre-authorization required for this service',
                'coverage' => $coverage,
                'requires_pre_auth' => true
            ];
        }

        return [
            'valid' => true,
            'message' => 'Insurance coverage validated successfully',
            'coverage' => $coverage
        ];
    }
}
