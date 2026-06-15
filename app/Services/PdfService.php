<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use App\Services\SettingsService;

class PdfService
{
    private $settingsService;

    public function __construct()
    {
        $this->settingsService = new SettingsService();
    }

    /**
     * Generate patient report PDF
     */
    public function generatePatientReport($patient, $consultations = [], $labResults = [])
    {
        $branding = $this->settingsService->getBrandingSettings();
        $documentSettings = $this->settingsService->getDocumentSettings();
        
        $data = [
            'patient' => $patient,
            'consultations' => $consultations,
            'labResults' => $labResults,
            'branding' => $branding,
            'documentSettings' => $documentSettings,
            'hospitalName' => $branding['business_name'] ?? env('HOSPITAL_NAME', 'Next Hospital'),
            'hospitalEmail' => $branding['business_email'] ?? env('HOSPITAL_EMAIL', 'info@nexthospital.com'),
            'generatedAt' => now()->format('Y-m-d H:i:s')
        ];

        $pdf = Pdf::loadView('reports.patient-report', $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }

    /**
     * Generate lab result PDF
     */
    public function generateLabResult($labResult, $patient)
    {
        $branding = $this->settingsService->getBrandingSettings();
        $documentSettings = $this->settingsService->getDocumentSettings();
        
        $data = [
            'labResult' => $labResult,
            'patient' => $patient,
            'branding' => $branding,
            'documentSettings' => $documentSettings,
            'hospitalName' => $branding['business_name'] ?? env('HOSPITAL_NAME', 'Next Hospital'),
            'hospitalEmail' => $branding['business_email'] ?? env('HOSPITAL_EMAIL', 'info@nexthospital.com'),
            'generatedAt' => now()->format('Y-m-d H:i:s')
        ];

        $pdf = Pdf::loadView('reports.lab-result', $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }

    /**
     * Generate invoice PDF
     */
    public function generateInvoice($invoice, $patient, $items = [])
    {
        $branding = $this->settingsService->getBrandingSettings();
        $documentSettings = $this->settingsService->getDocumentSettings();
        
        $data = [
            'invoice' => $invoice,
            'patient' => $patient,
            'items' => $items,
            'branding' => $branding,
            'documentSettings' => $documentSettings,
            'hospitalName' => $branding['business_name'] ?? env('HOSPITAL_NAME', 'Next Hospital'),
            'hospitalEmail' => $branding['business_email'] ?? env('HOSPITAL_EMAIL', 'info@nexthospital.com'),
            'generatedAt' => now()->format('Y-m-d H:i:s')
        ];

        $pdf = Pdf::loadView('reports.invoice', $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }

    /**
     * Generate prescription PDF
     */
    public function generatePrescription($prescription, $patient, $medications = [])
    {
        $branding = $this->settingsService->getBrandingSettings();
        $documentSettings = $this->settingsService->getDocumentSettings();
        
        $data = [
            'prescription' => $prescription,
            'patient' => $patient,
            'medications' => $medications,
            'branding' => $branding,
            'documentSettings' => $documentSettings,
            'settings' => [],
            'branch' => auth()->user()->branch ?? null,
            'generated_at' => now(),
            'page_title' => 'Prescription'
        ];

        $pdf = Pdf::loadView('reports.prescription', $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }

    /**
     * Generate discharge summary PDF
     */
    public function generateDischargeSummary($patient, $admission, $consultations = [])
    {
        $branding = $this->settingsService->getBrandingSettings();
        $documentSettings = $this->settingsService->getDocumentSettings();
        
        $data = [
            'patient' => $patient,
            'admission' => $admission,
            'consultations' => $consultations,
            'branding' => $branding,
            'documentSettings' => $documentSettings,
            'settings' => [],
            'branch' => auth()->user()->branch ?? null,
            'generated_at' => now(),
            'page_title' => 'Discharge Summary'
        ];

        $pdf = Pdf::loadView('reports.discharge-summary', $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }

    /**
     * Generate NHIS report PDF
     */
    public function generateNHISReport($claims, $period)
    {
        $branding = $this->settingsService->getBrandingSettings();
        $documentSettings = $this->settingsService->getDocumentSettings();
        
        $data = [
            'claims' => $claims,
            'period' => $period,
            'branding' => $branding,
            'documentSettings' => $documentSettings,
            'settings' => [],
            'branch' => auth()->user()->branch ?? null,
            'generated_at' => now(),
            'page_title' => 'NHIS Claims Report'
        ];

        $pdf = Pdf::loadView('reports.nhis-report', $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }

    /**
     * Generate GHS report PDF
     */
    public function generateGHSReport($data, $period)
    {
        $branding = $this->settingsService->getBrandingSettings();
        $documentSettings = $this->settingsService->getDocumentSettings();
        
        $reportData = [
            'data' => $data,
            'period' => $period,
            'branding' => $branding,
            'documentSettings' => $documentSettings,
            'settings' => [],
            'branch' => auth()->user()->branch ?? null,
            'generated_at' => now(),
            'page_title' => 'GHS Health Report'
        ];

        $pdf = Pdf::loadView('reports.ghs-report', $reportData);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }
}
