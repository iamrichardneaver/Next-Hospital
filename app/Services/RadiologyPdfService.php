<?php

namespace App\Services;

use App\Models\RadiologyImage;
use App\Models\RadiologyReport;
use App\Models\RadiologyStudy;
use App\Models\Patient;
use App\Models\Branch;
use App\Services\CrossPlatformService;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RadiologyPdfService
{
    private ?array $settings = null;
    private $branch = null;
    private ?SettingsService $settingsService = null;

    private function settingsService(): SettingsService
    {
        return $this->settingsService ??= new SettingsService();
    }

    private function resolvedSettings(): array
    {
        return $this->settings ??= $this->getSettings();
    }

    private function resolvedBranch(): object
    {
        return $this->branch ??= $this->getBranch();
    }

    /**
     * Generate radiology report PDF
     */
    public function generateRadiologyReportPdf($reportId, $options = [])
    {
        $this->ensureGdExtension();

        $report = RadiologyReport::with([
            'study.patient',
            'study.modality',
            'study.equipment',
            'study.technician.user',
            'study.series.images',
            'radiologist',
            'study.request.doctor'
        ])->findOrFail($reportId);

        $reportImages = $this->resolveReportImagesForPdf($report);

        // Ensure all string values are properly cast and handle null relationships
        $branding = $this->settingsService()->getBrandingSettings();
        $documentSettings = $this->settingsService()->getDocumentSettings('radiology_report');
        
        // Ensure branding values are strings (or null), not arrays
        // This prevents mb_strlen() errors when DomPDF processes the template
        // Use aggressive sanitization to catch any nested arrays
        $branding = $this->sanitizeArrayForPdf($branding);
        
        // Double-check critical CSS values that are used in template
        foreach ($branding as $key => $value) {
            if (is_array($value)) {
                $branding[$key] = json_encode($value);
            } elseif (!is_null($value) && !is_string($value) && !is_numeric($value) && !is_bool($value)) {
                $branding[$key] = (string) $value;
            } elseif (is_bool($value)) {
                $branding[$key] = $value ? '1' : '0';
            }
        }
        
        // Sanitize settings array as well
        $settings = $this->sanitizeArrayForPdf($this->resolvedSettings());
        
        // Double-check settings values too
        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                $settings[$key] = json_encode($value);
            } elseif (!is_null($value) && !is_string($value) && !is_numeric($value) && !is_bool($value)) {
                $settings[$key] = (string) $value;
            } elseif (is_bool($value)) {
                $settings[$key] = $value ? '1' : '0';
            }
        }

        $data = [
            'report' => $report,
            'study' => $report->study,
            'patient' => $report->study->patient,
            'modality' => $report->study->modality,
            'equipment' => $report->study->equipment,
            'technician' => $report->study->technician,
            'radiologist' => $report->radiologist,
            'doctor' => $report->study->request?->doctor ?? null,
            'settings' => $settings,
            'branding' => $branding,
            'documentSettings' => $documentSettings,
            'branch' => $this->resolvedBranch(),
            'options' => $this->sanitizeArrayForPdf($options),
            'reportImages' => $reportImages,
            'generated_at' => now(),
            'page_title' => 'Radiology Report'
        ];

        // Final safety check: Ensure ALL values in data arrays are strings/scalars
        // This is critical because DomPDF calls mb_strlen() on values during rendering
        $data['branding'] = array_map(function($value) {
            if (is_array($value)) {
                return json_encode($value);
            }
            if (is_object($value)) {
                return (string) $value;
            }
            return $value === null ? null : (string) $value;
        }, $data['branding']);
        
        $data['settings'] = array_map(function($value) {
            if (is_array($value)) {
                return json_encode($value);
            }
            if (is_object($value)) {
                return (string) $value;
            }
            return $value === null ? null : (string) $value;
        }, $data['settings']);

        $pdf = Pdf::loadView('reports.radiology-report', $data);
        $pdf->setPaper('A4', 'portrait');
        $this->configurePdfOptions($pdf);

        return $pdf;
    }
    
    /**
     * Recursively sanitize arrays to ensure all string values are strings, not arrays
     * This prevents mb_strlen() errors when DomPDF processes the template
     */
    private function sanitizeArrayForPdf($data)
    {
        // Handle arrays recursively
        if (is_array($data)) {
            return array_map(function($value) {
                return $this->sanitizeArrayForPdf($value);
            }, $data);
        }
        
        // Don't modify objects (Eloquent models, Carbon dates, etc.)
        if (is_object($data)) {
            return $data;
        }
        
        // Null values are allowed
        if ($data === null) {
            return null;
        }
        
        // Convert everything else to string (including booleans, integers, floats)
        return (string) $data;
    }

    /**
     * Generate radiology study summary PDF
     */
    public function generateStudySummaryPdf($studyId, $options = [])
    {
        $this->ensureGdExtension();

        $study = RadiologyStudy::with([
            'patient',
            'modality',
            'equipment',
            'technician.user',
            'radiologist',
            'request.doctor',
            'series.images'
        ])->findOrFail($studyId);

        $studyImagesBySeries = $this->resolveStudyImagesForPdf($study, 6);

        $data = [
            'study' => $study,
            'patient' => $study->patient,
            'modality' => $study->modality,
            'equipment' => $study->equipment,
            'technician' => $study->technician,
            'radiologist' => $study->radiologist,
            'doctor' => $study->request->doctor,
            'settings' => $this->resolvedSettings(),
            'branding' => $this->settingsService()->getBrandingSettings(),
            'documentSettings' => $this->settingsService()->getDocumentSettings('radiology_study'),
            'branch' => $this->resolvedBranch(),
            'options' => $options,
            'studyImagesBySeries' => $studyImagesBySeries,
            'generated_at' => now(),
            'page_title' => 'Radiology Study Summary'
        ];

        $pdf = Pdf::loadView('reports.radiology-study-summary', $data);
        $pdf->setPaper('A4', 'portrait');
        $this->configurePdfOptions($pdf);

        return $pdf;
    }

    /**
     * Generate patient radiology history PDF
     */
    public function generatePatientRadiologyHistoryPdf($patientId, $options = [])
    {
        $patient = Patient::with([
            'radiologyRequests.study.report.radiologist',
            'radiologyRequests.modality',
            'radiologyRequests.doctor'
        ])->findOrFail($patientId);

        $data = [
            'patient' => $patient,
            'radiologyHistory' => $patient->radiologyRequests,
            'settings' => $this->resolvedSettings(),
            'branding' => $this->settingsService()->getBrandingSettings(),
            'documentSettings' => $this->settingsService()->getDocumentSettings('radiology_report'),
            'branch' => $this->resolvedBranch(),
            'options' => $options,
            'generated_at' => now(),
            'page_title' => 'Patient Radiology History'
        ];

        $pdf = Pdf::loadView('reports.patient-radiology-history', $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }

    private function getSettings()
    {
        return [
            'hospital_name' => env('HOSPITAL_NAME', 'Next Hospital'),
            'hospital_address' => env('HOSPITAL_ADDRESS', '123 Medical Street, Accra'),
            'hospital_phone' => env('HOSPITAL_PHONE', '+233 24 123 4567'),
            'hospital_email' => env('HOSPITAL_EMAIL', 'info@nexthospital.com'),
        ];
    }

    private function getBranch(): object
    {
        try {
            return Branch::first() ?? $this->defaultBranch();
        } catch (\Throwable) {
            return $this->defaultBranch();
        }
    }

    private function defaultBranch(): object
    {
        return (object) [
            'name' => 'Main Branch',
            'address' => '123 Medical Street, Accra',
            'phone' => '+233 24 123 4567',
            'email' => 'info@nexthospital.com',
        ];
    }

    /**
     * Images selected for the report, or all displayable study images as fallback.
     */
    private function resolveReportImagesForPdf(RadiologyReport $report, int $limit = 12): array
    {
        $selected = $report->getSelectedImagesObjects();
        $images = $selected->isNotEmpty()
            ? $selected
            : $this->collectStudyImages($report->study);

        return $this->mapImagesForPdf($images, $limit);
    }

    /**
     * Group study images by series for the study summary PDF.
     */
    private function resolveStudyImagesForPdf(RadiologyStudy $study, int $perSeriesLimit = 6): array
    {
        $grouped = [];

        foreach ($study->series ?? [] as $series) {
            $images = $this->mapImagesForPdf($series->images ?? collect(), $perSeriesLimit);
            if (empty($images)) {
                continue;
            }

            $totalImages = ($series->images ?? collect())->count();
            $grouped[] = [
                'series_number' => $series->series_number,
                'series_description' => $series->series_description,
                'images' => $images,
                'remaining_count' => max(0, $totalImages - count($images)),
            ];
        }

        return $grouped;
    }

    private function collectStudyImages(?RadiologyStudy $study): Collection
    {
        if (!$study) {
            return collect();
        }

        $images = collect();
        foreach ($study->series ?? [] as $series) {
            foreach ($series->images ?? [] as $image) {
                $images->push($image);
            }
        }

        return $images;
    }

    private function mapImagesForPdf($images, int $limit): array
    {
        $mapped = [];

        foreach ($images as $image) {
            if (count($mapped) >= $limit) {
                break;
            }

            if (!$image instanceof RadiologyImage) {
                continue;
            }

            $base64 = $image->getBase64ForPdf();
            if (!$base64) {
                continue;
            }

            $mapped[] = [
                'id' => $image->id,
                'instance_number' => $image->instance_number,
                'label' => 'Image #' . ($image->instance_number ?? $image->id),
                'base64' => $base64,
            ];
        }

        return $mapped;
    }

    /**
     * DomPDF embeds images via GD; fail with a clear message instead of a fatal error.
     */
    private function ensureGdExtension(): void
    {
        if (!CrossPlatformService::isGdAvailable()) {
            throw new \RuntimeException(
                'PHP GD extension is required to generate radiology PDFs with images. ' .
                'Install php-gd on the server (e.g. sudo apt install php-gd) and restart PHP-FPM/Apache.'
            );
        }
    }

    private function configurePdfOptions($pdf): void
    {
        $pdf->setOption('enable-local-file-access', true);
        $pdf->setOption('enable-remote-file-access', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('defaultFont', 'Arial');
        $pdf->setOption('fontDir', public_path('fonts'));
    }
}
