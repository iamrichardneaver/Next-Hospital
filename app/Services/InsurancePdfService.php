<?php

namespace App\Services;

use App\Models\InsuranceClaim;
use App\Models\InsurancePolicy;
use App\Models\InsuranceProvider;
use App\Models\PreAuthorization;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class InsurancePdfService
{
    /**
     * Generate comprehensive insurance report
     */
    public function generateInsuranceReport($analytics, $reportType = 'analytics')
    {
        try {
            $data = [
                'analytics' => $analytics,
                'report_type' => $reportType,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'hospital_name' => config('app.name', config('app.name', 'Hospital')),
                'hospital_address' => config('app.hospital_address', ''),
                'hospital_phone' => config('app.hospital_phone', ''),
                'hospital_email' => config('app.hospital_email', ''),
            ];

            $pdf = Pdf::loadView('pdf.insurance-report', $data);
            $pdf->setPaper('A4', 'portrait');
            
            return $pdf;
        } catch (\Exception $e) {
            Log::error('Insurance PDF generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate individual claim report
     */
    public function generateClaimReport(InsuranceClaim $claim)
    {
        try {
            $claim->load([
                'patient',
                'policy.insuranceProvider',
                'claimItems',
                'visit'
            ]);

            $data = [
                'claim' => $claim,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'hospital_name' => config('app.name', config('app.name', 'Hospital')),
                'hospital_address' => config('app.hospital_address', ''),
                'hospital_phone' => config('app.hospital_phone', ''),
                'hospital_email' => config('app.hospital_email', ''),
            ];

            $pdf = Pdf::loadView('pdf.insurance-claim', $data);
            $pdf->setPaper('A4', 'portrait');
            
            return $pdf;
        } catch (\Exception $e) {
            Log::error('Claim PDF generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate pre-authorization report
     */
    public function generatePreAuthorizationReport(PreAuthorization $preAuth)
    {
        try {
            $preAuth->load([
                'patient',
                'policy.insuranceProvider',
                'requestedBy',
                'approvedBy'
            ]);

            $data = [
                'preAuth' => $preAuth,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'hospital_name' => config('app.name', config('app.name', 'Hospital')),
                'hospital_address' => config('app.hospital_address', ''),
                'hospital_phone' => config('app.hospital_phone', ''),
                'hospital_email' => config('app.hospital_email', ''),
            ];

            $pdf = Pdf::loadView('pdf.pre-authorization', $data);
            $pdf->setPaper('A4', 'portrait');
            
            return $pdf;
        } catch (\Exception $e) {
            Log::error('Pre-authorization PDF generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate insurance coverage verification report
     */
    public function generateCoverageVerificationReport($patientId, $serviceType, $serviceCode, $serviceAmount, $coverage)
    {
        try {
            $patient = \App\Models\Patient::with('insurancePolicies.insuranceProvider')->findOrFail($patientId);

            $data = [
                'patient' => $patient,
                'service_type' => $serviceType,
                'service_code' => $serviceCode,
                'service_amount' => $serviceAmount,
                'coverage' => $coverage,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'hospital_name' => config('app.name', config('app.name', 'Hospital')),
                'hospital_address' => config('app.hospital_address', ''),
                'hospital_phone' => config('app.hospital_phone', ''),
                'hospital_email' => config('app.hospital_email', ''),
            ];

            $pdf = Pdf::loadView('pdf.coverage-verification', $data);
            $pdf->setPaper('A4', 'portrait');
            
            return $pdf;
        } catch (\Exception $e) {
            Log::error('Coverage verification PDF generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate provider performance report
     */
    public function generateProviderPerformanceReport($providerId, $dateFrom, $dateTo)
    {
        try {
            $provider = InsuranceProvider::with([
                'claims' => function($query) use ($dateFrom, $dateTo) {
                    $query->whereBetween('submitted_date', [$dateFrom, $dateTo]);
                },
                'insurancePolicies.patient'
            ])->findOrFail($providerId);

            $analytics = $this->calculateProviderAnalytics($provider, $dateFrom, $dateTo);

            $data = [
                'provider' => $provider,
                'analytics' => $analytics,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'hospital_name' => config('app.name', config('app.name', 'Hospital')),
                'hospital_address' => config('app.hospital_address', ''),
                'hospital_phone' => config('app.hospital_phone', ''),
                'hospital_email' => config('app.hospital_email', ''),
            ];

            $pdf = Pdf::loadView('pdf.provider-performance', $data);
            $pdf->setPaper('A4', 'portrait');
            
            return $pdf;
        } catch (\Exception $e) {
            Log::error('Provider performance PDF generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate provider analytics
     */
    private function calculateProviderAnalytics($provider, $dateFrom, $dateTo)
    {
        $claims = $provider->claims;
        
        return [
            'total_claims' => $claims->count(),
            'total_amount' => $claims->sum('total_amount'),
            'total_covered' => $claims->sum('covered_amount'),
            'total_co_pay' => $claims->sum('co_pay_amount'),
            'approved_claims' => $claims->where('status', 'approved')->count(),
            'pending_claims' => $claims->where('status', 'pending')->count(),
            'rejected_claims' => $claims->where('status', 'rejected')->count(),
            'approval_rate' => $claims->count() > 0 ? ($claims->where('status', 'approved')->count() / $claims->count()) * 100 : 0,
            'average_processing_time' => $this->calculateAverageProcessingTime($claims),
            'monthly_breakdown' => $this->calculateMonthlyBreakdown($claims, $dateFrom, $dateTo),
        ];
    }

    /**
     * Calculate average processing time
     */
    private function calculateAverageProcessingTime($claims)
    {
        $processedClaims = $claims->whereNotNull('processed_date');
        
        if ($processedClaims->isEmpty()) {
            return 0;
        }

        $totalDays = $processedClaims->sum(function($claim) {
            return \Carbon\Carbon::parse($claim->submitted_date)
                ->diffInDays(\Carbon\Carbon::parse($claim->processed_date));
        });

        return round($totalDays / $processedClaims->count(), 1);
    }

    /**
     * Calculate monthly breakdown
     */
    private function calculateMonthlyBreakdown($claims, $dateFrom, $dateTo)
    {
        $breakdown = [];
        $current = \Carbon\Carbon::parse($dateFrom);
        $end = \Carbon\Carbon::parse($dateTo);

        while ($current->lte($end)) {
            $monthKey = $current->format('Y-m');
            $monthClaims = $claims->filter(function($claim) use ($current) {
                return \Carbon\Carbon::parse($claim->submitted_date)->format('Y-m') === $current->format('Y-m');
            });

            $breakdown[$monthKey] = [
                'month' => $current->format('M Y'),
                'claims_count' => $monthClaims->count(),
                'total_amount' => $monthClaims->sum('total_amount'),
                'covered_amount' => $monthClaims->sum('covered_amount'),
                'co_pay_amount' => $monthClaims->sum('co_pay_amount'),
            ];

            $current->addMonth();
        }

        return $breakdown;
    }

    /**
     * Generate batch claims report
     */
    public function generateBatchClaimsReport($claimIds, $dateFrom, $dateTo)
    {
        try {
            $claims = InsuranceClaim::with([
                'patient',
                'policy.insuranceProvider',
                'claimItems'
            ])->whereIn('id', $claimIds)->get();

            $data = [
                'claims' => $claims,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'hospital_name' => config('app.name', config('app.name', 'Hospital')),
                'hospital_address' => config('app.hospital_address', ''),
                'hospital_phone' => config('app.hospital_phone', ''),
                'hospital_email' => config('app.hospital_email', ''),
            ];

            $pdf = Pdf::loadView('pdf.batch-claims', $data);
            $pdf->setPaper('A4', 'portrait');
            
            return $pdf;
        } catch (\Exception $e) {
            Log::error('Batch claims PDF generation failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
