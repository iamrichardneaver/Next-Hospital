<?php

namespace App\Services;

use App\Models\EyeTestRequest;
use App\Models\EyeTestResult;
use App\Models\EyeTestImage;
use App\Models\Patient;
use App\Models\Branch;
use App\Models\User;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class EyeTestPdfService
{
    private $settingsService;

    public function __construct()
    {
        $this->settingsService = new SettingsService();
    }

    /**
     * Generate PDF for eye test results.
     */
    public function generateEyeTestReport(EyeTestRequest $testRequest): string
    {
        $testRequest->load([
            'patient',
            'appointment.doctor',
            'service',
            'template',
            'testResults.parameter',
            'testImages',
            'assignedTo',
            'resultsVerifiedBy',
            'branch'
        ]);

        $data = [
            'testRequest' => $testRequest,
            'patient' => $testRequest->patient,
            'doctor' => $testRequest->appointment->doctor,
            'optometrist' => $testRequest->assignedTo,
            'verifier' => $testRequest->resultsVerifiedBy,
            'branch' => $testRequest->branch,
            'service' => $testRequest->service,
            'template' => $testRequest->template,
            'results' => $this->organizeResults($testRequest->testResults),
            'images' => $this->organizeImages($testRequest->testImages),
            'generated_at' => now(),
            'report_number' => $this->generateReportNumber($testRequest),
            'settings' => $this->getSettings(),
            'branding' => $this->settingsService->getBrandingSettings(),
            'documentSettings' => $this->settingsService->getDocumentSettings('eye_test'),
            'page_title' => 'Eye Test Results Report'
        ];

        $pdf = Pdf::loadView('reports.eye-test-results', $data);
        $pdf->setPaper('A4', 'portrait');
        
        // Generate filename
        $filename = 'eye_test_report_' . $testRequest->request_number . '_' . now()->format('YmdHis') . '.pdf';
        $filepath = 'reports/eye-tests/' . $filename;
        
        // Save to storage
        Storage::disk('public')->put($filepath, $pdf->output());
        
        return $filepath;
    }

    /**
     * Generate PDF for eye test results with custom template.
     */
    public function generateCustomEyeTestReport(EyeTestRequest $testRequest, string $template = 'standard'): string
    {
        $testRequest->load([
            'patient',
            'appointment.doctor',
            'service',
            'template',
            'testResults.parameter',
            'testImages',
            'assignedTo',
            'resultsVerifiedBy',
            'branch'
        ]);

        $data = [
            'testRequest' => $testRequest,
            'patient' => $testRequest->patient,
            'doctor' => $testRequest->appointment->doctor,
            'optometrist' => $testRequest->assignedTo,
            'verifier' => $testRequest->resultsVerifiedBy,
            'branch' => $testRequest->branch,
            'service' => $testRequest->service,
            'template' => $testRequest->template,
            'results' => $this->organizeResults($testRequest->testResults),
            'images' => $this->organizeImages($testRequest->testImages),
            'generated_at' => now(),
            'report_number' => $this->generateReportNumber($testRequest),
            'template_type' => $template,
            'settings' => $this->getSettings(),
            'branding' => $this->settingsService->getBrandingSettings(),
            'documentSettings' => $this->settingsService->getDocumentSettings('eye_test'),
            'page_title' => 'Eye Test Results Report'
        ];

        $viewName = $this->getTemplateView($template);
        $pdf = Pdf::loadView($viewName, $data);
        $pdf->setPaper('A4', 'portrait');
        
        // Generate filename
        $filename = 'eye_test_report_' . $testRequest->request_number . '_' . $template . '_' . now()->format('YmdHis') . '.pdf';
        $filepath = 'reports/eye-tests/' . $filename;
        
        // Save to storage
        Storage::disk('public')->put($filepath, $pdf->output());
        
        return $filepath;
    }

    /**
     * Generate summary report for multiple eye tests.
     */
    public function generateSummaryReport(array $testRequestIds, string $dateFrom = null, string $dateTo = null): string
    {
        $query = EyeTestRequest::with([
            'patient',
            'appointment.doctor',
            'service',
            'template',
            'testResults.parameter',
            'assignedTo',
            'branch'
        ])->whereIn('id', $testRequestIds);

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $testRequests = $query->get();

        $data = [
            'testRequests' => $testRequests,
            'summary' => $this->generateSummaryData($testRequests),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'generated_at' => now(),
            'report_number' => 'EYE_SUMMARY_' . now()->format('YmdHis'),
            'settings' => $this->getSettings(),
            'branding' => $this->settingsService->getBrandingSettings(),
            'documentSettings' => $this->settingsService->getDocumentSettings('eye_test_summary'),
            'page_title' => 'Eye Test Summary Report'
        ];

        $pdf = Pdf::loadView('reports.eye-test-summary', $data);
        $pdf->setPaper('A4', 'portrait');
        
        // Generate filename
        $filename = 'eye_test_summary_' . now()->format('YmdHis') . '.pdf';
        $filepath = 'reports/eye-tests/summary/' . $filename;
        
        // Save to storage
        Storage::disk('public')->put($filepath, $pdf->output());
        
        return $filepath;
    }

    /**
     * Organize test results by parameter groups.
     */
    private function organizeResults($testResults)
    {
        $organized = [
            'vision' => [],
            'refraction' => [],
            'pressure' => [],
            'fundus' => [],
            'other' => []
        ];

        foreach ($testResults as $result) {
            $category = $this->categorizeResult($result->parameter->parameter_name);
            $organized[$category][] = $result;
        }

        return $organized;
    }

    /**
     * Organize test images by type.
     */
    private function organizeImages($testImages)
    {
        $organized = [];

        foreach ($testImages as $image) {
            $type = $image->image_type;
            if (!isset($organized[$type])) {
                $organized[$type] = [];
            }
            $organized[$type][] = $image;
        }

        return $organized;
    }

    /**
     * Categorize result by parameter name.
     */
    private function categorizeResult(string $parameterName): string
    {
        $parameterName = strtolower($parameterName);

        if (str_contains($parameterName, 'vision') || str_contains($parameterName, 'acuity')) {
            return 'vision';
        }

        if (str_contains($parameterName, 'refraction') || str_contains($parameterName, 'sphere') || 
            str_contains($parameterName, 'cylinder') || str_contains($parameterName, 'axis')) {
            return 'refraction';
        }

        if (str_contains($parameterName, 'pressure') || str_contains($parameterName, 'iop')) {
            return 'pressure';
        }

        if (str_contains($parameterName, 'fundus') || str_contains($parameterName, 'retina') || 
            str_contains($parameterName, 'optic') || str_contains($parameterName, 'macula')) {
            return 'fundus';
        }

        return 'other';
    }

    /**
     * Generate report number.
     */
    private function generateReportNumber(EyeTestRequest $testRequest): string
    {
        return 'ETR-' . $testRequest->request_number . '-' . now()->format('Ymd');
    }

    /**
     * Get template view name.
     */
    private function getTemplateView(string $template): string
    {
        $templates = [
            'standard' => 'reports.eye-test-results',
            'detailed' => 'reports.eye-test-results-detailed',
            'simple' => 'reports.eye-test-results-simple',
            'nhis' => 'reports.eye-test-results-nhis',
        ];

        return $templates[$template] ?? 'reports.eye-test-results';
    }

    /**
     * Get settings for PDF generation
     */
    private function getSettings()
    {
        return [
            'hospital_name' => env('HOSPITAL_NAME', 'Next Hospital'),
            'hospital_address' => env('HOSPITAL_ADDRESS', '123 Medical Street, Accra'),
            'hospital_phone' => env('HOSPITAL_PHONE', '+233 24 123 4567'),
            'hospital_email' => env('HOSPITAL_EMAIL', 'info@nexthospital.com'),
            'hospital_website' => env('HOSPITAL_WEBSITE', ''),
        ];
    }

    /**
     * Generate summary data for multiple test requests.
     */
    private function generateSummaryData($testRequests)
    {
        $summary = [
            'total_tests' => $testRequests->count(),
            'by_status' => $testRequests->groupBy('status')->map->count(),
            'by_service' => $testRequests->groupBy('service.service_name')->map->count(),
            'by_optometrist' => $testRequests->groupBy('assignedTo.firstname')->map->count(),
            'abnormal_results' => 0,
            'critical_results' => 0,
            'average_duration' => $testRequests->whereNotNull('actual_duration_minutes')->avg('actual_duration_minutes'),
        ];

        foreach ($testRequests as $testRequest) {
            $abnormalCount = $testRequest->testResults->where('result_status', 'abnormal')->count();
            $criticalCount = $testRequest->testResults->where('result_status', 'critical')->count();
            
            $summary['abnormal_results'] += $abnormalCount;
            $summary['critical_results'] += $criticalCount;
        }

        return $summary;
    }

    /**
     * Get PDF download URL.
     */
    public function getDownloadUrl(string $filepath): string
    {
        return Storage::disk('public')->url($filepath);
    }

    /**
     * Delete PDF file.
     */
    public function deletePdf(string $filepath): bool
    {
        return Storage::disk('public')->delete($filepath);
    }

    /**
     * Get PDF file size.
     */
    public function getFileSize(string $filepath): int
    {
        return Storage::disk('public')->size($filepath);
    }

    /**
     * Check if PDF exists.
     */
    public function pdfExists(string $filepath): bool
    {
        return Storage::disk('public')->exists($filepath);
    }

    /**
     * Generate QR code for report verification.
     */
    public function generateQrCode(EyeTestRequest $testRequest): string
    {
        $data = [
            'report_number' => $this->generateReportNumber($testRequest),
            'patient_id' => $testRequest->patient->patient_id,
            'test_date' => $testRequest->created_at->format('Y-m-d'),
            'verification_url' => url('/verify-eye-test/' . $testRequest->request_number),
        ];

        return json_encode($data);
    }

    /**
     * Generate barcode for report.
     */
    public function generateBarcode(EyeTestRequest $testRequest): string
    {
        return $testRequest->request_number;
    }
}
