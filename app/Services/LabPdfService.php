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

class LabPdfService
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
     * Generate dynamic test results PDF based on template structure
     */
    public function generateDynamicTestResultsPdf($requestId, $templateId, $options = [])
    {
        $labRequest = LabRequest::with([
            'patient',
            'doctor',
            'technician',
            'branch',
            'testType',
            'results' => function($query) use ($templateId) {
                $query->where('template_id', $templateId)
                      ->with(['parameter', 'performedBy', 'verifiedBy', 'approvedBy', 'comments']);
            }
        ])->findOrFail($requestId);

        $template = LabTestTemplate::with('parameters')->findOrFail($templateId);
        
        // Group results by parameter for organized display
        $resultsByParameter = $this->groupResultsByParameter($labRequest->results);
        
        $data = [
            'labRequest' => $labRequest,
            'template' => $template,
            'testType' => $labRequest->testType,
            'resultsByParameter' => $resultsByParameter,
            'settings' => $this->settings,
            'branding' => $this->settingsService->getBrandingSettings(),
            'documentSettings' => $this->settingsService->getDocumentSettings('lab_results'),
            'branch' => $this->branch,
            'options' => $options,
            'generated_at' => now(),
            'page_title' => $template->template_name . ' - Test Results Report',
            'template_type' => $template->template_type,
            'category' => $template->category,
            'pdfService' => $this
        ];

        // Choose the appropriate view based on template type
        $viewName = $this->getViewNameForTemplateType($template->template_type);
        
        $pdf = Pdf::loadView($viewName, $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }

    /**
     * Generate comprehensive test results PDF.
     * Uses template-type-specific layout when there is a single template so that
     * quantitative (Parameter | Result | Unit | Reference Range | Status),
     * qualitative (Parameter | Result | Status), and narrative layouts are correct
     * and results cannot be misinterpreted by patients or clinicians.
     */
    public function generateTestResultsPdf($requestId, $options = [])
    {
        // Check if this is for a patient (from options or context)
        $isPatientRequest = $options['is_patient'] ?? false;
        
        $labRequest = LabRequest::with([
            'patient',
            'doctor',
            'technician',
            'branch',
            'testType',
            'template',
            'results' => function($q) use ($isPatientRequest) {
                // For patients, only load verified and approved results
                if ($isPatientRequest) {
                    $q->whereNotNull('result_verified_at')
                      ->whereNotNull('result_approved_at')
                      ->whereNotNull('result_entered_at');
                }
                $q->with(['parameter', 'template', 'performedBy', 'verifiedBy', 'approvedBy', 'comments']);
            }
        ])->findOrFail($requestId);

        $template = $labRequest->template ?: LabTestTemplate::with('parameters')->find($labRequest->template_id);
        
        // Group results by template (for multi-template) and sort each group by parameter order
        $resultsByTemplate = $this->groupResultsByTemplate($labRequest->results);
        $resultsByTemplate = $this->sortResultsByParameterOrder($resultsByTemplate);
        
        // Single template with known type: use type-specific view so table layout matches template type
        $templateIds = array_keys($resultsByTemplate);
        $knownTypes = ['quantitative', 'qualitative', 'narrative', 'combined'];
        $templateType = $template ? ($template->template_type ?? null) : null;
        if (
            $template
            && count($templateIds) === 1
            && (int) $templateIds[0] === (int) $template->id
            && $templateType
            && in_array($templateType, $knownTypes, true)
        ) {
            $templateResults = $resultsByTemplate[$template->id]['results'] ?? collect();
            $resultsByParameter = $this->groupResultsByParameterSorted($templateResults);
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
                'page_title' => $template->template_name . ' - Laboratory Test Results',
                'template_type' => $templateType,
                'pdfService' => $this
            ];
            $viewName = $this->getViewNameForTemplateType($templateType);
            $pdf = Pdf::loadView($viewName, $data);
            $pdf->setPaper('A4', 'portrait');
            return $pdf;
        }
        
        // Multiple templates or no template: use unified view with per-block type-aware layout
        $data = [
            'labRequest' => $labRequest,
            'template' => $template,
            'resultsByTemplate' => $resultsByTemplate,
            'settings' => $this->settings,
            'branding' => $this->settingsService->getBrandingSettings(),
            'documentSettings' => $this->settingsService->getDocumentSettings('lab_results'),
            'branch' => $this->branch,
            'options' => $options,
            'generated_at' => now(),
            'page_title' => 'Laboratory Test Results Report',
            'pdfService' => $this
        ];

        $pdf = Pdf::loadView('reports.lab-test-results', $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }

    /**
     * Generate individual test result PDF for specific template
     */
    public function generateTemplateResultPdf($requestId, $templateId, $options = [])
    {
        $labRequest = LabRequest::with([
            'patient',
            'doctor',
            'technician',
            'branch',
            'results.parameter',
            'results.performedBy',
            'results.verifiedBy',
            'results.approvedBy',
            'results.comments'
        ])->findOrFail($requestId);

        $template = LabTestTemplate::with('parameters')->findOrFail($templateId);
        $results = $labRequest->results->where('template_id', $templateId);
        
        $data = [
            'labRequest' => $labRequest,
            'template' => $template,
            'results' => $results,
            'settings' => $this->settings,
            'branding' => $this->settingsService->getBrandingSettings(),
            'documentSettings' => $this->settingsService->getDocumentSettings('lab_results'),
            'branch' => $this->branch,
            'options' => $options,
            'generated_at' => now(),
            'page_title' => $template->template_name . ' - Test Results'
        ];

        $pdf = Pdf::loadView('reports.lab-template-results', $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }

    /**
     * Generate critical results alert PDF
     */
    public function generateCriticalResultsPdf($requestId, $options = [])
    {
        $labRequest = LabRequest::with([
            'patient',
            'doctor',
            'technician',
            'branch',
            'results' => function($query) {
                $query->where('result_status', 'critical');
            },
            'results.parameter',
            'results.performedBy'
        ])->findOrFail($requestId);

        $data = [
            'labRequest' => $labRequest,
            'criticalResults' => $labRequest->results,
            'settings' => $this->settings,
            'branding' => $this->settingsService->getBrandingSettings(),
            'documentSettings' => $this->settingsService->getDocumentSettings('lab_results'),
            'branch' => $this->branch,
            'options' => $options,
            'generated_at' => now(),
            'page_title' => 'CRITICAL RESULTS ALERT'
        ];

        $pdf = Pdf::loadView('reports.lab-critical-results', $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }

    /**
     * Generate quality control report PDF
     */
    public function generateQualityControlPdf($qcRecords, $options = [])
    {
        $data = [
            'qcRecords' => $qcRecords,
            'settings' => $this->settings,
            'branding' => $this->settingsService->getBrandingSettings(),
            'documentSettings' => $this->settingsService->getDocumentSettings('lab_results'),
            'branch' => $this->branch,
            'options' => $options,
            'generated_at' => now(),
            'page_title' => 'Quality Control Report'
        ];

        $pdf = Pdf::loadView('reports.lab-quality-control', $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }

    /**
     * Group results by parameter for organized display (order not guaranteed)
     */
    private function groupResultsByParameter($results)
    {
        $grouped = [];
        
        foreach ($results as $result) {
            $parameterId = $result->parameter_id;
            if (!isset($grouped[$parameterId])) {
                $grouped[$parameterId] = [
                    'parameter' => $result->parameter,
                    'result' => $result
                ];
            }
        }
        
        return $grouped;
    }

    /**
     * Group results by parameter in template parameter order (sort_order).
     * Ensures PDF table rows match the template parameter order so results are not misaligned.
     */
    private function groupResultsByParameterSorted($results)
    {
        $collection = $results instanceof \Illuminate\Support\Collection ? $results : collect($results);
        $sorted = $collection->sortBy(function ($result) {
            $p = $result->parameter;
            return $p ? ($p->sort_order ?? 999) : 999;
        });
        return $this->groupResultsByParameter($sorted->values());
    }

    /**
     * Sort each template's results by parameter sort_order within resultsByTemplate.
     */
    private function sortResultsByParameterOrder($resultsByTemplate)
    {
        foreach ($resultsByTemplate as $templateId => &$templateData) {
            $results = $templateData['results'];
            $templateData['results'] = $results instanceof \Illuminate\Support\Collection
                ? $results->sortBy(fn ($r) => $r->parameter ? ($r->parameter->sort_order ?? 999) : 999)->values()
                : collect($results)->sortBy(fn ($r) => $r->parameter ? ($r->parameter->sort_order ?? 999) : 999)->values();
        }
        return $resultsByTemplate;
    }

    /**
     * Get appropriate view name based on test type
     */
    private function getViewNameForTemplateType($templateType)
    {
        switch ($templateType) {
            case 'quantitative':
                return 'reports.lab-quantitative-results';
            case 'qualitative':
                return 'reports.lab-qualitative-results';
            case 'narrative':
                return 'reports.lab-narrative-results';
            case 'combined':
                return 'reports.lab-combined-results';
            default:
                return 'reports.lab-test-results';
        }
    }

    /**
     * Group results by template for multi-template reports
     */
    private function groupResultsByTemplate($results)
    {
        $grouped = [];
        
        foreach ($results as $result) {
            $templateId = $result->template_id;
            if (!isset($grouped[$templateId])) {
                $grouped[$templateId] = [
                    'template' => $result->template,
                    'results' => collect()
                ];
            }
            $grouped[$templateId]['results']->push($result);
        }
        
        return $grouped;
    }

    /**
     * Get system settings
     */
    private function getSettings()
    {
        return Setting::all()->pluck('value', 'key')->toArray();
    }

    /**
     * Get branch information
     */
    private function getBranch()
    {
        // Get current branch or default
        return Branch::first() ?? (object)[
            'name' => 'Main Branch',
            'address' => 'Hospital Address',
            'phone' => 'Phone Number',
            'email' => 'Email Address'
        ];
    }

    /**
     * Format result value based on parameter type
     */
    public function formatResultValue($result, $parameter)
    {
        if ($parameter->input_type === 'rich_text') {
            return $this->sanitizeRichTextForPdf($result->result_value);
        }
        
        if ($parameter->data_type === 'numeric' && $parameter->decimal_places > 0) {
            return number_format(floatval($result->result_value), $parameter->decimal_places);
        }
        
        return $result->result_value;
    }
    
    /**
     * Sanitize rich text content for PDF - preserve formatting tags, remove dangerous content
     */
    private function sanitizeRichTextForPdf($html)
    {
        if (empty($html)) {
            return '';
        }
        
        // Allowed tags for PDF formatting (CKEditor output)
        $allowedTags = '<p><br><strong><b><em><i><u><h1><h2><h3><h4><h5><h6><ul><ol><li><table><thead><tbody><tr><td><th><blockquote><pre><code><span><div>';
        
        // Strip dangerous tags but keep formatting
        $cleaned = strip_tags($html, $allowedTags);
        
        // Remove special characters that might break PDF rendering
        $cleaned = str_replace(['<script', '<iframe', '<object', '<embed', 'javascript:', 'onerror=', 'onclick='], '', $cleaned);
        
        // Clean up excessive whitespace
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = preg_replace('/>\s+</', '><', $cleaned);
        
        // Ensure proper encoding
        $cleaned = htmlspecialchars_decode($cleaned, ENT_QUOTES);
        
        return trim($cleaned);
    }

    /**
     * Get result status with appropriate styling
     */
    public function getResultStatusStyle($result)
    {
        switch ($result->result_status) {
            case 'critical':
                return 'color: #dc3545; font-weight: bold;';
            case 'abnormal':
                return 'color: #fd7e14; font-weight: bold;';
            case 'normal':
                return 'color: #198754;';
            default:
                return 'color: #6c757d;';
        }
    }

    /**
     * Get abnormal flag styling
     */
    public function getAbnormalFlagStyle($flag)
    {
        switch ($flag) {
            case 'CRITICAL':
            case 'PANIC':
                return 'background-color: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-weight: bold;';
            case 'HH':
            case 'LL':
                return 'background-color: #fd7e14; color: white; padding: 2px 6px; border-radius: 3px; font-weight: bold;';
            case 'H':
            case 'L':
                return 'background-color: #ffc107; color: #000; padding: 2px 6px; border-radius: 3px; font-weight: bold;';
            case 'DELTA':
                return 'background-color: #17a2b8; color: white; padding: 2px 6px; border-radius: 3px; font-weight: bold;';
            default:
                return '';
        }
    }

    /**
     * Generate QR code for result verification
     */
    public function generateQRCode($requestId)
    {
        $url = config('app.url') . '/lab/results/verify/' . $requestId;
        return 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . urlencode($url);
    }

    /**
     * Get reference range display text
     */
    public function getReferenceRangeText($result, $parameter)
    {
        if (!$result->reference_range) {
            return 'N/A';
        }
        
        $range = $result->reference_range;
        $unit = $parameter->unit;
        
        if ($unit) {
            return $range . ' ' . $unit;
        }
        
        return $range;
    }

    /**
     * Check if result is within reference range
     */
    public function isWithinReferenceRange($result, $parameter)
    {
        if (!$result->reference_range || $parameter->data_type !== 'numeric') {
            return null;
        }
        
        $value = floatval($result->result_value);
        $range = $result->reference_range;
        
        // Parse range (e.g., "4.5 - 11.0")
        if (preg_match('/(\d+\.?\d*)\s*-\s*(\d+\.?\d*)/', $range, $matches)) {
            $min = floatval($matches[1]);
            $max = floatval($matches[2]);
            return $value >= $min && $value <= $max;
        }
        
        return null;
    }

    /**
     * Get age calculation for patient
     */
    public function calculateAge($dateOfBirth)
    {
        return Carbon::parse($dateOfBirth)->age;
    }

    /**
     * Get priority styling
     */
    public function getPriorityStyle($priority)
    {
        switch ($priority) {
            case 'stat':
                return 'background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;';
            case 'urgent':
                return 'background-color: #fd7e14; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;';
            case 'routine':
                return 'background-color: #6c757d; color: white; padding: 4px 8px; border-radius: 4px;';
            default:
                return 'background-color: #6c757d; color: white; padding: 4px 8px; border-radius: 4px;';
        }
    }

    /**
     * Save PDF to storage and return path
     */
    public function savePdfToStorage($pdf, $filename)
    {
        $path = 'lab-reports/' . $filename;
        Storage::disk('public')->put($path, $pdf->output());
        return $path;
    }

    /**
     * Generate filename for PDF
     */
    public function generateFilename($labRequest, $type = 'results')
    {
        $requestNumber = $labRequest->request_number;
        $patientName = str_replace(' ', '_', $labRequest->patient->first_name . '_' . $labRequest->patient->last_name);
        $date = now()->format('Y-m-d');
        
        return "{$type}_{$requestNumber}_{$patientName}_{$date}.pdf";
    }
}
