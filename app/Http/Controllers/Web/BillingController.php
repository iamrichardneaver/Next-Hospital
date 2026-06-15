<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Http\Controllers\Concerns\ProcessesInvoiceItems;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Http\Controllers\Concerns\WorkflowNavigation;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Branch;
use App\Services\BillingPdfService;
use App\Services\PaymentPolicyService;
use App\Services\PaymentService;
use App\Services\PricingService;
use App\Support\PaymentMetadata;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BillingController extends Controller
{
    use ExportsListData, WorkflowNavigation, ResolvesUserBranch, ProcessesInvoiceItems;

    protected PricingService $pricingService;
    protected PaymentService $paymentService;
    protected PaymentPolicyService $paymentPolicyService;

    public function __construct(
        PricingService $pricingService = null,
        PaymentService $paymentService = null,
        PaymentPolicyService $paymentPolicyService = null
    ) {
        $this->pricingService = $pricingService ?? app(PricingService::class);
        $this->paymentService = $paymentService ?? app(PaymentService::class);
        $this->paymentPolicyService = $paymentPolicyService ?? app(PaymentPolicyService::class);
    }
    /**
     * Display listing of invoices
     */
    public function index()
    {
        $branchId = $this->resolveUserBranchId(['view_invoices', 'manage_billing']);
        $portalPatient = $this->portalPatient();
        
        // Fetch invoices with relationships (server-side) - scoped by branch
        $query = Invoice::with(['patient', 'branch', 'creator'])
            ->where('branch_id', $branchId);

        if ($portalPatient) {
            $query->where('patient_id', $portalPatient->id);
        }

        $invoices = $query->latest('id')->paginate(20);
        
        // Ensure patient relationship is loaded (even if patient was deleted)
        $invoices->getCollection()->transform(function($invoice) {
            // If patient relationship is null, try to reload it
            if (!$invoice->patient && $invoice->patient_id) {
                $invoice->load('patient');
            }
            return $invoice;
        });
        
        // Get statistics - scoped by branch
        $statsQuery = Invoice::where('branch_id', $branchId);
        if ($portalPatient) {
            $statsQuery->where('patient_id', $portalPatient->id);
        }

        $statistics = [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'paid' => (clone $statsQuery)->where('status', 'paid')->count(),
            'total_revenue' => (clone $statsQuery)->where('status', 'paid')->sum('total_amount'),
        ];
        
        return view('billing.index', compact('invoices', 'statistics'));
    }
    
    /**
     * Show the form for creating invoice
     */
    public function create()
    {
        $user = auth()->user();

        abort_unless(
            $user->can('create_invoices') || $user->can('manage_billing'),
            403,
            'You do not have permission to create invoices.'
        );

        // Load only pre-selected patient; others are fetched via AJAX search
        $preselectedPatientId = old('patient_id', request('patient_id'));
        $patients = collect();
        if ($preselectedPatientId) {
            $patient = Patient::find($preselectedPatientId);
            if ($patient) {
                $patients = collect([$patient]);
            }
        }
        
        // Get user's branches and determine default branch
        $userBranches = $user->branches()->where('branches.is_active', true)->get();
        $defaultBranch = $user->getDefaultBranch();
        
        // For super admin, show all branches; for others, show only their assigned branches
        if ($user->isSuperAdmin()) {
            $branches = Branch::where('is_active', true)->get();
        } else {
            $branches = $userBranches;
        }
        
        return view('billing.create', compact('patients', 'branches', 'defaultBranch', 'user', 'preselectedPatientId'));
    }
    
    /**
     * Store a newly created invoice
     */
    public function store(Request $request)
    {
        abort_unless(
            auth()->user()->can('create_invoices') || auth()->user()->can('manage_billing'),
            403,
            'You do not have permission to create invoices.'
        );

        $manualItems = $this->filterEmptyManualItems($request->input('items', []));
        $request->merge(['items' => $manualItems ?: null]);

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.total' => 'required_with:items|numeric|min:0',
            'selected_services' => 'nullable|array',
            'selected_services.*.id' => 'required_with:selected_services|string',
            'selected_services.*.name' => 'required_with:selected_services|string',
            'selected_services.*.price' => 'required_with:selected_services|numeric|min:0',
            'selected_services.*.quantity' => 'required_with:selected_services|numeric|min:0.01',
            'selected_services.*.type' => 'required_with:selected_services|string',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|' . PaymentMethod::validationRule(),
            'notes' => 'nullable|string',
        ]);

        $this->assertResourceInUserBranch((int) $validated['branch_id'], ['view_invoices', 'manage_billing', 'create_invoices']);
        
        // Ensure we have either items or selected services
        if (empty($validated['items']) && empty($validated['selected_services'])) {
            return back()
                ->withInput()
                ->with('error', 'Please add at least one item or service to the invoice.');
        }
        
        // Set default dates if not provided
        $validated['invoice_date'] = $validated['invoice_date'] ?? Carbon::today();
        $validated['due_date'] = $validated['due_date'] ?? Carbon::today()->addDays(30);
        
        // Process and validate items
        if (!empty($validated['items'])) {
            $validated['items'] = $this->processInvoiceItems($validated['items']);
        }
        
        // Process selected services and convert to items
        if (!empty($validated['selected_services'])) {
            $serviceItems = $this->processSelectedServices($validated['selected_services']);
            $validated['items'] = array_merge($validated['items'] ?? [], $serviceItems);
        }
        
        // Recalculate totals based on processed items; apply tax and discount from form
        $totals = $this->calculateTotals($validated['items'] ?? []);
        $validated['subtotal'] = $totals['subtotal'];
        $tax = (float) ($validated['tax_amount'] ?? 0);
        $discount = (float) ($validated['discount_amount'] ?? 0);
        $validated['total_amount'] = $totals['subtotal'] + $tax - $discount;
        
        $validated['status'] = 'pending';
        $validated['created_by'] = auth()->id();
        
        try {
            $invoice = Invoice::create($validated);

            if ($request->filled('payment_method')) {
                $paymentResult = $this->paymentService->recordPayment(
                    $invoice->id,
                    (float) $validated['total_amount'],
                    $validated['payment_method'],
                    PaymentMetadata::fromRequest($request, [
                        'notes' => $validated['notes'] ?? null,
                        'processed_by' => auth()->id(),
                        'source_platform' => 'web',
                        'ip_address' => $request->ip(),
                    ])
                );

                if (!$paymentResult['success']) {
                    throw new \Exception($paymentResult['message'] ?? 'Payment recording failed.');
                }

                $invoice = $paymentResult['invoice'] ?? $invoice->fresh();
            }
            
            // Try to find active visit for patient to complete workflow
            $activeVisit = \App\Models\Visit::where('patient_id', $validated['patient_id'])
                ->where('status', 'active')
                ->whereIn('visit_type', ['OPD', 'IPD', 'Emergency'])
                ->latest()
                ->first();
            
            // Complete workflow step if visit has workflow
            if ($activeVisit) {
                $this->completeWorkflowStep($activeVisit, 'billing', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'total_amount' => $invoice->total_amount,
                ]);
            }
            
            // Use workflow navigation if available
            if ($activeVisit) {
                return $this->redirectToNextStep($activeVisit, 'Invoice created successfully! Invoice #: ' . $invoice->invoice_number);
            }
            
            return redirect()->route('billing.show', $invoice)
                ->with('success', 'Invoice created successfully! Invoice #: ' . $invoice->invoice_number);
        } catch (\Exception $e) {
            \Log::error('Failed to create invoice', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create invoice: ' . $e->getMessage());
        }
    }
    
    /**
     * Display the specified invoice
     */
    public function show(Invoice $billing)
    {
        $this->assertPortalPatientOwns($billing->patient_id);
        $this->assertResourceInUserBranch((int) $billing->branch_id, ['view_invoices', 'manage_billing']);

        // Load relationships - patient may be null if deleted
        $billing->load(['patient', 'branch', 'creator']);
        
        // If patient is null but patient_id exists, try to reload
        if (!$billing->patient && $billing->patient_id) {
            $billing->load('patient');
        }
        
        return view('billing.show', compact('billing'));
    }
    
    /**
     * Show the form for editing invoice
     */
    public function edit(Invoice $billing)
    {
        $this->assertResourceInUserBranch((int) $billing->branch_id, ['view_invoices', 'manage_billing']);

        $patients = Patient::latest()->get();
        $branches = Branch::where('is_active', true)->get();
        
        return view('billing.edit', compact('billing', 'patients', 'branches'));
    }
    
    /**
     * Update the specified invoice
     */
    public function update(Request $request, Invoice $billing)
    {
        $this->assertResourceInUserBranch((int) $billing->branch_id, ['view_invoices', 'manage_billing']);

        $validated = $request->validate([
            'status' => 'required|in:draft,pending,paid,cancelled,refunded',
            'payment_method' => 'nullable|' . PaymentMethod::validationRule(),
            'notes' => 'nullable|string',
        ]);
        
        try {
            $billing->update($validated);
            
            return redirect()->route('billing.show', $billing)
                ->with('success', 'Invoice updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update invoice.');
        }
    }
    
    /**
     * Remove the specified invoice
     */
    public function destroy(Invoice $billing)
    {
        $this->assertResourceInUserBranch((int) $billing->branch_id, ['view_invoices', 'manage_billing']);

        try {
            $invoiceNumber = $billing->invoice_number;
            $billing->delete();
            
            return redirect()->route('billing.index')
                ->with('success', 'Invoice ' . $invoiceNumber . ' deleted successfully!');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete invoice.');
        }
    }

    /**
     * Print invoice or receipt
     */
    public function print(Invoice $billing, Request $request)
    {
        $this->assertPortalPatientOwns($billing->patient_id);
        $this->assertResourceInUserBranch((int) $billing->branch_id, ['view_invoices', 'manage_billing']);

        $format = $request->get('format', 'a4');
        $type = $request->get('type', 'invoice');

        try {
            $pdfService = new BillingPdfService();
            
            if ($type === 'receipt') {
                $pdf = $pdfService->generateReceiptPdf($billing->id, $pdfService->parseFormatOptions($format));
            } else {
                $pdf = $pdfService->generateInvoicePdf($billing->id, $pdfService->parseFormatOptions($format));
            }
            
            $filename = $pdfService->generateFormattedFilename($billing, $type, $format);
            
            return $pdf->stream($filename);
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Download invoice or receipt as PDF
     */
    public function download(Invoice $billing, Request $request)
    {
        $this->assertPortalPatientOwns($billing->patient_id);
        $this->assertResourceInUserBranch((int) $billing->branch_id, ['view_invoices', 'manage_billing']);

        $format = $request->get('format', 'a4');
        $type = $request->get('type', 'invoice');

        try {
            $pdfService = new BillingPdfService();
            
            if ($type === 'receipt') {
                $pdf = $pdfService->generateReceiptPdf($billing->id, $pdfService->parseFormatOptions($format));
            } else {
                $pdf = $pdfService->generateInvoicePdf($billing->id, $pdfService->parseFormatOptions($format));
            }
            
            $filename = $pdfService->generateFormattedFilename($billing, $type, $format);
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Record a payment against an invoice (staff / debtors module).
     */
    public function recordPayment(Request $request, Invoice $billing)
    {
        $this->assertResourceInUserBranch((int) $billing->branch_id, ['view_invoices', 'manage_billing', 'process_payments']);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . max(0.01, (float) $billing->balance_amount),
            'payment_method' => 'required|' . PaymentMethod::validationRule(true),
            'notes' => 'nullable|string',
            'amount_tendered' => 'nullable|numeric|min:0',
            'momo_phone' => 'nullable|string',
            'momo_network' => 'nullable|in:MTN,Vodafone,AirtelTigo',
            'momo_reference' => 'nullable|string',
            'reference_number' => 'nullable|string',
        ]);

        try {
            $activeVisit = \App\Models\Visit::where('patient_id', $billing->patient_id)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            $this->paymentPolicyService->validatePaymentAmount(
                $activeVisit,
                $billing,
                (float) $validated['amount']
            );

            $result = $this->paymentService->recordPayment(
                $billing->id,
                (float) $validated['amount'],
                $validated['payment_method'],
                PaymentMetadata::fromRequest($request, [
                    'notes' => $validated['notes'] ?? null,
                    'processed_by' => auth()->id(),
                    'source_platform' => 'web',
                    'ip_address' => $request->ip(),
                    'visit' => $activeVisit,
                ])
            );

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment recorded successfully',
                    'payment' => $result['payment'],
                    'invoice' => $result['invoice'],
                ]);
            }

            return back()->with('success', 'Payment recorded successfully.');
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to record payment.');
        }
    }

    /**
     * Get services by type for dynamic loading
     */
    public function getServices(Request $request)
    {
        $type = $request->get('type');
        $search = $request->get('search', '');
        
        $services = collect();
        
        switch ($type) {
            case 'lab_tests':
                $services = \App\Models\LabTest::where('is_active', true)
                    ->where('test_name', 'like', "%{$search}%")
                    ->get(['id', 'test_name as name', 'description', 'cost as base_price'])
                    ->map(function($item) {
                        $item->service_type = 'lab_test';
                        return $item;
                    });
                break;
                
            case 'drugs':
                $services = \App\Models\Drug::where('is_active', true)
                    ->where('name', 'like', "%{$search}%")
                    ->get(['id', 'name', 'description', 'selling_price as base_price'])
                    ->map(function($item) {
                        $item->service_type = 'drug';
                        return $item;
                    });
                break;
                
            case 'service_pricing':
                $services = \App\Models\ServicePricing::where('is_active', true)
                    ->where('service_name', 'like', "%{$search}%")
                    ->get(['id', 'service_name as name', 'description', 'base_price', 'service_type']);
                break;
                
                
            case 'consultation':
                $services = \App\Models\AppointmentFee::where('is_active', true)
                    ->where('fee_category', 'like', "%{$search}%")
                    ->get(['id', 'fee_category as name', 'description', 'base_fee as base_price'])
                    ->map(function($item) {
                        $item->service_type = 'consultation';
                        return $item;
                    });
                break;
                
            case 'radiology':
                // Use ServicePricing model for radiology services
                $services = \App\Models\ServicePricing::where('is_active', true)
                    ->where('service_type', 'radiology')
                    ->where('service_name', 'like', "%{$search}%")
                    ->get(['id', 'service_name as name', 'description', 'base_price', 'service_type']);
                break;
        }
        
        return response()->json($services);
    }

    public function export(Request $request)
    {
        $branchId = $this->resolveUserBranchId(['view_invoices', 'manage_billing']);

        $query = Invoice::with(['patient', 'branch'])
            ->where('branch_id', $branchId)
            ->latest('id');

        return $this->exportFromQuery($request, $query, [
            'Invoice #' => 'invoice_number',
            'Patient' => fn ($i) => $i->patient?->full_name ?? '',
            'Patient Number' => fn ($i) => $i->patient?->patient_number ?? '',
            'Branch' => fn ($i) => $i->branch?->name ?? '',
            'Total Amount' => 'total_amount',
            'Amount Paid' => 'paid_amount',
            'Balance' => 'balance',
            'Status' => 'status',
            'Invoice Date' => fn ($i) => $this->formatExportDate($i->invoice_date),
        ], 'invoices', 'view_invoices');
    }
}
