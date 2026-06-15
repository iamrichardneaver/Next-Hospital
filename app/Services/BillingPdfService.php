<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Branch;
use App\Models\Setting;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BillingPdfService
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
     * Generate invoice PDF with proper document headers and branding
     */
    public function generateInvoicePdf($invoiceId, $options = [])
    {
        $invoice = Invoice::with([
            'patient',
            'branch',
            'payments',
            'createdBy'
        ])->findOrFail($invoiceId);

        $branding = $this->settingsService->getBrandingSettings();
        
        // Extract logo path for PDF rendering
        $logoPath = $this->extractLogoPath($branding);

        $invoice->loadMissing('branch');

        $data = [
            'invoice' => $invoice,
            'settings' => $this->settings,
            'branding' => $branding,
            'logo_full_path' => $logoPath,
            'documentSettings' => $this->settingsService->getDocumentSettings('invoice'),
            'branch' => $invoice->branch ?? $this->branch,
            'options' => $options,
            'generated_at' => now(),
            'page_title' => 'Invoice - ' . $invoice->invoice_number,
            'pdfService' => $this
        ];

        // Determine template and paper size based on format
        $format = $options['format'] ?? 'a4';
        $template = $format === 'thermal' ? 'reports.invoice-thermal' : 'reports.invoice-simple';
        
        $pdf = Pdf::loadView($template, $data);
        
        if ($format === 'thermal') {
            // Set thermal printer paper size
            $printerWidth = $options['printer_width'] ?? '80mm';
            $pdf->setPaper($printerWidth, 'portrait');
        } else {
            // Set A4 paper size
            $pdf->setPaper('A4', 'portrait');
        }
        
        // Set PDF metadata for A4 format only
        if ($format === 'a4') {
            // Remove encryption settings to avoid mb_strlen error
            // $pdf->getDomPDF()->getCanvas()->get_cpdf()->setEncryption('', ['print', 'modify', 'copy', 'annot-forms']);
        }
        
        return $pdf;
    }

    /**
     * Generate receipt PDF from Payment object
     */
    public function generateReceipt($payment, $options = [])
    {
        // Get invoice from payment
        $invoice = $payment->invoice;
        
        if (!$invoice) {
            abort(404, 'Invoice not found for this payment');
        }
        
        // Load relationships if not already loaded
        if (!$invoice->relationLoaded('patient')) {
            $invoice->load(['patient', 'branch', 'payments', 'createdBy']);
        }

        $branding = $this->settingsService->getBrandingSettings();
        
        // Extract logo path for PDF rendering
        $logoPath = $this->extractLogoPath($branding);

        $data = [
            'invoice' => $invoice,
            'payment' => $payment,
            'settings' => $this->settings,
            'branding' => $branding,
            'logo_full_path' => $logoPath,
            'documentSettings' => $this->settingsService->getDocumentSettings('receipt'),
            'branch' => $invoice->branch ?? $this->branch,
            'options' => $options,
            'generated_at' => now(),
            'page_title' => 'Receipt - ' . $invoice->invoice_number,
            'pdfService' => $this
        ];

        // Determine template and paper size based on format
        $format = $options['format'] ?? 'a4';
        $template = $format === 'thermal' ? 'reports.receipt-thermal' : 'reports.receipt';
        
        $pdf = Pdf::loadView($template, $data);
        
        if ($format === 'thermal') {
            // Set thermal printer paper size
            $printerWidth = $options['printer_width'] ?? '80mm';
            $pdf->setPaper($printerWidth, 'portrait');
        } else {
            // Set A4 paper size
            $pdf->setPaper('A4', 'portrait');
        }
        
        // Set filename
        $filename = $this->generateFilename($invoice, 'receipt');
        
        return $pdf->stream($filename);
    }

    /**
     * Generate receipt PDF from Invoice ID
     */
    public function generateReceiptPdf($invoiceId, $options = [])
    {
        $invoice = Invoice::with([
            'patient',
            'branch',
            'payments',
            'createdBy'
        ])->findOrFail($invoiceId);

        $branding = $this->settingsService->getBrandingSettings();
        
        // Extract logo path for PDF rendering
        $logoPath = $this->extractLogoPath($branding);

        $data = [
            'invoice' => $invoice,
            'settings' => $this->settings,
            'branding' => $branding,
            'logo_full_path' => $logoPath,
            'documentSettings' => $this->settingsService->getDocumentSettings('receipt'),
            'branch' => $this->branch,
            'options' => $options,
            'generated_at' => now(),
            'page_title' => 'Receipt - ' . $invoice->invoice_number,
            'pdfService' => $this
        ];

        // Determine template and paper size based on format
        $format = $options['format'] ?? 'a4';
        $template = $format === 'thermal' ? 'reports.receipt-thermal' : 'reports.receipt';
        
        $pdf = Pdf::loadView($template, $data);
        
        if ($format === 'thermal') {
            // Set thermal printer paper size
            $printerWidth = $options['printer_width'] ?? '80mm';
            $pdf->setPaper($printerWidth, 'portrait');
        } else {
            // Set A4 paper size
            $pdf->setPaper('A4', 'portrait');
        }
        
        return $pdf;
    }

    /**
     * Generate billing statement PDF
     */
    public function generateBillingStatementPdf($patientId, $options = [])
    {
        $patient = \App\Models\Patient::with(['invoices.payments', 'invoices.branch'])->findOrFail($patientId);
        
        $invoices = $patient->invoices()
            ->where('status', '!=', 'cancelled')
            ->orderBy('invoice_date', 'desc')
            ->get();

        $branding = $this->settingsService->getBrandingSettings();
        
        // Extract logo path for PDF rendering
        $logoPath = $this->extractLogoPath($branding);

        $data = [
            'patient' => $patient,
            'invoices' => $invoices,
            'settings' => $this->settings,
            'branding' => $branding,
            'logo_full_path' => $logoPath,
            'documentSettings' => $this->settingsService->getDocumentSettings('billing_statement'),
            'branch' => $this->branch,
            'options' => $options,
            'generated_at' => now(),
            'page_title' => 'Billing Statement - ' . $patient->first_name . ' ' . $patient->last_name
        ];

        $pdf = Pdf::loadView('reports.billing-statement', $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }

    /**
     * Generate standalone bill PDF
     */
    public function generateStandaloneBillPdf($invoice, $options = [])
    {
        $branding = $this->settingsService->getBrandingSettings();
        
        // Extract logo path for PDF rendering
        $logoPath = $this->extractLogoPath($branding);

        $data = [
            'invoice' => $invoice,
            'settings' => $this->settings,
            'branding' => $branding,
            'logo_full_path' => $logoPath,
            'documentSettings' => $this->settingsService->getDocumentSettings('standalone_bill'),
            'branch' => $this->branch,
            'options' => $options,
            'generated_at' => now(),
            'page_title' => 'Standalone Bill - ' . $invoice->invoice_number,
            'isStandalone' => true
        ];

        $pdf = Pdf::loadView('reports.standalone-bill', $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
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
     * Format currency amount
     */
    public function formatCurrency($amount, $currency = 'GHS')
    {
        // Handle different data types
        if (is_array($amount)) {
            $amount = 0;
        } elseif (is_null($amount)) {
            $amount = 0;
        } elseif (is_string($amount)) {
            $amount = (float) $amount;
        }
        
        return $currency . ' ' . number_format($amount, 2);
    }

    /**
     * Get payment method display name
     */
    public function getPaymentMethodName($method)
    {
        return \App\Enums\PaymentMethod::labelFor($method);
    }

    /**
     * Get invoice status display name
     */
    public function getInvoiceStatusName($status)
    {
        $statuses = [
            'draft' => 'Draft',
            'sent' => 'Sent',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
            'partial' => 'Partially Paid'
        ];

        return $statuses[$status] ?? ucfirst($status);
    }

    /**
     * Get status styling
     */
    public function getStatusStyle($status)
    {
        switch ($status) {
            case 'paid':
                return 'background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;';
            case 'overdue':
                return 'background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;';
            case 'sent':
                return 'background-color: #007bff; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;';
            case 'draft':
                return 'background-color: #6c757d; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;';
            case 'cancelled':
                return 'background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;';
            case 'partial':
                return 'background-color: #ffc107; color: #000; padding: 4px 8px; border-radius: 4px; font-weight: bold;';
            default:
                return 'background-color: #6c757d; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;';
        }
    }

    /**
     * Calculate age from date of birth
     */
    public function calculateAge($dateOfBirth)
    {
        return Carbon::parse($dateOfBirth)->age;
    }

    /**
     * Save PDF to storage and return path
     */
    public function savePdfToStorage($pdf, $filename)
    {
        $path = 'invoices/' . $filename;
        Storage::disk('public')->put($path, $pdf->output());
        return $path;
    }

    /**
     * Generate filename for PDF
     */
    public function generateFilename($invoice, $type = 'invoice')
    {
        // Sanitize invoice number by replacing / and \ with hyphens
        $invoiceNumber = str_replace(['/', '\\'], '-', $invoice->invoice_number);
        $patientName = str_replace(' ', '_', $invoice->patient->first_name . '_' . $invoice->patient->last_name);
        $date = now()->format('Y-m-d');
        
        return "{$type}_{$invoiceNumber}_{$patientName}_{$date}.pdf";
    }

    /**
     * Extract logo path from branding settings for PDF rendering
     */
    private function extractLogoPath($branding)
    {
        $src = \App\Services\CrossPlatformService::resolvePdfLogoSrc($branding);
        if ($src) {
            return $src;
        }

        if (!empty($branding['logo_path'])) {
            $fullPath = storage_path('app/public/' . ltrim($branding['logo_path'], '/'));
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    /**
     * Generate QR code for invoice verification
     */
    public function generateQRCode($invoiceId)
    {
        $url = config('app.url') . '/invoices/verify/' . $invoiceId;
        return 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . urlencode($url);
    }

    /**
     * Generate thermal printer invoice PDF
     */
    public function generateThermalInvoicePdf($invoiceId, $printerWidth = '80mm')
    {
        return $this->generateInvoicePdf($invoiceId, [
            'format' => 'thermal',
            'printer_width' => $printerWidth
        ]);
    }

    /**
     * Generate thermal printer receipt PDF
     */
    public function generateThermalReceiptPdf($invoiceId, $printerWidth = '80mm')
    {
        return $this->generateReceiptPdf($invoiceId, [
            'format' => 'thermal',
            'printer_width' => $printerWidth
        ]);
    }

    /**
     * Get available print formats
     */
    public function getAvailableFormats()
    {
        return [
            'a4' => [
                'name' => 'A4 Paper',
                'description' => 'Standard A4 size for regular printers',
                'paper_size' => 'A4',
                'template_suffix' => ''
            ],
            'thermal_80mm' => [
                'name' => 'Thermal 80mm',
                'description' => '80mm thermal printer receipt format',
                'paper_size' => '80mm',
                'template_suffix' => '-thermal'
            ],
            'thermal_58mm' => [
                'name' => 'Thermal 58mm',
                'description' => '58mm thermal printer receipt format',
                'paper_size' => '58mm',
                'template_suffix' => '-thermal'
            ]
        ];
    }

    /**
     * Get print format options for frontend
     */
    public function getPrintFormatOptions()
    {
        return [
            [
                'value' => 'a4',
                'label' => 'A4 Paper',
                'description' => 'Standard A4 size for regular printers',
                'icon' => 'fas fa-file-alt'
            ],
            [
                'value' => 'thermal_80mm',
                'label' => 'Thermal 80mm',
                'description' => '80mm thermal printer receipt format',
                'icon' => 'fas fa-receipt'
            ],
            [
                'value' => 'thermal_58mm',
                'label' => 'Thermal 58mm',
                'description' => '58mm thermal printer receipt format',
                'icon' => 'fas fa-receipt'
            ]
        ];
    }

    /**
     * Parse format options from request
     */
    public function parseFormatOptions($format)
    {
        switch ($format) {
            case 'thermal_80mm':
                return [
                    'format' => 'thermal',
                    'printer_width' => '80mm'
                ];
            case 'thermal_58mm':
                return [
                    'format' => 'thermal',
                    'printer_width' => '58mm'
                ];
            case 'a4':
            default:
                return [
                    'format' => 'a4',
                    'printer_width' => null
                ];
        }
    }

    /**
     * Generate filename with format suffix
     */
    public function generateFormattedFilename($invoice, $type = 'invoice', $format = 'a4')
    {
        $baseFilename = $this->generateFilename($invoice, $type);
        
        if ($format !== 'a4') {
            $extension = pathinfo($baseFilename, PATHINFO_EXTENSION);
            $filenameWithoutExt = pathinfo($baseFilename, PATHINFO_FILENAME);
            $formattedFilename = $filenameWithoutExt . '_' . str_replace('thermal_', '', $format) . '.' . $extension;
            
            // Additional sanitization to ensure no invalid characters
            $formattedFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $formattedFilename);
            
            return $formattedFilename;
        }
        
        // Additional sanitization for base filename
        $baseFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $baseFilename);
        
        return $baseFilename;
    }
}
