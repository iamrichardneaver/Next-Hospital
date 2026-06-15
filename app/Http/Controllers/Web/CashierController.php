<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Enums\PaymentMethod;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Visit;
use App\Models\Consultation;
use App\Models\LabRequest;
use App\Models\Prescription;
use App\Models\RadiologyRequest;
use App\Models\BrandingSetting;
use App\Models\SystemSetting;
use App\Support\PaymentMetadata;
use App\Services\PendingChargesService;
use App\Services\BillingPdfService;
use App\Services\DebtorService;
use App\Services\PaymentService;
use App\Services\PaymentPolicyService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CashierController extends Controller
{
    use ExportsListData, ResolvesUserBranch;

    protected $pendingChargesService;
    protected $billingPdfService;
    protected $debtorService;
    protected $paymentService;
    protected $pricingService;
    protected PaymentPolicyService $paymentPolicyService;

    public function __construct(
        PendingChargesService $pendingChargesService, 
        BillingPdfService $billingPdfService, 
        DebtorService $debtorService,
        PaymentService $paymentService,
        PricingService $pricingService,
        PaymentPolicyService $paymentPolicyService
    ) {
        $this->pendingChargesService = $pendingChargesService;
        $this->billingPdfService = $billingPdfService;
        $this->debtorService = $debtorService;
        $this->paymentService = $paymentService;
        $this->pricingService = $pricingService;
        $this->paymentPolicyService = $paymentPolicyService;
    }

    /**
     * Scope payments to the user's branch (payment.branch_id or invoice.branch_id).
     */
    protected function scopePaymentsToBranch($query, int $branchId)
    {
        return $query->where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
                ->orWhereHas('invoice', fn ($iq) => $iq->where('branch_id', $branchId));
        });
    }

    /**
     * Display the cashier dashboard
     */
    public function index()
    {
        $branchId = $this->resolveUserBranchId('process_payments');

        // Get today's statistics
        $today = now()->toDateString();
        $thisMonth = now()->month;
        $thisYear = now()->year;
        
        $statistics = [
            'total_patients_served' => Visit::where('branch_id', $branchId)
                ->whereDate('created_at', $today)
                ->count(),
            'pending_payments' => $this->pendingChargesService->getPendingChargesCount($branchId),
            'total_collected' => $this->scopePaymentsToBranch(Payment::query(), $branchId)
                ->whereDate('created_at', $today)
                ->sum('amount'),
            'monthly_revenue' => $this->scopePaymentsToBranch(Payment::query(), $branchId)
                ->whereMonth('created_at', $thisMonth)
                ->whereYear('created_at', $thisYear)
                ->where('status', 'completed')
                ->sum('amount'),
            'outstanding_amount' => $this->pendingChargesService->getOutstandingAmount($branchId)
        ];

        // Get recent payments
        $recentPayments = $this->scopePaymentsToBranch(
            Payment::with(['invoice.patient', 'patient']),
            $branchId
        )
            ->latest()
            ->limit(10)
            ->get();

        $branches = Branch::orderBy('name')->get(['id', 'name']);

        return view('cashier.index', compact('statistics', 'recentPayments', 'branchId', 'branches'));
    }

    /**
     * Search for patient and their pending charges
     */
    public function searchPatient(Request $request)
    {
        try {
            $request->validate([
                'search_term' => 'required|string|min:2'
            ]);

            $searchTerm = $request->search_term;
            $branchId = $this->resolveUserBranchId('process_payments');

            \Log::info('Cashier patient search', [
                'search_term' => $searchTerm,
                'branch_id' => $branchId,
                'user_id' => auth()->id()
            ]);

            // Search patients by various criteria
            // Note: Show all patients, not just those with visits in this branch
            // Cashiers need to see all patients to process payments
            $patients = Patient::where(function($query) use ($searchTerm) {
                $query->where('patient_number', 'like', "%{$searchTerm}%")
                      ->orWhere('first_name', 'like', "%{$searchTerm}%")
                      ->orWhere('last_name', 'like', "%{$searchTerm}%")
                      ->orWhere('phone', 'like', "%{$searchTerm}%")
                      ->orWhere('nhis_number', 'like', "%{$searchTerm}%");
            })
            ->with(['visits' => function($query) use ($branchId) {
                $query->where('branch_id', $branchId)->latest()->limit(5);
            }])
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();

            \Log::info('Patient search results', [
                'count' => $patients->count()
            ]);

            $patientsData = $patients->map(function($patient) use ($branchId) {
                try {
                    // Get debt information for this patient
                    $debtor = \App\Models\Debtor::where('patient_id', $patient->id)
                        ->where('branch_id', $branchId)
                        ->first();
                    
                    return [
                        'id' => $patient->id,
                        'patient_number' => $patient->patient_number ?? 'N/A',
                        'name' => $patient->full_name ?? 'Unknown',
                        'phone' => $patient->phone ?? null,
                        'last_visit' => $patient->visits->first()?->created_at?->format('M d, Y H:i'),
                        'debt_info' => $debtor ? [
                            'total_outstanding' => $debtor->total_outstanding,
                            'debt_status' => $debtor->debt_status,
                            'days_overdue' => $debtor->days_overdue,
                            'outstanding_invoices_count' => $debtor->outstanding_invoices_count,
                            'has_debt' => $debtor->total_outstanding > 0
                        ] : [
                            'total_outstanding' => 0,
                            'debt_status' => 'current',
                            'days_overdue' => 0,
                            'outstanding_invoices_count' => 0,
                            'has_debt' => false
                        ]
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error processing patient in search', [
                        'patient_id' => $patient->id ?? null,
                        'error' => $e->getMessage()
                    ]);
                    return null;
                }
            })->filter()->values(); // Remove null entries and re-index

            return response()->json([
                'success' => true,
                'patients' => $patientsData,
                'count' => $patientsData->count()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Validation error in patient search', [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid search term. Please enter at least 2 characters.',
                'patients' => []
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error in patient search', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching for patients.',
                'patients' => []
            ], 500);
        }
    }

    /**
     * Get patient's pending charges
     */
    public function getPatientCharges(Request $request, $patientId)
    {
        try {
            \Log::info('Getting patient charges', [
                'patient_id' => $patientId,
                'user_id' => auth()->id(),
                'authenticated' => auth()->check(),
                'user_email' => auth()->user() ? auth()->user()->email : 'null'
            ]);

            if (!auth()->check()) {
                \Log::warning('User not authenticated for patient charges request');
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $branchId = $this->resolveUserBranchId('process_payments');

            // Find patient - return friendly error if not found
            $patient = Patient::find($patientId);
            
            if (!$patient) {
                \Log::warning('Patient not found', ['patient_id' => $patientId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found. Please try searching again.'
                ], 404);
            }
            
            // Get all pending charges for this patient (not restricted to branch)
            // Cashiers can collect payment for services rendered at any branch
            $pendingCharges = $this->pendingChargesService->getPatientPendingCharges($patientId, null);
            
            // Ensure pending charges is always an array
            if (!is_array($pendingCharges)) {
                $pendingCharges = [];
            }
            
            // Also check for pending charges in current branch specifically
            $branchPendingCharges = $this->pendingChargesService->getPatientPendingCharges($patientId, $branchId);
            
            // Get patient's recent visits
            $recentVisits = Visit::where('patient_id', $patientId)
                ->where('branch_id', $branchId)
                ->with(['consultation'])
                ->latest()
                ->limit(5)
                ->get();

            \Log::info('Patient charges retrieved successfully', [
                'patient_id' => $patientId,
                'pending_charges_count' => count($pendingCharges),
                'recent_visits_count' => $recentVisits->count(),
                'has_charges' => count($pendingCharges) > 0
            ]);

            $paymentPolicy = $this->paymentPolicyService->resolvePatientBillingContext($patientId);
            $paymentSummary = $this->paymentPolicyService->getPaymentStatusSummary($patientId, $branchId);
            $unpaidInvoices = $this->paymentPolicyService->getUnpaidInvoicesForPatient($patientId, null);

            return response()->json([
                'success' => true,
                'patient' => [
                    'id' => $patient->id,
                    'patient_number' => $patient->patient_number,
                    'name' => $patient->full_name,
                    'phone' => $patient->phone,
                    'email' => $patient->email,
                    'date_of_birth' => $patient->date_of_birth?->format('M d, Y'),
                    'gender' => $patient->gender,
                    'address' => $patient->address
                ],
                'payment_policy' => $paymentPolicy,
                'payment_summary' => $paymentSummary,
                'unpaid_invoices' => $unpaidInvoices->map(fn ($inv) => [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'total_amount' => (float) $inv->total_amount,
                    'paid_amount' => (float) $inv->paid_amount,
                    'balance_amount' => (float) $inv->balance_amount,
                    'payment_status' => $inv->payment_status,
                ]),
                'pending_charges' => $pendingCharges, // All charges across branches
                'branch_pending_charges' => $branchPendingCharges, // Charges for this branch only
                'pending_charges_count' => count($pendingCharges),
                'recent_visits' => $recentVisits->map(function($visit) {
                    return [
                        'id' => $visit->id,
                        'visit_type' => $visit->visit_type,
                        'created_at' => $visit->created_at->format('M d, Y H:i'),
                        'status' => $visit->status,
                        'consultation' => $visit->consultation ? [
                            'id' => $visit->consultation->id,
                            'status' => $visit->consultation->status
                        ] : null
                    ];
                })
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('Patient not found', ['patient_id' => $patientId]);
            return response()->json([
                'success' => false,
                'message' => 'Patient not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error getting patient charges', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading patient details.'
            ], 500);
        }
    }

    /**
     * Process payment for selected charges
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'charges' => 'required|array',
            'charges.*.type' => 'required|string',
            'charges.*.id' => 'required',
            'charges.*.amount' => 'required|numeric|min:0',
            'payment_method' => 'required|' . PaymentMethod::validationRule(true),
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'amount_tendered' => 'nullable|numeric|min:0',
            'momo_phone' => 'nullable|string',
            'momo_network' => 'nullable|in:MTN,Vodafone,AirtelTigo',
            'momo_reference' => 'nullable|string',
            'payment_reference' => 'nullable|string',
            'transaction_id' => 'nullable|string',
        ]);

        $branchId = $this->resolveUserBranchId('process_payments');

        DB::beginTransaction();

        try {
            $patient = Patient::findOrFail($request->patient_id);

            $this->paymentPolicyService->validateChargePaymentAmount(
                null,
                (float) $request->total_amount,
                (float) $request->total_amount,
                (int) $patient->id
            );
            
            // Create consolidated invoice
            $invoice = Invoice::create([
                'patient_id' => $patient->id,
                'branch_id' => $branchId,
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'subtotal' => $request->total_amount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $request->total_amount,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
                'items' => $this->prepareInvoiceItems($request->charges)
            ]);
            // Note: InvoiceObserver will initialize paid_amount=0, balance_amount=total_amount, payment_status='unpaid'

            // Process each charge
            foreach ($request->charges as $charge) {
                $this->processCharge($charge, $invoice->id, $branchId);
            }

            $paymentMetadata = PaymentMetadata::fromRequest($request, [
                'notes' => $request->notes,
                'processed_by' => auth()->id(),
                'reference_number' => $request->payment_reference ?? $this->generatePaymentReference(),
                'source_platform' => 'web',
                'ip_address' => $request->ip(),
            ]);

            // Use PaymentService to record payment (handles all cascading updates)
            $paymentResult = $this->paymentService->recordPayment(
                $invoice->id,
                $request->total_amount,
                $request->payment_method,
                $paymentMetadata
            );

            if (!$paymentResult['success']) {
                throw new \Exception($paymentResult['message']);
            }

            $payment = $paymentResult['payment'];
            $invoice = $paymentResult['invoice']; // Get refreshed invoice with updated amounts
            
            // PaymentObserver has automatically:
            // - Updated invoice.paid_amount
            // - Updated invoice.balance_amount  
            // - Updated invoice.payment_status
            // - Created revenue transaction
            // - Updated debtor records

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully!',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'payment_id' => $payment->id,
                'paid_amount' => $invoice->paid_amount,
                'balance_amount' => $invoice->balance_amount,
                'payment_status' => $invoice->payment_status
            ]);

        } catch (\InvalidArgumentException $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'payment_policy_violation' => true,
            ], 422);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pay an existing invoice (full for OPD, partial allowed for IPD).
     */
    public function payInvoice(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|' . PaymentMethod::validationRule(true),
            'notes' => 'nullable|string',
            'amount_tendered' => 'nullable|numeric|min:0',
            'momo_phone' => 'nullable|string',
            'momo_network' => 'nullable|in:MTN,Vodafone,AirtelTigo',
            'momo_reference' => 'nullable|string',
        ]);

        $branchId = $this->resolveUserBranchId('process_payments');

        DB::beginTransaction();

        try {
            $invoice = Invoice::where('id', $request->invoice_id)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->firstOrFail();

            $activeVisit = Visit::where('patient_id', $invoice->patient_id)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            $this->paymentPolicyService->validatePaymentAmount($activeVisit, $invoice, (float) $request->amount);

            $paymentResult = $this->paymentService->recordPayment(
                $invoice->id,
                (float) $request->amount,
                $request->payment_method,
                PaymentMetadata::fromRequest($request, [
                    'notes' => $request->notes,
                    'processed_by' => auth()->id(),
                    'reference_number' => $this->generatePaymentReference(),
                    'source_platform' => 'web',
                    'ip_address' => $request->ip(),
                    'visit' => $activeVisit,
                ])
            );

            if (!$paymentResult['success']) {
                throw new \Exception($paymentResult['message']);
            }

            DB::commit();

            $invoice = $paymentResult['invoice'];

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'payment_id' => $paymentResult['payment']->id,
                'invoice_number' => $invoice->invoice_number,
                'paid_amount' => $invoice->paid_amount,
                'balance_amount' => $invoice->balance_amount,
                'payment_status' => $invoice->payment_status,
            ]);
        } catch (\InvalidArgumentException $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'payment_policy_violation' => true,
            ], 422);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate receipt for payment
     */
    public function generateReceipt($payment)
    {
        // If payment is an ID (not a model), fetch it
        if (!$payment instanceof Payment) {
            $payment = Payment::with(['invoice.patient', 'invoice.branch'])->findOrFail($payment);
        }
        
        return $this->billingPdfService->generateReceipt($payment);
    }

    /**
     * Branch-wide payment history with filters, pagination, and CSV export.
     */
    public function branchPaymentHistory(Request $request)
    {
        $branchId = (int) $request->get('branch_id', $this->resolveUserBranchId(['process_payments', 'view_payments']));

        $query = $this->scopePaymentsToBranch(
            Payment::with(['invoice.patient', 'patient', 'processor']),
            $branchId
        );

        if ($request->filled('start_date')) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('patient_search')) {
            $term = $request->patient_search;
            $query->where(function ($q) use ($term) {
                $q->whereHas('patient', function ($pq) use ($term) {
                    $pq->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('patient_number', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                })->orWhereHas('invoice.patient', function ($pq) use ($term) {
                    $pq->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('patient_number', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            });
        }

        if ($request->get('export') === 'csv') {
            if (! auth()->user()->can('view_payments') && ! auth()->user()->can('process_payments')) {
                abort(403, 'Unauthorized to export payment history.');
            }

            return $this->exportFromQuery($request, (clone $query)->latest('payment_date'), [
                'Date' => fn ($p) => $this->formatExportDate($p->payment_date ?? $p->created_at),
                'Patient' => fn ($p) => $p->patient?->full_name ?? $p->invoice?->patient?->full_name ?? '',
                'Patient Number' => fn ($p) => $p->patient?->patient_number ?? $p->invoice?->patient?->patient_number ?? '',
                'Invoice #' => fn ($p) => $p->invoice?->invoice_number ?? '',
                'Amount' => 'amount',
                'Method' => fn ($p) => PaymentMethod::labelFor($p->payment_method),
                'Status' => 'status',
                'Reference' => fn ($p) => $p->reference_number ?? $p->payment_reference ?? '',
                'Processed By' => fn ($p) => $this->formatExportUserName($p->processor),
            ], 'payment-history');
        }

        $totalsQuery = clone $query;
        $totalsByMethod = $totalsQuery
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($row) => [
                'payment_method' => $row->payment_method,
                'label' => PaymentMethod::labelFor($row->payment_method),
                'count' => (int) $row->count,
                'total' => (float) $row->total,
            ]);

        $grandTotal = (float) (clone $query)->sum('amount');

        $payments = $query->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'payments' => $payments,
            'totals_by_method' => $totalsByMethod,
            'grand_total' => $grandTotal,
            'branch_id' => $branchId,
        ]);
    }

    /**
     * Get payment history for patient
     */
    public function getPaymentHistory($patientId)
    {
        $branchId = $this->resolveUserBranchId(['process_payments', 'view_payments']);

        $payments = $this->scopePaymentsToBranch(
            Payment::with(['invoice.patient', 'patient', 'processor']),
            $branchId
        )
            ->where(function ($q) use ($patientId) {
                $q->where('patient_id', $patientId)
                    ->orWhereHas('invoice', fn ($iq) => $iq->where('patient_id', $patientId));
            })
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'payments' => $payments
        ]);
    }

    /**
     * Generate invoice number
     */
    private function generateInvoiceNumber()
    {
        $prefix = 'INV';
        $year = now()->year;
        $month = now()->format('m');
        
        $lastInvoice = Invoice::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastInvoice ? (intval(substr($lastInvoice->invoice_number, -4)) + 1) : 1;
        
        return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate payment reference
     */
    private function generatePaymentReference()
    {
        return 'PAY' . now()->format('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Prepare invoice items from charges
     */
    private function prepareInvoiceItems($charges)
    {
        $items = [];

        foreach ($charges as $charge) {
            $description = $charge['description'] ?? $this->getChargeDescription($charge);

            $items[] = [
                'id' => $charge['line_id'] ?? uniqid(),
                'description' => $description,
                'quantity' => 1,
                'unit_price' => $charge['amount'],
                'total' => $charge['amount'],
                'service_type' => $charge['type'],
                'charge_component' => $charge['charge_component'] ?? null,
                'parent_id' => $charge['id'] ?? null,
            ];
        }

        return $items;
    }

    /**
     * Get description for charge type
     */
    private function getChargeDescription($charge)
    {
        $descriptions = [
            'consultation' => 'Medical Consultation',
            'lab_test' => 'Laboratory Test',
            'prescription' => 'Prescription Medication',
            'radiology' => 'Radiology Service',
            'procedure' => 'Medical Procedure',
            'other' => 'Other Service'
        ];
        
        return $descriptions[$charge['type']] ?? 'Service Charge';
    }

    /**
     * Process individual charge
     */
    private function processCharge($charge, $invoiceId, $branchId)
    {
        switch ($charge['type']) {
            case 'consultation':
                $consultation = Consultation::find($charge['id']);
                if ($consultation) {
                    $consultation->update(['billing_status' => 'billed', 'invoice_id' => $invoiceId]);
                }
                break;
                
            case 'lab_test':
                $labRequest = LabRequest::find($charge['id']);
                if ($labRequest) {
                    $labRequest->update(['billing_status' => 'billed', 'invoice_id' => $invoiceId]);
                }
                break;
                
            case 'prescription':
                $prescription = Prescription::find($charge['id']);
                if ($prescription) {
                    $prescription->update(['billing_status' => 'billed', 'invoice_id' => $invoiceId]);
                }
                break;
                
            case 'radiology':
                $radiologyRequest = RadiologyRequest::find($charge['id']);
                if ($radiologyRequest) {
                    $radiologyRequest->update(['billing_status' => 'billed', 'invoice_id' => $invoiceId]);
                }
                break;
        }
    }

    /**
     * Generate individual module receipt
     */
    public function generateModuleReceipt(Request $request, $module, $id)
    {
        $this->authorize('process_payments');
        
        $data = [];
        $filename = '';
        
        switch ($module) {
            case 'consultation':
                $consultation = Consultation::with(['patient', 'doctor', 'visit'])->findOrFail($id);
                $data = [
                    'type' => 'Consultation',
                    'item' => $consultation,
                    'patient' => $consultation->patient,
                    'doctor' => $consultation->doctor,
                    'amount' => $consultation->billing_amount ?? $this->pendingChargesService->resolveChargeAmount('consultation', $consultation),
                    'date' => $consultation->created_at,
                ];
                $filename = 'consultation-receipt-' . $consultation->consultation_number . '.pdf';
                break;
                
            case 'lab':
                $labRequest = LabRequest::with(['patient', 'doctor', 'visit', 'testType'])->findOrFail($id);
                $data = [
                    'type' => 'Lab Test',
                    'item' => $labRequest,
                    'patient' => $labRequest->patient,
                    'doctor' => $labRequest->doctor,
                    'amount' => $labRequest->billing_amount ?? $this->pendingChargesService->resolveChargeAmount('lab_test', $labRequest),
                    'date' => $labRequest->created_at,
                ];
                $filename = 'lab-receipt-' . $labRequest->lab_request_number . '.pdf';
                break;
                
            case 'prescription':
                $prescription = Prescription::with(['patient', 'doctor', 'visit', 'orders.drug'])->findOrFail($id);
                $data = [
                    'type' => 'Prescription',
                    'item' => $prescription,
                    'patient' => $prescription->patient,
                    'doctor' => $prescription->doctor,
                    'amount' => $prescription->billing_amount ?? $this->pendingChargesService->resolveChargeAmount('prescription', $prescription),
                    'date' => $prescription->created_at,
                ];
                $filename = 'prescription-receipt-' . $prescription->prescription_number . '.pdf';
                break;
                
            case 'radiology':
                $radiologyRequest = RadiologyRequest::with(['patient', 'doctor', 'visit'])->findOrFail($id);
                $data = [
                    'type' => 'Radiology',
                    'item' => $radiologyRequest,
                    'patient' => $radiologyRequest->patient,
                    'doctor' => $radiologyRequest->doctor,
                    'amount' => $radiologyRequest->billing_amount ?? $this->pendingChargesService->resolveChargeAmount('radiology', $radiologyRequest),
                    'date' => $radiologyRequest->created_at,
                ];
                $filename = 'radiology-receipt-' . $radiologyRequest->request_number . '.pdf';
                break;
                
            default:
                abort(404, 'Module not found');
        }
        
        // Get branding and settings data
        $branding = BrandingSetting::current();
        $settings = SystemSetting::current();
        
        $data['branding'] = [
            'business_name' => $branding->business_name,
            'business_address' => $branding->business_address,
            'business_phone' => $branding->business_phone,
            'business_email' => $branding->business_email,
            'business_website' => $branding->business_website,
        ];
        
        $data['settings'] = [
            'hospital_name' => $settings->hospital_name ?? null,
            'hospital_address' => $settings->hospital_address ?? null,
            'hospital_phone' => $settings->hospital_phone ?? null,
            'hospital_email' => $settings->hospital_email ?? null,
        ];
        
        $pdf = PDF::loadView('cashier.module-receipt', $data);
        return $pdf->download($filename);
    }

    /**
     * Get payment history for a specific module
     */
    public function getModulePaymentHistory(Request $request, $module, $patientId)
    {
        $this->authorize('process_payments');
        
        $patient = Patient::findOrFail($patientId);
        $payments = collect();
        
        switch ($module) {
            case 'consultation':
                $consultations = Consultation::where('patient_id', $patientId)
                    ->where('billing_status', '!=', 'pending')
                    ->with(['doctor'])
                    ->get();
                    
                foreach ($consultations as $consultation) {
                    $payments->push([
                        'id' => $consultation->id,
                        'type' => 'Consultation',
                        'description' => 'Consultation with Dr. ' . $consultation->doctor->name,
                        'amount' => $consultation->billing_amount ?? 0,
                        'status' => $consultation->billing_status,
                        'date' => $consultation->created_at,
                        'billed_at' => $consultation->billed_at,
                    ]);
                }
                break;
                
            case 'lab':
                $labRequests = LabRequest::where('patient_id', $patientId)
                    ->where('billing_status', '!=', 'pending')
                    ->with(['testType'])
                    ->get();
                    
                foreach ($labRequests as $labRequest) {
                    $payments->push([
                        'id' => $labRequest->id,
                        'type' => 'Lab Test',
                        'description' => 'Lab Test - ' . $labRequest->test_type,
                        'amount' => $labRequest->billing_amount ?? 0,
                        'status' => $labRequest->billing_status,
                        'date' => $labRequest->created_at,
                        'billed_at' => $labRequest->billed_at,
                    ]);
                }
                break;
                
            case 'prescription':
                $prescriptions = Prescription::where('patient_id', $patientId)
                    ->where('billing_status', '!=', 'pending')
                    ->with(['doctor', 'orders.drug'])
                    ->get();
                    
                foreach ($prescriptions as $prescription) {
                    $payments->push([
                        'id' => $prescription->id,
                        'type' => 'Prescription',
                        'description' => 'Prescription - ' . $prescription->orders->count() . ' items',
                        'amount' => $prescription->billing_amount ?? 0,
                        'status' => $prescription->billing_status,
                        'date' => $prescription->created_at,
                        'billed_at' => $prescription->billed_at,
                    ]);
                }
                break;
                
            case 'radiology':
                $radiologyRequests = RadiologyRequest::where('patient_id', $patientId)
                    ->where('billing_status', '!=', 'pending')
                    ->with(['doctor'])
                    ->get();
                    
                foreach ($radiologyRequests as $radiologyRequest) {
                    $payments->push([
                        'id' => $radiologyRequest->id,
                        'type' => 'Radiology',
                        'description' => 'Radiology - ' . $radiologyRequest->study_type,
                        'amount' => $radiologyRequest->billing_amount ?? 0,
                        'status' => $radiologyRequest->billing_status,
                        'date' => $radiologyRequest->created_at,
                        'billed_at' => $radiologyRequest->billed_at,
                    ]);
                }
                break;
                
            default:
                abort(404, 'Module not found');
        }
        
        return response()->json($payments->sortByDesc('date')->values());
    }

    /**
     * Get patient's debt information
     */
    public function getPatientDebtInfo(Patient $patient)
    {
        $branchId = $this->resolveUserBranchId('process_payments');

        // Get or create debtor record
        $debtor = $this->debtorService->createOrUpdateDebtor($patient->id, $branchId, auth()->id());
        
        // Get outstanding invoices
        $outstandingInvoices = $debtor->outstandingInvoices()->get();
        $overdueInvoices = $debtor->overdueInvoices()->get();
        
        // Get recent payment history
        $recentPayments = $debtor->paymentHistory()
            ->with(['invoice', 'payment', 'processor'])
            ->latest('payment_date')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'debtor' => [
                'id' => $debtor->id,
                'total_outstanding' => $debtor->total_outstanding,
                'total_paid' => $debtor->total_paid,
                'total_invoiced' => $debtor->total_invoiced,
                'debt_status' => $debtor->debt_status,
                'days_overdue' => $debtor->days_overdue,
                'outstanding_invoices_count' => $debtor->outstanding_invoices_count,
                'overdue_invoices_count' => $debtor->overdue_invoices_count,
                'last_payment_date' => $debtor->last_payment_date,
                'payment_percentage' => $debtor->getPaymentPercentage()
            ],
            'outstanding_invoices' => $outstandingInvoices->map(function($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date,
                    'due_date' => $invoice->due_date,
                    'total_amount' => $invoice->total_amount,
                    'balance_amount' => $invoice->balance_amount,
                    'status' => $invoice->status,
                    'days_overdue' => $invoice->due_date ? now()->diffInDays($invoice->due_date) : 0
                ];
            }),
            'overdue_invoices' => $overdueInvoices->map(function($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date,
                    'due_date' => $invoice->due_date,
                    'total_amount' => $invoice->total_amount,
                    'balance_amount' => $invoice->balance_amount,
                    'days_overdue' => $invoice->due_date ? now()->diffInDays($invoice->due_date) : 0
                ];
            }),
            'recent_payments' => $recentPayments->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'payment_amount' => $payment->payment_amount,
                    'payment_date' => $payment->payment_date,
                    'payment_method' => $payment->payment_method,
                    'reference_number' => $payment->reference_number,
                    'invoice_number' => $payment->invoice->invoice_number,
                    'processor_name' => $payment->processor ? $payment->processor->first_name . ' ' . $payment->processor->last_name : 'N/A'
                ];
            })
        ]);
    }

    /**
     * Get all pending payments for the branch
     */
    public function getAllPendingPayments(Request $request)
    {
        $branchId = $this->resolveUserBranchId('process_payments');

        // Get all pending charges for the branch
        $pendingCharges = $this->pendingChargesService->getPatientPendingCharges(null, $branchId);
        
        // Group by patient
        $groupedCharges = collect($pendingCharges)->groupBy('patient_id');
        
        $patientsWithCharges = [];
        foreach ($groupedCharges as $patientId => $charges) {
            $patient = Patient::find($patientId);
            if ($patient) {
                $patientsWithCharges[] = [
                    'patient' => [
                        'id' => $patient->id,
                        'patient_number' => $patient->patient_number,
                        'name' => $patient->full_name,
                        'phone' => $patient->phone,
                        'email' => $patient->email
                    ],
                    'charges' => $charges,
                    'total_amount' => collect($charges)->sum('amount'),
                    'charges_count' => count($charges)
                ];
            }
        }

        // Sort by total amount descending
        $patientsWithCharges = collect($patientsWithCharges)->sortByDesc('total_amount')->values();

        return response()->json([
            'success' => true,
            'patients_with_charges' => $patientsWithCharges,
            'total_pending_amount' => collect($pendingCharges)->sum('amount'),
            'total_patients' => count($patientsWithCharges)
        ]);
    }

    /**
     * Generate daily report
     */
    public function generateDailyReport(Request $request)
    {
        $branchId = $this->resolveUserBranchId(['process_payments', 'view_cashier_reports']);

        $date = $request->get('date', now()->toDateString());
        $startOfDay = Carbon::parse($date)->startOfDay();
        $endOfDay = Carbon::parse($date)->endOfDay();

        // Get daily statistics
        $statistics = [
            'date' => $date,
            'total_patients_served' => Visit::where('branch_id', $branchId)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->count(),
            'total_payments' => $this->scopePaymentsToBranch(Payment::query(), $branchId)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->count(),
            'total_collected' => $this->scopePaymentsToBranch(Payment::query(), $branchId)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->sum('amount'),
            'pending_payments' => $this->pendingChargesService->getPendingChargesCount($branchId),
            'outstanding_amount' => $this->pendingChargesService->getOutstandingAmount($branchId)
        ];

        // Get payment breakdown by method
        $paymentBreakdown = $this->scopePaymentsToBranch(Payment::query(), $branchId)
        ->whereBetween('created_at', [$startOfDay, $endOfDay])
        ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
        ->groupBy('payment_method')
        ->get();

        // Get recent payments
        $recentPayments = $this->scopePaymentsToBranch(
            Payment::with(['invoice.patient', 'patient']),
            $branchId
        )
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->latest()
            ->get();

        // Get pending charges
        $pendingCharges = $this->pendingChargesService->getPatientPendingCharges(null, $branchId);

        // Check if PDF export is requested
        if ($request->has('export') && $request->get('export') === 'pdf') {
            return $this->generateDailyReportPdf($statistics, $paymentBreakdown, $recentPayments, $pendingCharges, $date, $branchId);
        }

        if (!$request->expectsJson() && !$request->ajax()) {
            return view('cashier.daily-report', compact(
                'statistics',
                'paymentBreakdown',
                'recentPayments',
                'pendingCharges',
                'date',
                'branchId'
            ));
        }

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
            'payment_breakdown' => $paymentBreakdown,
            'recent_payments' => $recentPayments,
            'pending_charges' => $pendingCharges
        ]);
    }

    /**
     * Get outstanding debts for the branch
     * Includes both outstanding invoices and pending charges
     */
    public function getOutstandingDebts(Request $request)
    {
        $branchId = $this->resolveUserBranchId('process_payments');

        // 1. Get all debtors for this branch with outstanding invoices
        $debtors = \App\Models\Debtor::where('branch_id', $branchId)
            ->with(['patient', 'outstandingInvoices' => function($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                      ->where('balance_amount', '>', 0) // Only invoices with actual balance
                      ->with('payments');  // Load payments to calculate balance
            }])
            ->where('total_outstanding', '>', 0)
            ->orderBy('total_outstanding', 'desc')
            ->get();

        // 2. Get all unpaid/partial invoices (not paid or cancelled) with balance > 0
        $unpaidInvoices = \App\Models\Invoice::where('branch_id', $branchId)
            ->where(function($query) {
                $query->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
                      ->orWhereIn('status', ['pending', 'draft', 'partial', 'overdue']);
            })
            ->where('status', '!=', 'cancelled')
            ->where('balance_amount', '>', 0) // Only invoices with actual balance
            ->with(['patient', 'payments'])
            ->orderBy('due_date', 'asc')
            ->get();

        // 3. Get pending charges (services not yet invoiced)
        $pendingCharges = $this->pendingChargesService->getPatientPendingCharges(null, $branchId);
        
        // Group pending charges by patient
        $pendingChargesByPatient = collect($pendingCharges)->groupBy('patient_id');

        // Calculate total outstanding from all sources
        $totalOutstandingFromInvoices = $unpaidInvoices->sum('balance_amount');
        $totalOutstandingFromPendingCharges = collect($pendingCharges)->sum('amount');
        $totalOutstanding = $totalOutstandingFromInvoices + $totalOutstandingFromPendingCharges;

        // Get debt summary (includes both invoices and pending charges)
        $debtSummary = [
            'total_debtors' => $debtors->count(),
            'total_outstanding' => round($totalOutstanding, 2),
            'total_outstanding_invoices' => round($totalOutstandingFromInvoices, 2),
            'total_pending_charges' => round($totalOutstandingFromPendingCharges, 2),
            'total_overdue' => $debtors->where('debt_status', 'overdue')->sum('total_outstanding') ?? 0,
            'average_debt' => $debtors->count() > 0 ? round($debtors->avg('total_outstanding'), 2) : 0,
            'debt_by_status' => $debtors->groupBy('debt_status')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'total_amount' => round($group->sum('total_outstanding'), 2)
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'debt_summary' => $debtSummary,
            'debtors' => $debtors->map(function($debtor) {
                // Get outstanding invoices with payment details (use balance_amount from invoice)
                $invoices = $debtor->outstandingInvoices->map(function($invoice) {
                    // Use balance_amount field which is the actual outstanding amount
                    $balance = $invoice->balance_amount ?? ($invoice->total_amount - $invoice->paid_amount);
                    
                    return [
                        'invoice_number' => $invoice->invoice_number,
                        'status' => $invoice->status,
                        'payment_status' => $invoice->payment_status,
                        'total_amount' => $invoice->total_amount,
                        'total_paid' => $invoice->paid_amount,
                        'balance' => round($balance, 2),
                        'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                        'is_overdue' => $invoice->due_date && $invoice->due_date < now()
                    ];
                });
                
                return [
                    'id' => $debtor->id,
                    'patient' => [
                        'id' => $debtor->patient->id,
                        'patient_number' => $debtor->patient->patient_number,
                        'name' => $debtor->patient->full_name,
                        'phone' => $debtor->patient->phone,
                        'email' => $debtor->patient->email
                    ],
                    'total_outstanding' => round($debtor->total_outstanding, 2),
                    'total_paid' => round($debtor->total_paid, 2),
                    'debt_status' => $debtor->debt_status,
                    'days_overdue' => $debtor->days_overdue,
                    'outstanding_invoices_count' => $debtor->outstanding_invoices_count,
                    'last_payment_date' => $debtor->last_payment_date,
                    'invoices' => $invoices,
                    'created_at' => $debtor->created_at
                ];
            }),
            'unpaid_invoices' => $unpaidInvoices->map(function($invoice) {
                // Use balance_amount field which is the actual outstanding amount
                $balance = $invoice->balance_amount ?? ($invoice->total_amount - $invoice->paid_amount);
                $dueDate = $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date) : null;
                $daysOverdue = $dueDate && $dueDate < now() ? abs($dueDate->diffInDays(now(), false)) : 0;
                
                // Determine payment status text
                $paymentStatus = 'Unpaid';
                if ($invoice->paid_amount > 0 && $balance > 0) {
                    $paymentStatus = 'Partial';
                } elseif ($balance <= 0) {
                    $paymentStatus = 'Paid'; // This shouldn't happen as we filter by status
                }
                
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'status' => $invoice->status,
                    'payment_status' => $paymentStatus,
                    'patient' => [
                        'id' => $invoice->patient->id,
                        'name' => $invoice->patient->full_name,
                        'phone' => $invoice->patient->phone
                    ],
                    'total_amount' => round($invoice->total_amount ?? 0, 2),
                    'total_paid' => round($invoice->paid_amount ?? 0, 2),
                    'balance' => round($balance, 2),
                    'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                    'days_overdue' => $daysOverdue,
                    'is_overdue' => $daysOverdue > 0,
                    'created_at' => $invoice->created_at
                ];
            }),
            'pending_charges' => $pendingChargesByPatient->map(function($charges, $patientId) {
                $patient = \App\Models\Patient::find($patientId);
                if (!$patient) {
                    return null;
                }
                
                return [
                    'patient' => [
                        'id' => $patient->id,
                        'patient_number' => $patient->patient_number,
                        'name' => $patient->full_name,
                        'phone' => $patient->phone
                    ],
                    'charges' => $charges->map(function($charge) {
                        return [
                            'type' => $charge['type'],
                            'description' => $charge['description'],
                            'amount' => round($charge['amount'], 2),
                            'date' => $charge['date']
                        ];
                    }),
                    'total_amount' => round($charges->sum('amount'), 2)
                ];
            })->filter()->values()
        ]);
    }

    /**
     * Generate daily report PDF
     */
    public function generateDailyReportPdf($statistics, $paymentBreakdown, $recentPayments, $pendingCharges, $date, $branchId)
    {
        try {
            $settingsService = new \App\Services\SettingsService();
            $branch = \App\Models\Branch::find($branchId);

            $data = [
                'statistics' => $statistics,
                'payment_breakdown' => $paymentBreakdown ?? collect(),
                'recent_payments' => $recentPayments ?? collect(),
                'pending_charges' => $pendingCharges ?? [],
                'date' => $date,
                'branch' => $branch,
                'branding' => $settingsService->getBrandingSettings(),
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'generated_by' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
            ];

            $pdf = Pdf::loadView('cashier.daily-report-pdf', $data);
            $filename = 'daily-report-' . $date . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            \Log::error('PDF generation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'PDF generation failed. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process a payment refund from the cashier UI.
     */
    public function refundPayment(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        $branchId = $this->resolveUserBranchId('process_payments');
        if ((int) $payment->branch_id !== (int) $branchId && !auth()->user()->hasRole('super_admin')) {
            abort(403, 'You cannot refund payments outside your branch.');
        }

        $result = $this->paymentService->refundPayment(
            $payment->id,
            $validated['amount'] ?? null,
            $validated['reason'] ?? null
        );

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }
}
