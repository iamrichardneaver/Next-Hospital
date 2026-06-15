<?php

namespace App\Http\Controllers\API;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Support\PaymentMetadata;
use App\Http\Controllers\Concerns\ProcessesInvoiceItems;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Http\Controllers\Concerns\WorkflowNavigation;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Patient;
use App\Models\Consultation;
use App\Models\Prescription;
use App\Models\LabRequest;
use App\Models\Visit;
use App\Models\InsuranceProvider;
use App\Models\InsurancePolicy;
use App\Models\InsuranceClaim;
use App\Models\Branch;
use App\Models\PaymentSetting;
use App\Services\BillingPdfService;
use App\Services\PaymentService;
use App\Services\PricingService;
use App\Services\ModulePricingService;
use App\Services\PendingChargesService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BillingController extends Controller
{
    use WorkflowNavigation, ResolvesUserBranch, ProcessesInvoiceItems;
    protected $paymentService;
    protected $pricingService;
    protected $modulePricingService;
    protected $pendingChargesService;

    public function __construct(
        PaymentService $paymentService,
        PricingService $pricingService,
        ModulePricingService $modulePricingService = null,
        PendingChargesService $pendingChargesService = null
    ) {
        $this->paymentService = $paymentService;
        $this->pricingService = $pricingService;
        $this->modulePricingService = $modulePricingService ?? app(ModulePricingService::class);
        $this->pendingChargesService = $pendingChargesService ?? app(PendingChargesService::class);
    }

    /**
     * Update invoice status based on payments
     * NOTE: This method is now DEPRECATED - PaymentObserver handles this automatically
     * Kept for backward compatibility but should not be called
     * @deprecated Use PaymentService->recordPayment() instead
     */
    private function updateInvoiceStatus(Invoice $invoice)
    {
        // PaymentObserver now handles this automatically
        // This method is kept for backward compatibility only
        // Calculate total paid from all completed payments
        $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');

        // Update status based on paid amount
        if ($totalPaid >= $invoice->total_amount) {
            $invoice->status = 'paid';
        } elseif ($totalPaid > 0) {
            $invoice->status = 'partial';
        } else {
            // Keep current status if no payments
            if ($invoice->status === 'paid' || $invoice->status === 'partial') {
                $invoice->status = 'pending';
            }
        }

        $invoice->save();
    }

    /**
     * Display a listing of invoices.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['patient', 'branch', 'payments'])
            ->orderBy('id', 'desc');

        $user = auth()->user();
        if ($user && $user->hasRole('patient')) {
            $patient = $user->patient;
            if ($patient) {
                $query->where('patient_id', $patient->id);
            } else {
                // If patient record doesn't exist, return empty result
                $query->where('patient_id', -1); // Impossible ID to return empty
            }
        } elseif ($user && !$user->hasRole('super_admin')) {
            $branchId = $user->staffProfile?->branch_id
                ?? $user->branches()->first()?->id
                ?? ($user->current_branch_id ?? null);
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        // Search by patient name or invoice number
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('patient_number', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query->paginate(20);

        // Transform the data to match frontend expectations
        $transformedInvoices = $invoices->getCollection()->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'patient_id' => $invoice->patient_id,
                'patient_name' => $invoice->patient ? $invoice->patient->first_name . ' ' . $invoice->patient->last_name : 'Unknown Patient',
                'total_amount' => $invoice->total_amount,
                'paid_amount' => $invoice->payments->sum('amount'),
                'balance' => $invoice->total_amount - $invoice->payments->sum('amount'),
                'status' => $invoice->status,
                'due_date' => $invoice->due_date,
                'created_at' => $invoice->created_at,
                'updated_at' => $invoice->updated_at,
                'services' => $invoice->items ? array_map(function ($item) {
                    return [
                        'id' => $item['id'] ?? 'item_' . uniqid(),
                        'name' => $item['description'] ?? 'Service',
                        'description' => $item['description'] ?? '',
                        'quantity' => $item['quantity'] ?? 1,
                        'unit_price' => $item['unit_price'] ?? 0,
                        'total_price' => $item['total'] ?? 0,
                        'service_type' => $item['service_type'] ?? 'general'
                    ];
                }, $invoice->items) : []
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedInvoices,
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
            'message' => 'Invoices retrieved successfully'
        ]);
    }

    /**
     * Store a newly created invoice.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after:invoice_date',
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
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Ensure we have either items or selected services
        if (empty($request->items) && empty($request->selected_services)) {
            return response()->json([
                'success' => false,
                'message' => 'Please add at least one item or service to the invoice.'
            ], 422);
        }

        // Process and validate items
        $items = [];
        if (!empty($request->items)) {
            $items = $this->processInvoiceItems($request->items);
        }

        // Process selected services and convert to items
        if (!empty($request->selected_services)) {
            $serviceItems = $this->processSelectedServices($request->selected_services);
            $items = array_merge($items, $serviceItems);
        }

        // Recalculate totals based on processed items
        $taxAmount = floatval($request->tax_amount ?? 0);
        $discountAmount = floatval($request->discount_amount ?? 0);
        $totals = $this->calculateTotals($items, $taxAmount, $discountAmount);
        $subtotal = $totals['subtotal'];
        $totalAmount = $totals['total'];

        $this->assertResourceInUserBranch((int) $request->branch_id, ['view_invoices', 'manage_billing']);

        $invoice = Invoice::create([
            'patient_id' => $request->patient_id,
            'branch_id' => $request->branch_id,
            'invoice_date' => $request->invoice_date,
            'due_date' => $request->due_date ?? Carbon::parse($request->invoice_date)->addDays(30)->toDateString(),
            'items' => $items,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'status' => $request->payment_method ? 'paid' : 'pending',
            'payment_method' => $request->payment_method,
            'notes' => $request->notes,
            'created_by' => auth()->id()
        ]);

        // Try to find active visit for patient to complete workflow
        $activeVisit = Visit::where('patient_id', $request->patient_id)
            ->where('status', 'active')
            ->whereIn('visit_type', ['OPD', 'IPD', 'Emergency'])
            ->latest()
            ->first();

        // Complete workflow step if visit has workflow
        if ($activeVisit && $activeVisit->workflowInstance) {
            $this->completeWorkflowStep($activeVisit, 'billing', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total_amount' => $invoice->total_amount,
            ]);
        }

        // Get workflow next step suggestion
        $response = [
            'success' => true,
            'data' => $invoice->load(['patient', 'branch']),
            'message' => 'Invoice created successfully'
        ];

        if ($activeVisit && $activeVisit->workflowInstance) {
            $workflowResponse = $this->getNextStepResponse($activeVisit, 'Invoice created successfully');
            $response['workflow'] = $workflowResponse->getData(true)['workflow'] ?? null;
        }

        return response()->json($response, 201);
    }

    /**
     * Display the specified invoice.
     */
    public function show($id)
    {
        $user = auth()->user();
        $invoice = Invoice::with(['patient', 'branch', 'payments', 'createdBy'])->findOrFail($id);

        // If user is a patient, verify ownership
        if ($user->hasRole('patient')) {
            $patient = $user->patient;
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient profile not found for this user'
                ], 404);
            }
            
            // Verify this invoice belongs to the patient
            if ($invoice->patient_id !== $patient->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this invoice'
                ], 403);
            }
        }

        // Calculate paid amount and balance
        $totalPaid = $invoice->payments->sum('amount');
        $balance = $invoice->total_amount - $totalPaid;

        // Transform invoice data (contract-aligned for mobile InvoiceModel)
        $invoiceData = [
            'id' => $invoice->id,
            'patient_id' => $invoice->patient_id,
            'branch_id' => $invoice->branch_id,
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date?->format('Y-m-d') ?? (is_object($invoice->invoice_date) ? $invoice->invoice_date->format('Y-m-d') : $invoice->invoice_date),
            'due_date' => $invoice->due_date ? (is_object($invoice->due_date) ? $invoice->due_date->format('Y-m-d') : $invoice->due_date) : null,
            'subtotal' => (float) ($invoice->subtotal ?? $invoice->total_amount),
            'tax_amount' => (float) ($invoice->tax_amount ?? 0),
            'discount_amount' => (float) ($invoice->discount_amount ?? 0),
            'total_amount' => (float) $invoice->total_amount,
            'paid_amount' => $totalPaid,
            'balance' => $balance,
            'status' => $invoice->status,
            'payment_status' => $invoice->payment_status,
            'payment_method' => $invoice->payment_method,
            'items' => $invoice->items ?? [],
            'notes' => $invoice->notes,
            'created_at' => $invoice->created_at,
            'updated_at' => $invoice->updated_at,
            'patient' => $invoice->patient ? [
                'id' => $invoice->patient->id,
                'first_name' => $invoice->patient->first_name,
                'last_name' => $invoice->patient->last_name,
                'patient_number' => $invoice->patient->patient_number,
                'phone' => $invoice->patient->phone,
                'email' => $invoice->patient->email,
            ] : null,
            'branch' => $invoice->branch ? [
                'id' => $invoice->branch->id,
                'name' => $invoice->branch->name,
                'address' => $invoice->branch->address,
                'phone' => $invoice->branch->phone,
            ] : null,
            'payments' => $invoice->payments->map(function ($payment) use ($invoice) {
                return [
                    'id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'status' => $payment->status,
                    'transaction_id' => $payment->transaction_id,
                    'created_at' => $payment->created_at,
                ];
            }),
            'created_by' => $invoice->createdBy ? [
                'id' => $invoice->createdBy->id,
                'name' => $invoice->createdBy->name,
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'data' => $invoiceData,
            'message' => 'Invoice retrieved successfully'
        ]);
    }

    /**
     * Update the specified invoice.
     */
    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,paid,overdue,cancelled',
            'payment_method' => 'sometimes|' . PaymentMethod::validationRule(),
            'notes' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $invoice->update($request->only(['status', 'payment_method', 'notes']));

        return response()->json([
            'success' => true,
            'data' => $invoice->load(['patient', 'branch', 'payments']),
            'message' => 'Invoice updated successfully'
        ]);
    }

    /**
     * Remove the specified invoice from storage.
     */
    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);

        // Check if invoice has payments
        if ($invoice->payments()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete invoice with existing payments'
            ], 400);
        }

        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully'
        ]);
    }

    /**
     * Add payment to invoice.
     */
    public function addPayment(Request $request, $invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->total_amount,
            'payment_method' => 'required|' . PaymentMethod::validationRule(),
            'momo_phone' => 'nullable|string',
            'momo_network' => 'nullable|in:MTN,Vodafone,AirtelTigo',
            'momo_reference' => 'nullable|string',
            'amount_tendered' => 'nullable|numeric|min:0',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $activeVisit = \App\Models\Visit::where('patient_id', $invoice->patient_id)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            app(\App\Services\PaymentPolicyService::class)->validatePaymentAmount(
                $activeVisit,
                $invoice,
                (float) $request->amount
            );

            // Use PaymentService for consistent payment recording
            $result = $this->paymentService->recordPayment(
                $invoiceId,
                $request->amount,
                $request->payment_method,
                PaymentMetadata::fromRequest($request, [
                    'payment_date' => $request->payment_date,
                    'reference_number' => $request->reference_number,
                    'notes' => $request->notes,
                    'processed_by' => auth()->id(),
                    'source_platform' => 'mobile',
                    'device_info' => $request->header('User-Agent'),
                    'ip_address' => $request->ip(),
                    'visit' => $activeVisit,
                ])
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            // PaymentObserver has automatically:
            // - Updated invoice paid_amount, balance_amount, payment_status
            // - Created revenue transaction
            // - Updated debtor records

            return response()->json([
                'success' => true,
                'data' => $result['payment'],
                'invoice' => [
                    'paid_amount' => $result['invoice']->paid_amount,
                    'balance_amount' => $result['invoice']->balance_amount,
                    'payment_status' => $result['invoice']->payment_status
                ],
                'message' => 'Payment added successfully'
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'payment_policy_violation' => true,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get billing statistics.
     */
    public function getStatistics(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(30);
        $dateTo = $request->date_to ?? now();

        $stats = [
            'total_invoices' => Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])->count(),
            'total_revenue' => Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])->sum('total_amount'),
            'paid_invoices' => Invoice::where('status', 'paid')->whereBetween('invoice_date', [$dateFrom, $dateTo])->count(),
            'pending_invoices' => Invoice::where('status', 'pending')->count(),
            'overdue_invoices' => Invoice::where('status', 'overdue')->count(),
            'total_payments' => Payment::whereBetween('payment_date', [$dateFrom, $dateTo])->sum('amount'),
            'payment_methods' => $this->calculatePaymentMethodStats($dateFrom, $dateTo),
            'monthly_revenue' => $this->calculateMonthlyRevenue($dateFrom, $dateTo),
            'top_patients' => $this->getTopPatients($dateFrom, $dateTo)
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Billing statistics retrieved successfully'
        ]);
    }

    /**
     * Get payment method statistics.
     */
    private function calculatePaymentMethodStats($dateFrom, $dateTo)
    {
        return Payment::whereBetween('payment_date', [$dateFrom, $dateTo])
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('payment_method')
            ->orderBy('total_amount', 'desc')
            ->get();
    }

    /**
     * Get monthly revenue.
     */
    private function calculateMonthlyRevenue($dateFrom, $dateTo)
    {
        return Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->selectRaw('DATE_FORMAT(invoice_date, "%Y-%m") as month, SUM(total_amount) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    /**
     * Get top patients by billing.
     */
    private function getTopPatients($dateFrom, $dateTo)
    {
        return Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->join('patients', 'invoices.patient_id', '=', 'patients.id')
            ->selectRaw('patients.first_name, patients.last_name, patients.patient_number, COUNT(*) as invoice_count, SUM(invoices.total_amount) as total_amount')
            ->groupBy('patients.id', 'patients.first_name', 'patients.last_name', 'patients.patient_number')
            ->orderBy('total_amount', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get overdue invoices.
     */
    public function getOverdueInvoices(Request $request)
    {
        $query = Invoice::with(['patient', 'branch'])
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', now()->toDateString())
            ->orderBy('due_date', 'asc');

        $invoices = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $invoices,
            'message' => 'Overdue invoices retrieved successfully'
        ]);
    }

    /**
     * Update invoice status.
     */
    public function updateStatus(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,sent,paid,overdue,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $invoice->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'data' => $invoice->load(['patient', 'branch', 'payments']),
            'message' => 'Invoice status updated successfully'
        ]);
    }

    /**
     * Get payment methods statistics.
     */
    public function getPaymentMethodStats(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(30);
        $dateTo = $request->date_to ?? now();

        $stats = $this->calculatePaymentMethodStats($dateFrom, $dateTo);

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Payment method statistics retrieved successfully'
        ]);
    }

    /**
     * Get monthly revenue.
     */
    public function getMonthlyRevenue(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(365);
        $dateTo = $request->date_to ?? now();

        $revenue = $this->calculateMonthlyRevenue($dateFrom, $dateTo);

        return response()->json([
            'success' => true,
            'data' => $revenue,
            'message' => 'Monthly revenue retrieved successfully'
        ]);
    }

    /**
     * Generate invoice from consultation.
     */
    public function generateFromConsultation(Request $request, $consultationId)
    {
        $consultation = Consultation::with(['patient', 'doctor', 'interventions'])->findOrFail($consultationId);

        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'due_date' => 'nullable|date|after:today'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $items = [];
        $totalAmount = 0;
        $branchId = (int) $request->branch_id;

        foreach ($this->modulePricingService->resolveConsultationBillableLines($consultation) as $line) {
            $item = $this->modulePricingService->chargeLineToInvoiceItem($line, [
                'service_id' => $line['service_id'] ?? 'consultation',
                'consultation_id' => $consultation->id,
            ]);
            $items[] = $item;
            $totalAmount += $item['total'];
        }

        foreach ($consultation->interventions as $intervention) {
            $lines = $this->modulePricingService->resolveInterventionBillableLines(
                $intervention,
                $consultation,
                $branchId
            );

            foreach ($lines as $line) {
                $item = $this->modulePricingService->chargeLineToInvoiceItem($line, [
                    'intervention_id' => $intervention->id,
                    'service_id' => $line['service_id'] ?? null,
                ]);
                $items[] = $item;
                $totalAmount += $item['total'];
            }
        }

        $consultationFee = collect($items)
            ->where('service_type', 'consultation')
            ->sum('total');

        $invoice = Invoice::create([
            'patient_id' => $consultation->patient_id,
            'branch_id' => $request->branch_id,
            'invoice_date' => now()->toDateString(),
            'due_date' => $request->due_date ?? now()->addDays(30)->toDateString(),
            'items' => $items,
            'subtotal' => $totalAmount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $totalAmount,
            'status' => 'pending', // Pending payment (not draft)
            'notes' => 'Consultation #' . $consultation->id . ' - Dr. ' . $consultation->doctor->name,
            'created_by' => auth()->id()
        ]);

        \Log::info('Consultation invoice created with dynamic pricing', [
            'consultation_id' => $consultation->id,
            'invoice_id' => $invoice->id,
            'total_amount' => $totalAmount,
            'items_count' => count($items)
        ]);

        return response()->json([
            'success' => true,
            'data' => $invoice->load(['patient', 'branch', 'payments']),
            'message' => 'Invoice generated from consultation successfully',
            'pricing_info' => [
                'consultation_fee' => $consultationFee ?? 0,
                'interventions_count' => count($consultation->interventions),
                'total_amount' => $totalAmount
            ]
        ], 201);
    }

    /**
     * Generate invoice PDF with proper document headers and branding.
     */
    public function generatePDF($id)
    {
        try {
            $user = auth()->user();
            $invoice = Invoice::findOrFail($id);

            // If user is a patient, verify ownership
            if ($user->hasRole('patient')) {
                $patient = $user->patient;
                
                if (!$patient) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Patient profile not found for this user'
                    ], 404);
                }
                
                // Verify this invoice belongs to the patient
                if ($invoice->patient_id !== $patient->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have access to this invoice'
                    ], 403);
                }
            }

            $pdfService = new BillingPdfService();
            $pdf = $pdfService->generateInvoicePdf($id);

            $filename = $pdfService->generateFilename($invoice, 'invoice');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate receipt PDF.
     */
    public function generateReceiptPDF($id)
    {
        try {
            $user = auth()->user();
            $invoice = Invoice::findOrFail($id);

            // If user is a patient, verify ownership
            if ($user->hasRole('patient')) {
                $patient = $user->patient;
                
                if (!$patient) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Patient profile not found for this user'
                    ], 404);
                }
                
                // Verify this invoice belongs to the patient
                if ($invoice->patient_id !== $patient->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have access to this invoice'
                    ], 403);
                }
            }

            $pdfService = new BillingPdfService();
            $pdf = $pdfService->generateReceiptPdf($id);

            $filename = $pdfService->generateFilename($invoice, 'receipt');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating receipt PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate billing statement PDF for patient.
     */
    public function generateBillingStatementPDF($patientId)
    {
        try {
            $pdfService = new BillingPdfService();
            $pdf = $pdfService->generateBillingStatementPdf($patientId);

            $patient = Patient::findOrFail($patientId);
            $filename = "billing_statement_{$patient->first_name}_{$patient->last_name}_" . now()->format('Y-m-d') . ".pdf";

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating billing statement PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate standalone bill for patient with specific services.
     */
    public function generateStandaloneBill(Request $request)
    {
        // Log the incoming request for debugging
        \Log::info('Standalone bill request data:', $request->all());
        \Log::info('Request headers:', $request->headers->all());
        \Log::info('Request method: ' . $request->method());

        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
            'services' => 'required|array|min:1',
            'services.*.description' => 'required|string',
            'services.*.quantity' => 'required|numeric|min:0.01',
            'services.*.unit_price' => 'required|numeric|min:0',
            'services.*.service_type' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            \Log::error('Standalone bill validation failed:', $validator->errors()->toArray());
            \Log::error('Request data that failed validation:', $request->all());
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
                'debug_data' => $request->all()
            ], 422);
        }

        try {
            $patient = Patient::findOrFail($request->patient_id);
            $branch = Branch::findOrFail($request->branch_id);

            // Calculate totals
            $subtotal = 0;
            $items = [];

            foreach ($request->services as $service) {
                $total = $service['quantity'] * $service['unit_price'];
                $subtotal += $total;

                $items[] = [
                    'id' => 'item_' . uniqid(),
                    'description' => $service['description'],
                    'quantity' => $service['quantity'],
                    'unit_price' => $service['unit_price'],
                    'total' => $total,
                    'service_type' => $service['service_type'] ?? 'general'
                ];
            }

            $taxAmount = 0; // Can be calculated based on business rules
            $discountAmount = 0; // Can be applied based on business rules
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            // Create temporary invoice record for PDF generation
            $tempInvoice = (object) [
                'id' => 'temp_' . uniqid(),
                'patient' => $patient,
                'branch' => $branch,
                'invoice_date' => now()->toDateString(),
                'due_date' => $request->due_date ?? now()->addDays(30)->toDateString(),
                'items' => $items,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'payment_method' => 'cash',
                'notes' => $request->notes,
                'payments' => collect([]),
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Generate PDF
            $pdfService = new BillingPdfService();
            $pdf = $pdfService->generateStandaloneBillPdf($tempInvoice);

            $billNumber = $tempInvoice->id;
            $filename = "standalone_bill_{$billNumber}_{$patient->first_name}_{$patient->last_name}_" . now()->format('Y-m-d') . ".pdf";

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating standalone bill: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all payments with pagination and filters.
     */
    public function getPayments(Request $request)
    {
        $query = Payment::with(['invoice.patient', 'invoice.branch', 'processor'])
            ->orderBy('id', 'desc');

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        // Search by reference number or patient name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_reference', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('invoice.patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $payments = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total()
            ],
            'message' => 'Payments retrieved successfully'
        ]);
    }

    /**
     * Get available services for standalone bill generation.
     */
    public function getAvailableServices(Request $request)
    {
        try {
            $branchId = $request->integer('branch_id') ?: null;

            $query = \App\Models\ServicePricing::where('is_active', true);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            if ($request->filled('service_type')) {
                $query->where('service_type', $request->service_type);
            }

            $services = $query->orderBy('service_type')->orderBy('service_name')->get()
                ->map(function ($service) {
                    $category = ucfirst(str_replace('_', ' ', $service->service_type));

                    return [
                        'id' => $service->service_id,
                        'name' => $service->service_name,
                        'description' => $service->description,
                        'service_type' => $service->service_type,
                        'pricing_type' => $service->pricing_type ?? 'standalone',
                        'is_additive' => (bool) ($service->is_additive ?? false),
                        'module_codes' => $service->module_codes ?? [],
                        'base_price' => (float) $service->base_price,
                        'branch_id' => $service->branch_id,
                        'category' => $category,
                    ];
                })
                ->values()
                ->all();

            if ($request->filled('category')) {
                $services = array_values(array_filter(
                    $services,
                    fn ($service) => $service['category'] === $request->category
                ));
            }

            return response()->json([
                'success' => true,
                'data' => $services,
                'message' => 'Available services retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving services: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initialize Paystack payment
     */
    public function initializePaystackPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0.01',
                'email' => 'required|email',
                'reference' => 'required|string',
                'metadata' => 'nullable|array',
                'payment_type' => 'nullable|string|in:invoice,order,appointment,service',
                'reference_id' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Prepare metadata with payment context
            $metadata = $request->metadata ?? [];

            // Add payment type and reference if provided
            if ($request->has('payment_type')) {
                $metadata['payment_type'] = $request->payment_type;
            }
            if ($request->has('reference_id')) {
                $metadata['reference_id'] = $request->reference_id;
            }

            // Add patient and branch info if authenticated
            if (auth()->check()) {
                $metadata['user_id'] = auth()->id();
                if (auth()->user()->branch_id) {
                    $metadata['branch_id'] = auth()->user()->branch_id;
                }
            }

            // Get dynamic callback URL
            $callbackUrl = PaymentSetting::getPaystackCallbackUrl();

            // Initialize payment with Paystack
            $paystackService = new PaystackService();
            $result = $paystackService->initializeTransaction(
                $request->email,
                $request->amount,
                $request->reference,
                $metadata,
                $callbackUrl
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment initialization failed'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'callback_url' => $callbackUrl,
                'message' => 'Payment initialized successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Paystack initialization error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Payment initialization failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process Paystack payment (Mobile App)
     */
    public function processPaystackPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'invoice_id' => 'required|exists:invoices,id',
                'amount' => 'required|numeric|min:0.01',
                'reference' => 'required|string|unique:payments,reference_number',
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $invoice = Invoice::findOrFail($request->invoice_id);

            // If user is a patient, verify ownership
            if ($user->hasRole('patient')) {
                $patient = $user->patient;
                
                if (!$patient) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Patient profile not found for this user'
                    ], 404);
                }
                
                // Verify this invoice belongs to the patient
                if ($invoice->patient_id !== $patient->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have access to this invoice'
                    ], 403);
                }
            }

            // Verify payment with Paystack
            $paystackService = new PaystackService();
            $verification = $paystackService->verifyTransaction($request->reference);

            if (!$verification['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed'
                ], 400);
            }

            $transactionData = $verification['data'];

            // Check if payment was successful
            if ($transactionData['status'] !== 'success') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment was not successful'
                ], 400);
            }

            $verifiedAmount = round((float) ($transactionData['amount'] ?? 0) / 100, 2);
            $expectedBalance = (float) ($invoice->balance_amount ?? max(0, $invoice->total_amount - $invoice->paid_amount));

            if (!$paystackService->amountMatchesExpected($expectedBalance, $transactionData)
                && !$paystackService->amountMatchesExpected((float) $request->amount, $transactionData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paystack amount does not match invoice balance. Full payment required.',
                ], 400);
            }

            $activeVisit = \App\Models\Visit::where('patient_id', $invoice->patient_id)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            app(\App\Services\PaymentPolicyService::class)->validatePaymentAmount(
                $activeVisit,
                $invoice,
                $verifiedAmount
            );

            // Use PaymentService for consistent payment recording
            $result = $this->paymentService->recordPayment(
                $invoice->id,
                $verifiedAmount,
                PaymentMethod::Paystack->value,
                [
                    'reference_number' => $request->reference,
                    'transaction_id' => $transactionData['id'] ?? null,
                    'notes' => 'Payment via Paystack',
                    'processed_by' => auth()->id(),
                    'source_platform' => 'mobile',
                    'device_info' => $request->header('User-Agent'),
                    'ip_address' => $request->ip(),
                    'metadata' => ['paystack_transaction' => $transactionData],
                ]
            );

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            $payment = $result['payment'];
            $invoice = $result['invoice']; // Get updated invoice

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $payment->load('invoice'),
                'message' => 'Payment processed successfully via Paystack'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Paystack payment error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify Paystack payment
     */
    public function verifyPaystackPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reference' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify payment with Paystack
            $paystackService = new PaystackService();
            $verification = $paystackService->verifyTransaction($request->reference);

            if (!$verification['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed'
                ], 400);
            }

            $transactionData = $verification['data'];
            $payment = \App\Models\Payment::where('reference_number', $request->reference)->first();

            if ($payment && !$paystackService->amountMatchesExpected((float) $payment->amount, $transactionData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paystack amount does not match expected payment amount',
                ], 400);
            }

            if ($payment?->invoice_id) {
                $invoice = Invoice::find($payment->invoice_id);
                if ($invoice && !$paystackService->amountMatchesExpected((float) $invoice->balance_amount, $transactionData)
                    && !$paystackService->amountMatchesExpected((float) $payment->amount, $transactionData)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Paystack amount does not match invoice balance',
                    ], 400);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $transactionData,
                'message' => 'Payment verified successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Paystack verification error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process mobile money payment (Mobile App)
     */
    public function processMobileMoneyPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'invoice_id' => 'required|exists:invoices,id',
                'amount' => 'required|numeric|min:0.01',
                'phone_number' => 'required|string',
                'network' => 'required|in:MTN,Vodafone,AirtelTigo',
                'reference_number' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $invoice = Invoice::findOrFail($request->invoice_id);

            // If user is a patient, verify ownership
            if ($user->hasRole('patient')) {
                $patient = $user->patient;
                
                if (!$patient) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Patient profile not found for this user'
                    ], 404);
                }
                
                // Verify this invoice belongs to the patient
                if ($invoice->patient_id !== $patient->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have access to this invoice'
                    ], 403);
                }
            }

            // Use PaymentService for consistent payment recording
            $result = $this->paymentService->recordPayment(
                $invoice->id,
                $request->amount,
                PaymentMethod::MobileMoneyOffline->value,
                [
                    'reference_number' => $request->reference_number,
                    'momo_phone' => $request->phone_number,
                    'momo_network' => $request->network,
                    'notes' => "Offline mobile money via {$request->network} ({$request->phone_number})",
                    'processed_by' => auth()->id(),
                    'source_platform' => 'mobile',
                    'device_info' => $request->header('User-Agent'),
                    'ip_address' => $request->ip(),
                ]
            );

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            $payment = $result['payment'];
            $invoice = $result['invoice']; // Get updated invoice

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $payment->load('invoice'),
                'message' => 'Mobile money payment processed successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Mobile money payment error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process card payment (Mobile App)
     */
    public function processCardPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'invoice_id' => 'required|exists:invoices,id',
                'amount' => 'required|numeric|min:0.01',
                'card_details' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $invoice = Invoice::findOrFail($request->invoice_id);

            // If user is a patient, verify ownership
            if ($user->hasRole('patient')) {
                $patient = $user->patient;
                
                if (!$patient) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Patient profile not found for this user'
                    ], 404);
                }
                
                // Verify this invoice belongs to the patient
                if ($invoice->patient_id !== $patient->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have access to this invoice'
                    ], 403);
                }
            }

            // Use PaymentService for consistent payment recording
            $result = $this->paymentService->recordPayment(
                $invoice->id,
                $request->amount,
                'card',
                [
                    'reference_number' => 'CARD_' . uniqid(),
                    'notes' => 'Card payment',
                    'processed_by' => auth()->id(),
                    'source_platform' => 'mobile', // TAG: Mobile card payment
                    'device_info' => $request->header('User-Agent'),
                    'ip_address' => $request->ip()
                ]
            );

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            $payment = $result['payment'];
            $invoice = $result['invoice']; // Get updated invoice

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $payment->load('invoice'),
                'message' => 'Card payment processed successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Card payment error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create payment (Mobile App - Cash/General)
     */
    public function createPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'invoice_id' => 'required|exists:invoices,id',
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|' . PaymentMethod::validationRule(),
            'momo_phone' => 'nullable|string',
            'momo_network' => 'nullable|in:MTN,Vodafone,AirtelTigo',
            'momo_reference' => 'nullable|string',
            'amount_tendered' => 'nullable|numeric|min:0',
                'transaction_id' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $invoice = Invoice::findOrFail($request->invoice_id);

            // If user is a patient, verify ownership
            if ($user->hasRole('patient')) {
                $patient = $user->patient;
                
                if (!$patient) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Patient profile not found for this user'
                    ], 404);
                }
                
                // Verify this invoice belongs to the patient
                if ($invoice->patient_id !== $patient->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have access to this invoice'
                    ], 403);
                }
            }

            // Use PaymentService for consistent payment recording
            $result = $this->paymentService->recordPayment(
                $invoice->id,
                $request->amount,
                $request->payment_method,
                PaymentMetadata::fromRequest($request, [
                    'reference_number' => $request->transaction_id ?? strtoupper($request->payment_method) . '_' . uniqid(),
                    'transaction_id' => $request->transaction_id,
                    'notes' => $request->notes,
                    'processed_by' => auth()->id(),
                    'source_platform' => 'mobile',
                    'device_info' => $request->header('User-Agent'),
                    'ip_address' => $request->ip(),
                ])
            );

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            $payment = $result['payment'];
            $invoice = $result['invoice']; // Get updated invoice

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $payment->load('invoice'),
                'message' => 'Payment processed successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Payment creation error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get billing summary for mobile app (authenticated patient)
     */
    public function getBillingSummary(Request $request)
    {
        try {
            $user = auth()->user();

            $patient = $user->patient ?? \App\Models\Patient::where('user_id', $user->id)->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient profile not found'
                ], 404);
            }

            // Get all invoices for the patient
            $invoices = Invoice::where('patient_id', $patient->id)
                ->with('payments')
                ->get();

            // Calculate total outstanding (unpaid balance)
            $totalOutstanding = 0;
            $totalPaid = 0;

            foreach ($invoices as $invoice) {
                $paidAmount = $invoice->payments()->where('status', 'completed')->sum('amount');
                $balance = $invoice->total_amount - $paidAmount;

                if ($invoice->status !== 'paid' && $balance > 0) {
                    $totalOutstanding += $balance;
                }

                $totalPaid += $paidAmount;
            }

            $pendingInvoices = Invoice::where('patient_id', $patient->id)
                ->where('status', 'pending')
                ->count();

            $overdueInvoices = Invoice::where('patient_id', $patient->id)
                ->where('status', 'overdue')
                ->count();

            $totalInvoices = $invoices->count();
            $paidInvoices = $invoices->where('status', 'paid')->count();

            $pendingCharges = $this->pendingChargesService->getPatientPendingCharges($patient->id);
            $pendingChargesTotal = round(collect($pendingCharges)->sum('amount'), 2);
            $formattedCharges = $this->modulePricingService->formatChargeLinesForApi($pendingCharges);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_outstanding' => round($totalOutstanding + $pendingChargesTotal, 2),
                    'total_outstanding_invoices' => round($totalOutstanding, 2),
                    'total_pending_charges' => $pendingChargesTotal,
                    'total_paid' => round($totalPaid, 2),
                    'pending_invoices' => $pendingInvoices,
                    'overdue_invoices' => $overdueInvoices,
                    'total_invoices' => $totalInvoices,
                    'paid_invoices' => $paidInvoices,
                    'pending_charges' => $formattedCharges,
                    'currency' => 'GHS',
                ],
                'message' => 'Billing summary retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Billing summary error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving billing summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending charges for authenticated patient (additive line breakdown).
     */
    public function getPatientPendingCharges(Request $request)
    {
        try {
            $user = auth()->user();
            $patient = $user->patient ?? \App\Models\Patient::where('user_id', $user->id)->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient profile not found',
                ], 404);
            }

            $branchId = $request->integer('branch_id') ?: null;
            $charges = $this->pendingChargesService->getPatientPendingCharges($patient->id, $branchId);
            $formatted = $this->modulePricingService->formatChargeLinesForApi($charges);

            return response()->json([
                'success' => true,
                'data' => $formatted,
                'meta' => [
                    'total_amount' => round(collect($charges)->sum('amount'), 2),
                    'line_count' => count($formatted),
                ],
                'message' => 'Pending charges retrieved successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Get patient pending charges error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving pending charges: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get patient invoices for mobile app
     */
    public function getPatientInvoices(Request $request)
    {
        try {
            $user = auth()->user();

            // Get patient from user
            $patient = $user->patient;

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient profile not found'
                ], 404);
            }

            $query = Invoice::where('patient_id', $patient->id)
                ->with(['patient', 'branch', 'payments', 'createdBy'])
                ->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('invoice_date', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('invoice_date', '<=', $request->date_to);
            }

            $invoices = $query->paginate($request->get('limit', 20));

            // Transform invoices to include calculated fields
            $transformedInvoices = $invoices->getCollection()->map(function ($invoice) {
                $totalPaid = $invoice->payments->sum('amount');
                $balance = $invoice->total_amount - $totalPaid;
                
                return [
                    'id' => $invoice->id,
                    'patient_id' => $invoice->patient_id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date?->format('Y-m-d') ?? $invoice->invoice_date,
                    'due_date' => $invoice->due_date?->format('Y-m-d') ?? $invoice->due_date,
                    'subtotal' => (float) ($invoice->subtotal ?? $invoice->total_amount),
                    'tax_amount' => (float) ($invoice->tax_amount ?? 0),
                    'discount_amount' => (float) ($invoice->discount_amount ?? 0),
                    'total_amount' => (float) $invoice->total_amount,
                    'paid_amount' => $totalPaid,
                    'balance' => $balance,
                    'status' => $invoice->status,
                    'payment_status' => $invoice->payment_status ?? $invoice->status,
                    'payment_method' => $invoice->payment_method,
                    'items' => $invoice->items ?? [],
                    'notes' => $invoice->notes,
                    'created_at' => $invoice->created_at,
                    'updated_at' => $invoice->updated_at,
                    'patient' => $invoice->patient ? [
                        'id' => $invoice->patient->id,
                        'first_name' => $invoice->patient->first_name,
                        'last_name' => $invoice->patient->last_name,
                        'patient_number' => $invoice->patient->patient_number,
                    ] : null,
                    'branch' => $invoice->branch ? [
                        'id' => $invoice->branch->id,
                        'name' => $invoice->branch->name,
                    ] : null,
                    'payments' => $invoice->payments->map(function ($payment) use ($invoice) {
                        return [
                            'id' => $payment->id,
                            'invoice_id' => $invoice->id,
                            'amount' => $payment->amount,
                            'payment_method' => $payment->payment_method,
                            'status' => $payment->status,
                            'created_at' => $payment->created_at,
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedInvoices,
                'meta' => [
                    'current_page' => $invoices->currentPage(),
                    'last_page' => $invoices->lastPage(),
                    'per_page' => $invoices->perPage(),
                    'total' => $invoices->total(),
                ],
                'message' => 'Invoices retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Get patient invoices error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patient payments for mobile app
     */
    public function getPatientPayments(Request $request)
    {
        try {
            $user = auth()->user();

            // Get patient from user
            $patient = $user->patient;

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient profile not found'
                ], 404);
            }

            $query = Payment::whereHas('invoice', function ($query) use ($patient) {
                $query->where('patient_id', $patient->id);
            })->with(['invoice'])
                ->orderBy('id', 'desc');

            $payments = $query->paginate($request->limit ?? 20);

            return response()->json([
                'success' => true,
                'data' => $payments->items(),
                'meta' => [
                    'current_page' => $payments->currentPage(),
                    'total' => $payments->total(),
                    'per_page' => $payments->perPage(),
                ],
                'message' => 'Payments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Get patient payments error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get insurance claims for mobile app
     */
    public function getInsuranceClaims(Request $request)
    {
        try {
            $user = auth()->user();

            // Get patient from user
            $patient = $user->patient;

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient profile not found'
                ], 404);
            }

            $query = InsuranceClaim::where('patient_id', $patient->id)
                ->with(['invoice', 'policy.insuranceProvider'])
                ->orderBy('id', 'desc');

            $claims = $query->paginate($request->limit ?? 20);

            return response()->json([
                'success' => true,
                'data' => $claims->items(),
                'meta' => [
                    'current_page' => $claims->currentPage(),
                    'total' => $claims->total(),
                    'per_page' => $claims->perPage(),
                ],
                'message' => 'Insurance claims retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Get insurance claims error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving insurance claims: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit insurance claim for mobile app
     */
    public function submitInsuranceClaim(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'invoice_id' => 'required|exists:invoices,id',
                'insurance_provider_id' => 'required|exists:insurance_providers,id',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $patient = $user->patient;

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient profile not found'
                ], 404);
            }

            $invoice = Invoice::findOrFail($request->invoice_id);

            if ($invoice->patient_id !== $patient->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice does not belong to this patient'
                ], 403);
            }

            // Get patient's active policy with this provider
            $policy = InsurancePolicy::where('patient_id', $patient->id)
                ->where('insurance_provider_id', $request->insurance_provider_id)
                ->where('is_active', true)
                ->where('start_date', '<=', now()->toDateString())
                ->where('end_date', '>=', now()->toDateString())
                ->first();

            if (!$policy) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active insurance policy found for this provider'
                ], 400);
            }

            DB::beginTransaction();

            // Create insurance claim
            $claim = InsuranceClaim::create([
                'patient_id' => $patient->id,
                'insurance_provider_id' => $policy->insurance_provider_id,
                'policy_id' => $policy->id,
                'invoice_id' => $invoice->id,
                'visit_id' => $invoice->visit_id ?? null,
                'total_amount' => $invoice->total_amount,
                'covered_amount' => 0, // To be calculated by insurance
                'co_pay_amount' => $invoice->total_amount, // Initially full amount
                'status' => 'pending',
                'submitted_date' => now(),
                'claim_number' => 'CLM_' . uniqid(),
                'notes' => $request->notes,
                'created_by' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $claim->load(['policy.insuranceProvider', 'invoice']),
                'message' => 'Insurance claim submitted successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Submit insurance claim error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error submitting insurance claim: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refund a completed payment.
     */
    public function refundPayment(Request $request, Payment $payment): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->paymentService->refundPayment(
            $payment->id,
            $validated['amount'] ?? null,
            $validated['reason'] ?? null
        );

        $status = $result['success'] ? 200 : 422;

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['payment'],
            'refund_amount' => $result['refund_amount'] ?? 0,
        ], $status);
    }
}
