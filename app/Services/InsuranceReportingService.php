<?php

namespace App\Services;

use App\Models\InsuranceProvider;
use App\Models\InsurancePolicy;
use App\Models\InsuranceClaim;
use App\Models\PreAuthorization;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InsuranceReportingService
{
    /**
     * Get comprehensive insurance analytics.
     */
    public function getInsuranceAnalytics($dateFrom = null, $dateTo = null)
    {
        $dateFrom = $dateFrom ?: now()->subDays(30)->toDateString();
        $dateTo = $dateTo ?: now()->toDateString();

        return [
            'overview' => $this->getOverviewStats($dateFrom, $dateTo),
            'providers' => $this->getProviderAnalytics($dateFrom, $dateTo),
            'claims' => $this->getClaimsAnalytics($dateFrom, $dateTo),
            'pre_authorizations' => $this->getPreAuthorizationAnalytics($dateFrom, $dateTo),
            'financial' => $this->getFinancialAnalytics($dateFrom, $dateTo),
            'trends' => $this->getTrendAnalytics($dateFrom, $dateTo)
        ];
    }

    /**
     * Get overview statistics.
     */
    private function getOverviewStats($dateFrom, $dateTo)
    {
        return [
            'total_providers' => InsuranceProvider::count(),
            'active_providers' => InsuranceProvider::where('is_active', true)->count(),
            'total_policies' => InsurancePolicy::whereBetween('start_date', [$dateFrom, $dateTo])->count(),
            'active_policies' => InsurancePolicy::where('is_active', true)->count(),
            'total_claims' => InsuranceClaim::whereBetween('service_date', [$dateFrom, $dateTo])->count(),
            'approved_claims' => InsuranceClaim::where('status', 'approved')
                ->whereBetween('service_date', [$dateFrom, $dateTo])->count(),
            'rejected_claims' => InsuranceClaim::where('status', 'rejected')
                ->whereBetween('service_date', [$dateFrom, $dateTo])->count(),
            'pending_claims' => InsuranceClaim::whereIn('status', ['draft', 'submitted', 'under_review'])
                ->whereBetween('service_date', [$dateFrom, $dateTo])->count()
        ];
    }

    /**
     * Get provider analytics.
     */
    private function getProviderAnalytics($dateFrom, $dateTo)
    {
        $providers = InsuranceProvider::withCount([
            'policies' => function($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('start_date', [$dateFrom, $dateTo]);
            },
            'claims' => function($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('service_date', [$dateFrom, $dateTo]);
            }
        ])->get();

        $topProviders = InsuranceClaim::whereBetween('service_date', [$dateFrom, $dateTo])
            ->join('insurance_providers', 'insurance_claims.insurance_provider_id', '=', 'insurance_providers.id')
            ->selectRaw('
                insurance_providers.id,
                insurance_providers.name,
                insurance_providers.type,
                COUNT(*) as claim_count,
                SUM(insurance_claims.total_amount) as total_amount,
                SUM(insurance_claims.covered_amount) as covered_amount,
                SUM(insurance_claims.co_pay_amount) as co_pay_amount,
                AVG(insurance_claims.total_amount) as avg_claim_amount
            ')
            ->groupBy('insurance_providers.id', 'insurance_providers.name', 'insurance_providers.type')
            ->orderBy('claim_count', 'desc')
            ->limit(10)
            ->get();

        return [
            'providers' => $providers,
            'top_providers' => $topProviders,
            'provider_types' => $this->getProviderTypeDistribution(),
            'coverage_rates' => $this->getCoverageRatesByProvider($dateFrom, $dateTo)
        ];
    }

    /**
     * Get claims analytics.
     */
    private function getClaimsAnalytics($dateFrom, $dateTo)
    {
        $claims = InsuranceClaim::whereBetween('service_date', [$dateFrom, $dateTo])->get();

        $statusDistribution = $claims->groupBy('status')->map(function($group) {
            return $group->count();
        });

        $serviceTypeDistribution = $claims->groupBy('service_type')->map(function($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('total_amount'),
                'covered_amount' => $group->sum('covered_amount'),
                'co_pay_amount' => $group->sum('co_pay_amount')
            ];
        });

        $approvalRate = $this->calculateApprovalRate($claims);

        return [
            'total_claims' => $claims->count(),
            'total_amount' => $claims->sum('total_amount'),
            'covered_amount' => $claims->sum('covered_amount'),
            'co_pay_amount' => $claims->sum('co_pay_amount'),
            'avg_claim_amount' => $claims->avg('total_amount'),
            'status_distribution' => $statusDistribution,
            'service_type_distribution' => $serviceTypeDistribution,
            'approval_rate' => $approvalRate,
            'processing_times' => $this->getProcessingTimes($claims)
        ];
    }

    /**
     * Get pre-authorization analytics.
     */
    private function getPreAuthorizationAnalytics($dateFrom, $dateTo)
    {
        $preAuths = PreAuthorization::whereBetween('request_date', [$dateFrom, $dateTo])->get();

        $statusDistribution = $preAuths->groupBy('status')->map(function($group) {
            return $group->count();
        });

        $urgencyDistribution = $preAuths->groupBy('urgency')->map(function($group) {
            return $group->count();
        });

        $approvalRate = $preAuths->where('status', 'approved')->count() / max($preAuths->count(), 1) * 100;

        return [
            'total_requests' => $preAuths->count(),
            'approved_requests' => $preAuths->where('status', 'approved')->count(),
            'rejected_requests' => $preAuths->where('status', 'rejected')->count(),
            'pending_requests' => $preAuths->whereIn('status', ['pending'])->count(),
            'total_requested_amount' => $preAuths->sum('requested_amount'),
            'total_approved_amount' => $preAuths->sum('approved_amount'),
            'avg_requested_amount' => $preAuths->avg('requested_amount'),
            'avg_approved_amount' => $preAuths->avg('approved_amount'),
            'status_distribution' => $statusDistribution,
            'urgency_distribution' => $urgencyDistribution,
            'approval_rate' => round($approvalRate, 2)
        ];
    }

    /**
     * Get financial analytics.
     */
    private function getFinancialAnalytics($dateFrom, $dateTo)
    {
        $invoices = Invoice::where('billing_type', 'insurance')
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->get();

        $claims = InsuranceClaim::whereBetween('service_date', [$dateFrom, $dateTo])->get();

        return [
            'total_invoice_amount' => $invoices->sum('total_amount'),
            'total_paid_amount' => $invoices->sum('paid_amount'),
            'total_balance_amount' => $invoices->sum('balance_amount'),
            'total_claim_amount' => $claims->sum('total_amount'),
            'total_covered_amount' => $claims->sum('covered_amount'),
            'total_co_pay_amount' => $claims->sum('co_pay_amount'),
            'total_processed_amount' => $claims->sum('processed_amount'),
            'outstanding_amount' => $claims->where('status', 'approved')
                ->sum('processed_amount') - $invoices->sum('paid_amount'),
            'collection_rate' => $this->calculateCollectionRate($invoices),
            'coverage_rate' => $this->calculateCoverageRate($claims)
        ];
    }

    /**
     * Get trend analytics.
     */
    private function getTrendAnalytics($dateFrom, $dateTo)
    {
        $startDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);
        $daysDiff = $startDate->diffInDays($endDate);

        if ($daysDiff <= 30) {
            // Daily trends
            return $this->getDailyTrends($dateFrom, $dateTo);
        } elseif ($daysDiff <= 365) {
            // Monthly trends
            return $this->getMonthlyTrends($dateFrom, $dateTo);
        } else {
            // Yearly trends
            return $this->getYearlyTrends($dateFrom, $dateTo);
        }
    }

    /**
     * Get daily trends.
     */
    private function getDailyTrends($dateFrom, $dateTo)
    {
        $claims = InsuranceClaim::whereBetween('service_date', [$dateFrom, $dateTo])
            ->selectRaw('DATE(service_date) as date, COUNT(*) as count, SUM(total_amount) as total_amount')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $preAuths = PreAuthorization::whereBetween('request_date', [$dateFrom, $dateTo])
            ->selectRaw('DATE(request_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'claims' => $claims,
            'pre_authorizations' => $preAuths,
            'period' => 'daily'
        ];
    }

    /**
     * Get monthly trends.
     */
    private function getMonthlyTrends($dateFrom, $dateTo)
    {
        $claims = InsuranceClaim::whereBetween('service_date', [$dateFrom, $dateTo])
            ->selectRaw('YEAR(service_date) as year, MONTH(service_date) as month, COUNT(*) as count, SUM(total_amount) as total_amount')
            ->groupBy('year', 'month')
            ->orderBy('year', 'month')
            ->get();

        $preAuths = PreAuthorization::whereBetween('request_date', [$dateFrom, $dateTo])
            ->selectRaw('YEAR(request_date) as year, MONTH(request_date) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->orderBy('year', 'month')
            ->get();

        return [
            'claims' => $claims,
            'pre_authorizations' => $preAuths,
            'period' => 'monthly'
        ];
    }

    /**
     * Get yearly trends.
     */
    private function getYearlyTrends($dateFrom, $dateTo)
    {
        $claims = InsuranceClaim::whereBetween('service_date', [$dateFrom, $dateTo])
            ->selectRaw('YEAR(service_date) as year, COUNT(*) as count, SUM(total_amount) as total_amount')
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        $preAuths = PreAuthorization::whereBetween('request_date', [$dateFrom, $dateTo])
            ->selectRaw('YEAR(request_date) as year, COUNT(*) as count')
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        return [
            'claims' => $claims,
            'pre_authorizations' => $preAuths,
            'period' => 'yearly'
        ];
    }

    /**
     * Get provider type distribution.
     */
    private function getProviderTypeDistribution()
    {
        return InsuranceProvider::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');
    }

    /**
     * Get coverage rates by provider.
     */
    private function getCoverageRatesByProvider($dateFrom, $dateTo)
    {
        return InsuranceClaim::whereBetween('service_date', [$dateFrom, $dateTo])
            ->join('insurance_providers', 'insurance_claims.insurance_provider_id', '=', 'insurance_providers.id')
            ->selectRaw('
                insurance_providers.id,
                insurance_providers.name,
                COUNT(*) as total_claims,
                SUM(total_amount) as total_amount,
                SUM(covered_amount) as covered_amount,
                (SUM(covered_amount) / SUM(total_amount)) * 100 as coverage_rate
            ')
            ->groupBy('insurance_providers.id', 'insurance_providers.name')
            ->having('total_claims', '>', 0)
            ->orderBy('coverage_rate', 'desc')
            ->get();
    }

    /**
     * Calculate approval rate.
     */
    private function calculateApprovalRate($claims)
    {
        $totalClaims = $claims->count();
        if ($totalClaims === 0) return 0;

        $approvedClaims = $claims->where('status', 'approved')->count();
        return round(($approvedClaims / $totalClaims) * 100, 2);
    }

    /**
     * Get processing times.
     */
    private function getProcessingTimes($claims)
    {
        $processedClaims = $claims->whereNotNull('processed_date')->whereNotNull('submitted_date');

        if ($processedClaims->isEmpty()) {
            return [
                'avg_processing_days' => 0,
                'min_processing_days' => 0,
                'max_processing_days' => 0
            ];
        }

        $processingTimes = $processedClaims->map(function($claim) {
            return Carbon::parse($claim->submitted_date)->diffInDays(Carbon::parse($claim->processed_date));
        });

        return [
            'avg_processing_days' => round($processingTimes->avg(), 2),
            'min_processing_days' => $processingTimes->min(),
            'max_processing_days' => $processingTimes->max()
        ];
    }

    /**
     * Calculate collection rate.
     */
    private function calculateCollectionRate($invoices)
    {
        $totalAmount = $invoices->sum('total_amount');
        if ($totalAmount === 0) return 0;

        $paidAmount = $invoices->sum('paid_amount');
        return round(($paidAmount / $totalAmount) * 100, 2);
    }

    /**
     * Calculate coverage rate.
     */
    private function calculateCoverageRate($claims)
    {
        $totalAmount = $claims->sum('total_amount');
        if ($totalAmount === 0) return 0;

        $coveredAmount = $claims->sum('covered_amount');
        return round(($coveredAmount / $totalAmount) * 100, 2);
    }

    /**
     * Generate insurance report.
     */
    public function generateInsuranceReport($reportType, $dateFrom, $dateTo, $filters = [])
    {
        switch ($reportType) {
            case 'claims_summary':
                return $this->generateClaimsSummaryReport($dateFrom, $dateTo, $filters);
            case 'provider_performance':
                return $this->generateProviderPerformanceReport($dateFrom, $dateTo, $filters);
            case 'financial_summary':
                return $this->generateFinancialSummaryReport($dateFrom, $dateTo, $filters);
            case 'pre_authorization_summary':
                return $this->generatePreAuthorizationSummaryReport($dateFrom, $dateTo, $filters);
            default:
                throw new \InvalidArgumentException('Invalid report type');
        }
    }

    /**
     * Generate claims summary report.
     */
    private function generateClaimsSummaryReport($dateFrom, $dateTo, $filters)
    {
        $query = InsuranceClaim::with(['patient', 'insuranceProvider', 'policy'])
            ->whereBetween('service_date', [$dateFrom, $dateTo]);

        if (isset($filters['provider_id'])) {
            $query->where('insurance_provider_id', $filters['provider_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $claims = $query->get();

        return [
            'report_type' => 'Claims Summary',
            'period' => "{$dateFrom} to {$dateTo}",
            'total_claims' => $claims->count(),
            'total_amount' => $claims->sum('total_amount'),
            'covered_amount' => $claims->sum('covered_amount'),
            'co_pay_amount' => $claims->sum('co_pay_amount'),
            'claims' => $claims,
            'generated_at' => now()->toDateTimeString()
        ];
    }

    /**
     * Generate provider performance report.
     */
    private function generateProviderPerformanceReport($dateFrom, $dateTo, $filters)
    {
        $providers = InsuranceProvider::withCount([
            'claims' => function($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('service_date', [$dateFrom, $dateTo]);
            }
        ])->get();

        $performance = $providers->map(function($provider) use ($dateFrom, $dateTo) {
            $claims = $provider->claims()->whereBetween('service_date', [$dateFrom, $dateTo])->get();
            
            return [
                'provider' => $provider,
                'total_claims' => $claims->count(),
                'total_amount' => $claims->sum('total_amount'),
                'covered_amount' => $claims->sum('covered_amount'),
                'co_pay_amount' => $claims->sum('co_pay_amount'),
                'avg_claim_amount' => $claims->avg('total_amount'),
                'approval_rate' => $this->calculateApprovalRate($claims)
            ];
        });

        return [
            'report_type' => 'Provider Performance',
            'period' => "{$dateFrom} to {$dateTo}",
            'providers' => $performance,
            'generated_at' => now()->toDateTimeString()
        ];
    }

    /**
     * Generate financial summary report.
     */
    private function generateFinancialSummaryReport($dateFrom, $dateTo, $filters)
    {
        $invoices = Invoice::where('billing_type', 'insurance')
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->get();

        $claims = InsuranceClaim::whereBetween('service_date', [$dateFrom, $dateTo])->get();

        return [
            'report_type' => 'Financial Summary',
            'period' => "{$dateFrom} to {$dateTo}",
            'invoices' => [
                'total_count' => $invoices->count(),
                'total_amount' => $invoices->sum('total_amount'),
                'paid_amount' => $invoices->sum('paid_amount'),
                'balance_amount' => $invoices->sum('balance_amount')
            ],
            'claims' => [
                'total_count' => $claims->count(),
                'total_amount' => $claims->sum('total_amount'),
                'covered_amount' => $claims->sum('covered_amount'),
                'co_pay_amount' => $claims->sum('co_pay_amount'),
                'processed_amount' => $claims->sum('processed_amount')
            ],
            'generated_at' => now()->toDateTimeString()
        ];
    }

    /**
     * Generate pre-authorization summary report.
     */
    private function generatePreAuthorizationSummaryReport($dateFrom, $dateTo, $filters)
    {
        $query = PreAuthorization::with(['patient', 'insuranceProvider', 'policy'])
            ->whereBetween('request_date', [$dateFrom, $dateTo]);

        if (isset($filters['provider_id'])) {
            $query->where('insurance_provider_id', $filters['provider_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $preAuths = $query->get();

        return [
            'report_type' => 'Pre-Authorization Summary',
            'period' => "{$dateFrom} to {$dateTo}",
            'total_requests' => $preAuths->count(),
            'total_requested_amount' => $preAuths->sum('requested_amount'),
            'total_approved_amount' => $preAuths->sum('approved_amount'),
            'approval_rate' => $this->calculateApprovalRate($preAuths),
            'pre_authorizations' => $preAuths,
            'generated_at' => now()->toDateTimeString()
        ];
    }
}
