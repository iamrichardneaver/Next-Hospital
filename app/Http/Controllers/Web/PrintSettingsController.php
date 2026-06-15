<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\BillingPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PrintSettingsController extends Controller
{
    private $billingPdfService;

    public function __construct(BillingPdfService $billingPdfService)
    {
        $this->billingPdfService = $billingPdfService;
    }

    /**
     * Get available print formats
     */
    public function getFormats()
    {
        try {
            $formats = $this->billingPdfService->getPrintFormatOptions();
            
            return response()->json([
                'success' => true,
                'data' => $formats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving print formats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate invoice PDF with specified format
     */
    public function generateInvoicePdf(Request $request, $invoiceId)
    {
        try {
            $format = $request->get('format', 'a4');
            $options = $this->billingPdfService->parseFormatOptions($format);
            
            $pdf = $this->billingPdfService->generateInvoicePdf($invoiceId, $options);
            
            // Get invoice for filename generation
            $invoice = \App\Models\Invoice::findOrFail($invoiceId);
            $filename = $this->billingPdfService->generateFormattedFilename($invoice, 'invoice', $format);
            
            return response()->streamDownload(function() use ($pdf) {
                echo $pdf->output();
            }, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating invoice PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate receipt PDF with specified format
     */
    public function generateReceiptPdf(Request $request, $invoiceId)
    {
        try {
            $format = $request->get('format', 'a4');
            $options = $this->billingPdfService->parseFormatOptions($format);
            
            $pdf = $this->billingPdfService->generateReceiptPdf($invoiceId, $options);
            
            // Get invoice for filename generation
            $invoice = \App\Models\Invoice::findOrFail($invoiceId);
            $filename = $this->billingPdfService->generateFormattedFilename($invoice, 'receipt', $format);
            
            return response()->streamDownload(function() use ($pdf) {
                echo $pdf->output();
            }, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating receipt PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview invoice in specified format
     */
    public function previewInvoice(Request $request, $invoiceId)
    {
        try {
            $format = $request->get('format', 'a4');
            $options = $this->billingPdfService->parseFormatOptions($format);
            
            $invoice = \App\Models\Invoice::with([
                'patient',
                'branch',
                'payments',
                'createdBy'
            ])->findOrFail($invoiceId);

            $settingsService = new \App\Services\SettingsService();
            $settings = \App\Models\Setting::all()->pluck('value', 'key')->toArray();
            $branding = $settingsService->getBrandingSettings();
            $branch = \App\Models\Branch::first() ?? (object)[
                'name' => 'Main Branch',
                'address' => 'Hospital Address',
                'phone' => 'Phone Number',
                'email' => 'Email Address'
            ];

            $data = [
                'invoice' => $invoice,
                'settings' => $settings,
                'branding' => $branding,
                'documentSettings' => $settingsService->getDocumentSettings('invoice'),
                'branch' => $branch,
                'options' => $options,
                'generated_at' => now(),
                'page_title' => 'Invoice - ' . $invoice->invoice_number,
                'pdfService' => $this->billingPdfService
            ];

            // Determine template based on format
            $template = $format === 'thermal' ? 'reports.invoice-thermal' : 'reports.invoice';
            
            return view($template, $data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error previewing invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview receipt in specified format
     */
    public function previewReceipt(Request $request, $invoiceId)
    {
        try {
            $format = $request->get('format', 'a4');
            $options = $this->billingPdfService->parseFormatOptions($format);
            
            $invoice = \App\Models\Invoice::with([
                'patient',
                'branch',
                'payments',
                'createdBy'
            ])->findOrFail($invoiceId);

            $settingsService = new \App\Services\SettingsService();
            $settings = \App\Models\Setting::all()->pluck('value', 'key')->toArray();
            $branding = $settingsService->getBrandingSettings();
            $branch = \App\Models\Branch::first() ?? (object)[
                'name' => 'Main Branch',
                'address' => 'Hospital Address',
                'phone' => 'Phone Number',
                'email' => 'Email Address'
            ];

            $data = [
                'invoice' => $invoice,
                'settings' => $settings,
                'branding' => $branding,
                'documentSettings' => $settingsService->getDocumentSettings('receipt'),
                'branch' => $branch,
                'options' => $options,
                'generated_at' => now(),
                'page_title' => 'Receipt - ' . $invoice->invoice_number,
                'pdfService' => $this->billingPdfService
            ];

            // Determine template based on format
            $template = $format === 'thermal' ? 'reports.receipt-thermal' : 'reports.receipt';
            
            return view($template, $data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error previewing receipt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Print settings page
     */
    public function index()
    {
        $formats = $this->billingPdfService->getPrintFormatOptions();
        
        return view('print-settings.index', compact('formats'));
    }

    /**
     * Save print settings
     */
    public function store(Request $request)
    {
        $request->validate([
            'default_format' => 'required|in:a4,thermal_80mm,thermal_58mm',
            'auto_print' => 'boolean',
            'printer_name' => 'nullable|string|max:255'
        ]);

        try {
            // Save print settings to database or session
            $settings = [
                'default_format' => $request->default_format,
                'auto_print' => $request->boolean('auto_print'),
                'printer_name' => $request->printer_name
            ];

            // Store in session for now (can be moved to database later)
            session(['print_settings' => $settings]);

            return response()->json([
                'success' => true,
                'message' => 'Print settings saved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving print settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current print settings
     */
    public function getSettings()
    {
        $settings = session('print_settings', [
            'default_format' => 'a4',
            'auto_print' => false,
            'printer_name' => null
        ]);

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }
}
