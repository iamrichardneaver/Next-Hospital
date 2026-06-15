<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\InsurancePolicy;
use App\Models\InsuranceProvider;
use App\Models\InsuranceClaim;
use App\Models\PreAuthorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InsuranceIntelligenceService
{
    protected $insuranceCoverageService;

    public function __construct(InsuranceCoverageService $insuranceCoverageService)
    {
        $this->insuranceCoverageService = $insuranceCoverageService;
    }

    /**
     * Intelligent coverage prediction based on historical data
     */
    public function predictCoverage($patientId, $serviceType, $serviceCode, $serviceAmount)
    {
        try {
            $patient = Patient::findOrFail($patientId);
            
            // Get historical coverage data for similar services
            $historicalData = $this->getHistoricalCoverageData($patientId, $serviceType, $serviceCode);
            
            // Get current policy coverage
            $currentCoverage = $this->insuranceCoverageService->calculateCoverage(
                $patientId, $serviceType, $serviceCode, $serviceAmount
            );
            
            // Calculate prediction confidence
            $confidence = $this->calculatePredictionConfidence($historicalData, $currentCoverage);
            
            // Predict potential issues
            $potentialIssues = $this->identifyPotentialIssues($patientId, $serviceType, $serviceAmount, $currentCoverage);
            
            return [
                'predicted_coverage' => $currentCoverage,
                'confidence_level' => $confidence,
                'historical_success_rate' => $this->calculateHistoricalSuccessRate($historicalData),
                'potential_issues' => $potentialIssues,
                'recommendations' => $this->generateRecommendations($potentialIssues, $currentCoverage),
                'alternative_options' => $this->suggestAlternativeOptions($patientId, $serviceType, $serviceAmount)
            ];
            
        } catch (\Exception $e) {
            Log::error('Coverage prediction failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get historical coverage data
     */
    private function getHistoricalCoverageData($patientId, $serviceType, $serviceCode)
    {
        return InsuranceClaim::whereHas('policy', function($query) use ($patientId) {
                $query->where('patient_id', $patientId);
            })
            ->whereHas('claimItems', function($query) use ($serviceType, $serviceCode) {
                $query->where('service_type', $serviceType);
                if ($serviceCode) {
                    $query->where('service_code', $serviceCode);
                }
            })
            ->with(['policy.insuranceProvider', 'claimItems'])
            ->get();
    }

    /**
     * Calculate prediction confidence
     */
    private function calculatePredictionConfidence($historicalData, $currentCoverage)
    {
        if ($historicalData->isEmpty()) {
            return 'Low'; // No historical data
        }
        
        $successRate = $this->calculateHistoricalSuccessRate($historicalData);
        
        if ($successRate >= 90) return 'High';
        if ($successRate >= 70) return 'Medium';
        return 'Low';
    }

    /**
     * Calculate historical success rate
     */
    private function calculateHistoricalSuccessRate($historicalData)
    {
        if ($historicalData->isEmpty()) return 0;
        
        $approvedClaims = $historicalData->where('status', 'approved')->count();
        return ($approvedClaims / $historicalData->count()) * 100;
    }

    /**
     * Identify potential issues
     */
    private function identifyPotentialIssues($patientId, $serviceType, $serviceAmount, $coverage)
    {
        $issues = [];
        
        // Check if patient has active insurance
        if (!$coverage['has_coverage']) {
            $issues[] = [
                'type' => 'no_coverage',
                'severity' => 'high',
                'message' => 'Patient has no active insurance coverage'
            ];
        }
        
        // Check for high co-pay amounts
        if ($coverage['co_pay_amount'] > 1000) {
            $issues[] = [
                'type' => 'high_copay',
                'severity' => 'medium',
                'message' => 'High co-pay amount: ₵' . number_format($coverage['co_pay_amount'], 2)
            ];
        }
        
        // Check for pre-authorization requirements
        if ($coverage['requires_pre_authorization']) {
            $issues[] = [
                'type' => 'preauth_required',
                'severity' => 'medium',
                'message' => 'Pre-authorization required for this service'
            ];
        }
        
        // Check for policy expiration
        $expiringPolicies = $this->checkExpiringPolicies($patientId);
        if ($expiringPolicies->isNotEmpty()) {
            $issues[] = [
                'type' => 'policy_expiring',
                'severity' => 'high',
                'message' => 'Insurance policy expires within 30 days'
            ];
        }
        
        // Check for coverage limits
        $limitIssues = $this->checkCoverageLimits($patientId, $serviceAmount);
        $issues = array_merge($issues, $limitIssues);
        
        return $issues;
    }

    /**
     * Generate recommendations
     */
    private function generateRecommendations($potentialIssues, $coverage)
    {
        $recommendations = [];
        
        foreach ($potentialIssues as $issue) {
            switch ($issue['type']) {
                case 'no_coverage':
                    $recommendations[] = 'Consider cash payment or payment plan options';
                    break;
                case 'high_copay':
                    $recommendations[] = 'Discuss payment options with patient before proceeding';
                    break;
                case 'preauth_required':
                    $recommendations[] = 'Submit pre-authorization request before scheduling service';
                    break;
                case 'policy_expiring':
                    $recommendations[] = 'Remind patient to renew insurance policy';
                    break;
                case 'coverage_limit_exceeded':
                    $recommendations[] = 'Consider alternative payment methods or service modifications';
                    break;
            }
        }
        
        // Add general recommendations
        if ($coverage['coverage_percentage'] < 50) {
            $recommendations[] = 'Low coverage percentage - consider discussing cost implications with patient';
        }
        
        return $recommendations;
    }

    /**
     * Suggest alternative options
     */
    private function suggestAlternativeOptions($patientId, $serviceType, $serviceAmount)
    {
        $alternatives = [];
        
        // Check for lower-cost alternatives
        $alternatives[] = [
            'type' => 'payment_plan',
            'description' => 'Set up payment plan for co-pay amount',
            'estimated_savings' => 0
        ];
        
        // Check if patient has secondary insurance
        $secondaryInsurance = $this->checkSecondaryInsurance($patientId);
        if ($secondaryInsurance) {
            $alternatives[] = [
                'type' => 'secondary_insurance',
                'description' => 'Use secondary insurance to cover remaining amount',
                'estimated_savings' => $serviceAmount * 0.2 // Estimate 20% additional coverage
            ];
        }
        
        return $alternatives;
    }

    /**
     * Check expiring policies
     */
    private function checkExpiringPolicies($patientId)
    {
        return InsurancePolicy::where('patient_id', $patientId)
            ->where('is_active', true)
            ->where('end_date', '<=', now()->addDays(30))
            ->where('end_date', '>=', now())
            ->get();
    }

    /**
     * Check coverage limits
     */
    private function checkCoverageLimits($patientId, $serviceAmount)
    {
        $issues = [];
        
        $policies = InsurancePolicy::where('patient_id', $patientId)
            ->where('is_active', true)
            ->get();
            
        foreach ($policies as $policy) {
            // Check annual limit
            if ($policy->annual_limit) {
                $usedAmount = $this->calculateUsedAnnualAmount($policy);
                if (($usedAmount + $serviceAmount) > $policy->annual_limit) {
                    $issues[] = [
                        'type' => 'annual_limit_exceeded',
                        'severity' => 'high',
                        'message' => 'Annual coverage limit would be exceeded'
                    ];
                }
            }
            
            // Check lifetime limit
            if ($policy->lifetime_limit) {
                $usedLifetimeAmount = $this->calculateUsedLifetimeAmount($policy);
                if (($usedLifetimeAmount + $serviceAmount) > $policy->lifetime_limit) {
                    $issues[] = [
                        'type' => 'lifetime_limit_exceeded',
                        'severity' => 'high',
                        'message' => 'Lifetime coverage limit would be exceeded'
                    ];
                }
            }
        }
        
        return $issues;
    }

    /**
     * Calculate used annual amount
     */
    private function calculateUsedAnnualAmount($policy)
    {
        $yearStart = now()->startOfYear();
        $yearEnd = now()->endOfYear();
        
        return InsuranceClaim::whereHas('policy', function($query) use ($policy) {
                $query->where('id', $policy->id);
            })
            ->whereBetween('submitted_date', [$yearStart, $yearEnd])
            ->where('status', 'approved')
            ->sum('covered_amount');
    }

    /**
     * Calculate used lifetime amount
     */
    private function calculateUsedLifetimeAmount($policy)
    {
        return InsuranceClaim::whereHas('policy', function($query) use ($policy) {
                $query->where('id', $policy->id);
            })
            ->where('status', 'approved')
            ->sum('covered_amount');
    }

    /**
     * Check for secondary insurance
     */
    private function checkSecondaryInsurance($patientId)
    {
        return InsurancePolicy::where('patient_id', $patientId)
            ->where('is_active', true)
            ->where('is_primary', false)
            ->first();
    }

    /**
     * Intelligent claim processing recommendations
     */
    public function getClaimProcessingRecommendations($claimId)
    {
        $claim = InsuranceClaim::with(['patient', 'policy.insuranceProvider', 'claimItems'])->findOrFail($claimId);
        
        $recommendations = [];
        
        // Check for duplicate claims
        $duplicateClaims = $this->checkDuplicateClaims($claim);
        if ($duplicateClaims->isNotEmpty()) {
            $recommendations[] = [
                'type' => 'duplicate_claim',
                'severity' => 'high',
                'message' => 'Potential duplicate claim detected',
                'action' => 'Review existing claims before processing'
            ];
        }
        
        // Check for unusual claim amounts
        if ($this->isUnusualClaimAmount($claim)) {
            $recommendations[] = [
                'type' => 'unusual_amount',
                'severity' => 'medium',
                'message' => 'Claim amount is significantly higher than average for this service type',
                'action' => 'Verify service details and pricing'
            ];
        }
        
        // Check for missing documentation
        $missingDocs = $this->checkMissingDocumentation($claim);
        if ($missingDocs) {
            $recommendations[] = [
                'type' => 'missing_documentation',
                'severity' => 'high',
                'message' => 'Required documentation may be missing',
                'action' => 'Request additional documentation before processing'
            ];
        }
        
        return $recommendations;
    }

    /**
     * Check for duplicate claims
     */
    private function checkDuplicateClaims($claim)
    {
        return InsuranceClaim::where('patient_id', $claim->patient_id)
            ->where('id', '!=', $claim->id)
            ->where('status', '!=', 'rejected')
            ->whereBetween('submitted_date', [
                now()->subDays(30),
                now()
            ])
            ->whereHas('claimItems', function($query) use ($claim) {
                $query->whereIn('service_type', $claim->claimItems->pluck('service_type'));
            })
            ->get();
    }

    /**
     * Check if claim amount is unusual
     */
    private function isUnusualClaimAmount($claim)
    {
        $averageAmount = InsuranceClaim::whereHas('claimItems', function($query) use ($claim) {
                $query->whereIn('service_type', $claim->claimItems->pluck('service_type'));
            })
            ->where('status', 'approved')
            ->avg('total_amount');
            
        return $claim->total_amount > ($averageAmount * 2); // More than 2x average
    }

    /**
     * Check for missing documentation
     */
    private function checkMissingDocumentation($claim)
    {
        // This would integrate with document management system
        // For now, return false as placeholder
        return false;
    }

    /**
     * Generate insurance utilization insights
     */
    public function generateUtilizationInsights($patientId = null, $dateFrom = null, $dateTo = null)
    {
        $dateFrom = $dateFrom ?: now()->subDays(30);
        $dateTo = $dateTo ?: now();
        
        $query = InsuranceClaim::whereBetween('submitted_date', [$dateFrom, $dateTo]);
        
        if ($patientId) {
            $query->where('patient_id', $patientId);
        }
        
        $claims = $query->with(['patient', 'policy.insuranceProvider', 'claimItems'])->get();
        
        return [
            'total_claims' => $claims->count(),
            'total_amount' => $claims->sum('total_amount'),
            'average_claim_amount' => $claims->avg('total_amount'),
            'most_common_services' => $this->getMostCommonServices($claims),
            'provider_performance' => $this->getProviderPerformance($claims),
            'trend_analysis' => $this->getTrendAnalysis($claims),
            'cost_savings_opportunities' => $this->identifyCostSavingsOpportunities($claims)
        ];
    }

    /**
     * Get most common services
     */
    private function getMostCommonServices($claims)
    {
        $serviceCounts = [];
        
        foreach ($claims as $claim) {
            foreach ($claim->claimItems as $item) {
                $serviceType = $item->service_type;
                if (!isset($serviceCounts[$serviceType])) {
                    $serviceCounts[$serviceType] = 0;
                }
                $serviceCounts[$serviceType]++;
            }
        }
        
        arsort($serviceCounts);
        return array_slice($serviceCounts, 0, 5, true);
    }

    /**
     * Get provider performance
     */
    private function getProviderPerformance($claims)
    {
        $providerStats = [];
        
        foreach ($claims as $claim) {
            $providerId = $claim->policy->insurance_provider_id;
            $providerName = $claim->policy->insuranceProvider->name;
            
            if (!isset($providerStats[$providerId])) {
                $providerStats[$providerId] = [
                    'name' => $providerName,
                    'total_claims' => 0,
                    'approved_claims' => 0,
                    'total_amount' => 0,
                    'avg_processing_days' => 0
                ];
            }
            
            $providerStats[$providerId]['total_claims']++;
            $providerStats[$providerId]['total_amount'] += $claim->total_amount;
            
            if ($claim->status === 'approved') {
                $providerStats[$providerId]['approved_claims']++;
            }
        }
        
        // Calculate approval rates and processing times
        foreach ($providerStats as &$stats) {
            $stats['approval_rate'] = $stats['total_claims'] > 0 
                ? ($stats['approved_claims'] / $stats['total_claims']) * 100 
                : 0;
        }
        
        return $providerStats;
    }

    /**
     * Get trend analysis
     */
    private function getTrendAnalysis($claims)
    {
        $monthlyData = [];
        
        foreach ($claims as $claim) {
            $month = \Carbon\Carbon::parse($claim->submitted_date)->format('Y-m');
            
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = [
                    'claims_count' => 0,
                    'total_amount' => 0
                ];
            }
            
            $monthlyData[$month]['claims_count']++;
            $monthlyData[$month]['total_amount'] += $claim->total_amount;
        }
        
        return $monthlyData;
    }

    /**
     * Identify cost savings opportunities
     */
    private function identifyCostSavingsOpportunities($claims)
    {
        $opportunities = [];
        
        // Check for high rejection rates
        $rejectionRate = ($claims->where('status', 'rejected')->count() / $claims->count()) * 100;
        if ($rejectionRate > 20) {
            $opportunities[] = [
                'type' => 'high_rejection_rate',
                'description' => 'High claim rejection rate (' . number_format($rejectionRate, 1) . '%)',
                'potential_savings' => 'Reduce administrative costs and improve approval rates'
            ];
        }
        
        // Check for pre-authorization opportunities
        $preAuthClaims = $claims->filter(function($claim) {
            return $claim->total_amount > 1000; // High-value claims that might benefit from pre-auth
        });
        
        if ($preAuthClaims->count() > 0) {
            $opportunities[] = [
                'type' => 'preauth_opportunity',
                'description' => 'Consider pre-authorization for high-value claims',
                'potential_savings' => 'Reduce claim rejections and processing delays'
            ];
        }
        
        return $opportunities;
    }
}
