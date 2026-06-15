<?php

namespace App\Services;

use App\Models\LabRequest;
use App\Models\LabTestResult;
use App\Models\LabTestTemplate;
use App\Models\Patient;
use App\Models\Branch;
use App\Models\Setting;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DiagnosticReportPdfService
{
    private $settings;
    private $branch;
    private $settingsService;

    public function __construct()
    {
        $this->settings = $this->getSettings();
        $this->branch = $this->getBranch();
        $this->settingsService = new SettingsService();
    }

    /**
     * Generate diagnostic report style test results PDF
     */
    public function generateDiagnosticReportPdf($requestId, $templateId = null, $options = [])
    {
        $labRequest = LabRequest::with([
            'patient',
            'doctor',
            'technician',
            'branch',
            'results' => function($query) use ($templateId) {
                if ($templateId) {
                    $query->where('template_id', $templateId);
                }
                $query->with(['parameter', 'performedBy', 'verifiedBy', 'approvedBy']);
            }
        ])->findOrFail($requestId);

        $template = $templateId ? LabTestTemplate::with('parameters')->findOrFail($templateId) : null;
        
        // Group results by parameter for organized display
        $resultsByParameter = $this->groupResultsByParameter($labRequest->results);
        
        $data = [
            'labRequest' => $labRequest,
            'template' => $template,
            'resultsByParameter' => $resultsByParameter,
            'settings' => $this->settings,
            'branding' => $this->settingsService->getBrandingSettings(),
            'documentSettings' => $this->settingsService->getDocumentSettings('lab_results'),
            'branch' => $this->branch,
            'options' => $options,
            'generated_at' => now(),
            'page_title' => 'MEDICAL DIAGNOSTIC REPORT',
            'pdfService' => $this
        ];

        $pdf = Pdf::loadView('reports.diagnostic-report-results', $data);
        $pdf->setPaper('A4', 'portrait');
        
        // Set PDF metadata
        $pdf->getDomPDF()->getCanvas()->get_cpdf()->setEncryption('', ['print', 'modify', 'copy', 'annot-forms']);
        
        return $pdf;
    }

    /**
     * Group results by parameter for organized display
     */
    private function groupResultsByParameter($results)
    {
        $grouped = [];
        
        foreach ($results as $result) {
            $parameterCode = $result->parameter_code ?? $result->parameter_name;
            $grouped[$parameterCode] = $result;
        }
        
        return $grouped;
    }

    /**
     * Determine test type and format results accordingly
     */
    public function formatResultsByType($results, $testType = 'quantitative')
    {
        $formatted = [];
        
        foreach ($results as $code => $result) {
            $formatted[$code] = [
                'parameter_name' => $result->parameter_name,
                'result_value' => $this->formatResultValue($result, $testType),
                'unit' => $result->unit,
                'reference_range' => $result->reference_range,
                'abnormal_flag' => $this->getAbnormalFlag($result),
                'result_status' => $result->result_status,
                'clinical_interpretation' => $result->clinical_interpretation,
                'technical_notes' => $result->technical_notes
            ];
        }
        
        return $formatted;
    }

    /**
     * Format result value based on test type
     */
    private function formatResultValue($result, $testType)
    {
        switch ($testType) {
            case 'qualitative':
                return $this->formatQualitativeResult($result->result_value);
            case 'narrative':
                return $result->formatted_value ?? $result->result_value;
            case 'quantitative':
            default:
                return $this->formatQuantitativeResult($result->result_value, $result->unit);
        }
    }

    /**
     * Format qualitative results (Positive/Negative, Present/Absent, etc.)
     */
    private function formatQualitativeResult($value)
    {
        $value = strtolower(trim($value));
        
        $qualitativeMap = [
            'pos' => 'Positive',
            'neg' => 'Negative',
            'present' => 'Present',
            'absent' => 'Absent',
            'detected' => 'Detected',
            'not detected' => 'Not Detected',
            'reactive' => 'Reactive',
            'non-reactive' => 'Non-Reactive',
            'normal' => 'Normal',
            'abnormal' => 'Abnormal'
        ];
        
        return $qualitativeMap[$value] ?? ucfirst($value);
    }

    /**
     * Format quantitative results with proper decimal places
     */
    private function formatQuantitativeResult($value, $unit = null)
    {
        if (is_numeric($value)) {
            // Determine decimal places based on value
            $decimalPlaces = $this->getDecimalPlaces($value);
            $formatted = number_format((float)$value, $decimalPlaces);
            
            return $unit ? $formatted . ' ' . $unit : $formatted;
        }
        
        return $value;
    }

    /**
     * Get appropriate decimal places for numeric values
     */
    private function getDecimalPlaces($value)
    {
        $value = (float)$value;
        
        if ($value >= 1000) return 0;
        if ($value >= 100) return 1;
        if ($value >= 10) return 2;
        if ($value >= 1) return 2;
        if ($value >= 0.1) return 3;
        if ($value >= 0.01) return 4;
        
        return 5;
    }

    /**
     * Get abnormal flag (H for High, L for Low, empty for normal)
     */
    private function getAbnormalFlag($result)
    {
        if (!$result->abnormal_flag) {
            return '';
        }
        
        $flag = strtoupper($result->abnormal_flag);
        
        switch ($flag) {
            case 'H':
            case 'HH':
                return 'H';
            case 'L':
            case 'LL':
                return 'L';
            case 'CRITICAL':
                return 'CRITICAL';
            default:
                return $flag;
        }
    }

    /**
     * Get system settings
     */
    private function getSettings()
    {
        return Setting::pluck('value', 'key')->toArray();
    }

    /**
     * Get branch information
     */
    private function getBranch()
    {
        return Branch::first();
    }

    /**
     * Generate filename for PDF
     */
    public function generateFilename($labRequest, $suffix = 'results')
    {
        $patientName = str_replace(' ', '_', $labRequest->patient->first_name . '_' . $labRequest->patient->last_name);
        $date = now()->format('Y-m-d');
        
        return "DIAGNOSTIC_REPORT_{$patientName}_{$labRequest->request_number}_{$date}.pdf";
    }

    /**
     * Get patient age in years
     */
    public function getPatientAge($dateOfBirth)
    {
        if (!$dateOfBirth) return null;
        
        return Carbon::parse($dateOfBirth)->age;
    }

    /**
     * Format date for display
     */
    public function formatDate($date, $format = 'd/m/Y')
    {
        if (!$date) return null;
        
        return Carbon::parse($date)->format($format);
    }

    /**
     * Format time for display
     */
    public function formatTime($date, $format = 'H:i A')
    {
        if (!$date) return null;
        
        return Carbon::parse($date)->format($format);
    }

    /**
     * Get sample collection time
     */
    public function getSampleCollectionTime($labRequest)
    {
        // Use created_at as collection time if no specific collection time
        return $labRequest->created_at;
    }

    /**
     * Get report generation time
     */
    public function getReportTime($labRequest)
    {
        // Use the latest result verification time or current time
        $latestResult = $labRequest->results->sortByDesc('result_verified_at')->first();
        
        return $latestResult && $latestResult->result_verified_at 
            ? $latestResult->result_verified_at 
            : now();
    }

    /**
     * Get sample ID (barcode)
     */
    public function getSampleId($labRequest)
    {
        // Generate sample ID based on request number and date
        $date = $labRequest->created_at->format('ymd');
        $requestNumber = str_pad($labRequest->id, 4, '0', STR_PAD_LEFT);
        
        return "BB{$date}{$requestNumber}";
    }

    /**
     * Get registration number
     */
    public function getRegistrationNumber($labRequest)
    {
        return $labRequest->id;
    }

    /**
     * Get referred by information
     */
    public function getReferredBy($labRequest)
    {
        if ($labRequest->doctor) {
            return "Dr. {$labRequest->doctor->first_name} {$labRequest->doctor->last_name}";
        }
        
        return 'SELF';
    }

    /**
     * Get source information
     */
    public function getSource($labRequest)
    {
        if ($labRequest->visit && $labRequest->visit->visit_type) {
            return strtoupper($labRequest->visit->visit_type);
        }
        
        return 'DIRECT';
    }

    /**
     * Get technician who performed the test
     */
    public function getTechnicianName($labRequest)
    {
        $technician = $labRequest->results->first()?->performedBy;
        
        if ($technician) {
            return "{$technician->first_name} {$technician->last_name}";
        }
        
        return $labRequest->technician 
            ? "{$labRequest->technician->first_name} {$labRequest->technician->last_name}"
            : 'Lab Technician';
    }

    /**
     * Get reviewer/approver name
     */
    public function getReviewerName($labRequest)
    {
        $reviewer = $labRequest->results->first()?->verifiedBy ?? $labRequest->results->first()?->approvedBy;
        
        if ($reviewer) {
            return "{$reviewer->first_name} {$reviewer->last_name}";
        }
        
        return 'Technical Officer';
    }

    /**
     * Get reviewer title
     */
    public function getReviewerTitle($labRequest)
    {
        $reviewer = $labRequest->results->first()?->verifiedBy ?? $labRequest->results->first()?->approvedBy;
        
        if ($reviewer && $reviewer->hasRole('pathologist')) {
            return 'Pathologist';
        }
        
        return 'Technical Officer';
    }

    /**
     * Get company name from settings
     */
    public function getCompanyName()
    {
        return $this->settings['hospital_name'] ?? 'Diagnostic Services Ltd.';
    }

    /**
     * Get company address from settings
     */
    public function getCompanyAddress()
    {
        return $this->settings['hospital_address'] ?? 'Hospital Address';
    }

    /**
     * Get company contact information
     */
    public function getCompanyContact()
    {
        return [
            'website' => $this->settings['hospital_website'] ?? 'www.hospital.com',
            'email' => $this->settings['hospital_email'] ?? 'info@hospital.com',
            'phone' => $this->settings['hospital_phone'] ?? 'Phone Number',
            'mobile' => $this->settings['hospital_mobile'] ?? 'Mobile Number',
            'digital_address' => $this->settings['hospital_digital_address'] ?? 'Digital Address'
        ];
    }
}
