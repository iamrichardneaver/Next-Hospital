<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\PaymentService;
use App\Services\PaymentPolicyService;
use App\Services\PendingChargesService;
use App\Services\ModulePricingService;
use App\Services\DebtorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CashierController extends Controller
{
    use ResolvesUserBranch;

    protected $paymentService;
    protected $pendingChargesService;
    protected $debtorService;
    protected PaymentPolicyService $paymentPolicyService;

    public function __construct(
        PaymentService $paymentService,
        PaymentPolicyService $paymentPolicyService,
        PendingChargesService $pendingChargesService = null,
        DebtorService $debtorService = null
    ) {
        $this->paymentService = $paymentService;
        $this->paymentPolicyService = $paymentPolicyService;
        $this->pendingChargesService = $pendingChargesService ?? app(PendingChargesService::class);
        $this->debtorService = $debtorService ?? app(DebtorService::class);
    }
    /**
     * Get cashier dashboard data
     * Includes unified outstanding amount calculation (pending charges + outstanding invoices)
     */
    public function getDashboard(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $branchId = $this->resolveUserBranchId('process_payments');
            $today = Carbon::today();
            $thisMonth = now()->month;
            $thisYear = now()->year;

            $data = [
                'total_patients_served' => Visit::where('branch_id', $branchId)
                    ->whereDate('created_at', $today)
                    ->count(),
                'pending_payments' => $this->pendingChargesService->getPendingChargesCount($branchId),
                'total_collected' => Payment::whereHas('invoice', function($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })->whereDate('created_at', $today)->sum('amount'),
                'monthly_revenue' => Payment::whereHas('invoice', function($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->whereMonth('created_at', $thisMonth)
                ->whereYear('created_at', $thisYear)
                ->where('status', 'completed')
                ->sum('amount'),
                'outstanding_amount' => $this->pendingChargesService->getOutstandingAmount($branchId),
                'today_revenue' => $this->getTodayRevenue($branchId),
                'today_transactions' => $this->getTodayTransactionsCount($branchId),
                'payment_methods_summary' => $this->getPaymentMethodsSummary($branchId, $today),
                'recent_transactions' => $this->getRecentTransactions($branchId, 10),
                'shift_summary' => $this->getShiftSummary($user->id, $today),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending payments queue
     */
    public function getPendingPayments(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $branchId = $this->resolveUserBranchId('process_payments');

            $invoices = Invoice::with(['patient', 'created_by_user'])
                ->where('branch_id', $branchId)
                ->whereIn('status', ['pending', 'partial'])
                ->orderBy('created_at', 'asc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $invoices
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending payments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get invoices to process
     */
    public function getInvoices(Request $request): JsonResponse
    {
        try {
            $branchId = $this->resolveUserBranchId('process_payments');

            $query = Invoice::with(['patient', 'payments'])
                ->where('branch_id', $branchId);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by patient
            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }

            // Filter by date
            if ($request->has('date')) {
                $query->whereDate('created_at', $request->date);
            }

            // Search by invoice number or patient name
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('patient', function($pq) use ($search) {
                          $pq->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%");
                      });
                });
            }

            $invoices = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $invoices
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'invoice_id' => 'required|exists:invoices,id',
                'amount' => 'required|numeric|min:0',
                'payment_method' => 'required|' . \App\Enums\PaymentMethod::validationRule(true),
                'momo_phone' => 'nullable|string',
                'momo_network' => 'nullable|in:MTN,Vodafone,AirtelTigo',
                'momo_reference' => 'nullable|string',
                'amount_tendered' => 'nullable|numeric|min:0',
                'reference_number' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            $user = Auth::user();
            $branchId = $this->resolveUserBranchId('process_payments');
            $invoice = Invoice::where('id', $request->invoice_id)
                ->where('branch_id', $branchId)
                ->firstOrFail();

            $activeVisit = Visit::where('patient_id', $invoice->patient_id)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            $this->paymentPolicyService->validatePaymentAmount($activeVisit, $invoice, (float) $request->amount);

            // Use PaymentService for consistent payment recording
            $result = $this->paymentService->recordPayment(
                $invoice->id,
                $request->amount,
                $request->payment_method,
                \App\Support\PaymentMetadata::fromRequest($request, [
                    'reference_number' => $request->reference_number,
                    'notes' => $request->notes,
                    'processed_by' => $user->id,
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

            $payment = $result['payment'];
            $payment->load(['invoice.patient', 'processor', 'patient', 'branch']);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => $payment,
                'invoice' => [
                    'id' => $result['invoice']->id,
                    'invoice_number' => $result['invoice']->invoice_number,
                    'total_amount' => $result['invoice']->total_amount,
                    'paid_amount' => $result['invoice']->paid_amount,
                    'balance_amount' => $result['invoice']->balance_amount,
                    'payment_status' => $result['invoice']->payment_status,
                    'status' => $result['invoice']->status
                ]
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
                'message' => 'Failed to process payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment history
     */
    public function getPaymentHistory(Request $request): JsonResponse
    {
        try {
            $branchId = $this->resolveUserBranchId('process_payments');

            $query = Payment::with(['invoice.patient', 'processed_by_user'])
                ->where('branch_id', $branchId);

            // Filter by date range
            if ($request->has('start_date')) {
                $query->whereDate('payment_date', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->whereDate('payment_date', '<=', $request->end_date);
            }

            // Filter by payment method
            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            // Filter by cashier
            if ($request->has('processed_by')) {
                $query->where('processed_by', $request->processed_by);
            }

            $payments = $query->orderBy('payment_date', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $payments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get daily summary
     */
    public function getDailySummary(Request $request): JsonResponse
    {
        try {
            $branchId = $this->resolveUserBranchId('process_payments');
            $date = $request->get('date', Carbon::today());

            $summary = [
                'total_revenue' => Payment::where('branch_id', $branchId)
                    ->whereDate('payment_date', $date)
                    ->sum('amount'),
                'total_transactions' => Payment::where('branch_id', $branchId)
                    ->whereDate('payment_date', $date)
                    ->count(),
                'by_payment_method' => Payment::where('branch_id', $branchId)
                    ->whereDate('payment_date', $date)
                    ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                    ->groupBy('payment_method')
                    ->get(),
                'by_cashier' => Payment::where('branch_id', $branchId)
                    ->whereDate('payment_date', $date)
                    ->with('processed_by_user:id,first_name,last_name')
                    ->select('processed_by', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                    ->groupBy('processed_by')
                    ->get(),
                'pending_invoices' => Invoice::where('branch_id', $branchId)
                    ->whereIn('status', ['pending', 'partial'])
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch daily summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Full daily report (parity with web cashier/daily-report).
     */
    public function getDailyReport(Request $request): JsonResponse
    {
        try {
            $branchId = $this->resolveUserBranchId(['process_payments', 'view_cashier_reports']);
            $date = $request->get('date', now()->toDateString());
            $startOfDay = Carbon::parse($date)->startOfDay();
            $endOfDay = Carbon::parse($date)->endOfDay();

            $statistics = [
                'date' => $date,
                'total_patients_served' => Visit::where('branch_id', $branchId)
                    ->whereBetween('created_at', [$startOfDay, $endOfDay])
                    ->count(),
                'total_payments' => Payment::where('branch_id', $branchId)
                    ->whereBetween('created_at', [$startOfDay, $endOfDay])
                    ->count(),
                'total_collected' => Payment::where('branch_id', $branchId)
                    ->whereBetween('created_at', [$startOfDay, $endOfDay])
                    ->sum('amount'),
                'pending_payments' => $this->pendingChargesService->getPendingChargesCount($branchId),
                'outstanding_amount' => $this->pendingChargesService->getOutstandingAmount($branchId),
            ];

            $paymentBreakdown = Payment::where('branch_id', $branchId)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('payment_method')
                ->get();

            $recentPayments = Payment::with(['invoice.patient', 'patient'])
                ->where('branch_id', $branchId)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->latest()
                ->get();

            $pendingCharges = $this->pendingChargesService->getPatientPendingCharges(null, $branchId);

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $statistics,
                    'payment_breakdown' => $paymentBreakdown,
                    'recent_payments' => $recentPayments,
                    'pending_charges' => $pendingCharges,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch daily report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Shift handover
     */
    public function shiftHandover(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $branchId = $this->resolveUserBranchId('process_payments');
            $today = Carbon::today();

            $handoverData = [
                'cashier' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'date' => $today->toDateString(),
                'shift_start' => $request->shift_start ?? $today->toDateTimeString(),
                'shift_end' => now()->toDateTimeString(),
                'summary' => $this->getShiftSummary($user->id, $today),
            ];

            // Here you could save the handover record to a database table

            return response()->json([
                'success' => true,
                'message' => 'Shift handover completed',
                'data' => $handoverData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete handover: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cashier statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $branchId = $this->resolveUserBranchId('process_payments');
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now());

            $stats = [
                'total_processed' => Payment::where('branch_id', $branchId)
                    ->where('processed_by', $user->id)
                    ->whereBetween('payment_date', [$startDate, $endDate])
                    ->sum('amount'),
                'transactions_count' => Payment::where('branch_id', $branchId)
                    ->where('processed_by', $user->id)
                    ->whereBetween('payment_date', [$startDate, $endDate])
                    ->count(),
                'average_transaction' => Payment::where('branch_id', $branchId)
                    ->where('processed_by', $user->id)
                    ->whereBetween('payment_date', [$startDate, $endDate])
                    ->avg('amount'),
                'by_payment_method' => Payment::where('branch_id', $branchId)
                    ->where('processed_by', $user->id)
                    ->whereBetween('payment_date', [$startDate, $endDate])
                    ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                    ->groupBy('payment_method')
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    // Private helper methods
    private function getPendingPaymentsCount($branchId)
    {
        return Invoice::where('branch_id', $branchId)
            ->whereIn('status', ['pending', 'partial'])
            ->count();
    }

    private function getTodayRevenue($branchId)
    {
        return Payment::where('branch_id', $branchId)
            ->whereDate('payment_date', Carbon::today())
            ->sum('amount');
    }

    private function getTodayTransactionsCount($branchId)
    {
        return Payment::where('branch_id', $branchId)
            ->whereDate('payment_date', Carbon::today())
            ->count();
    }

    private function getPaymentMethodsSummary($branchId, $date)
    {
        return Payment::where('branch_id', $branchId)
            ->whereDate('payment_date', $date)
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();
    }

    private function getRecentTransactions($branchId, $limit = 10)
    {
        return Payment::with(['invoice.patient', 'processed_by_user'])
            ->where('branch_id', $branchId)
            ->orderBy('payment_date', 'desc')
            ->limit($limit)
            ->get();
    }

    private function getShiftSummary($userId, $date)
    {
        return [
            'total_collected' => Payment::where('processed_by', $userId)
                ->whereDate('payment_date', $date)
                ->sum('amount'),
            'transactions' => Payment::where('processed_by', $userId)
                ->whereDate('payment_date', $date)
                ->count(),
            'by_method' => Payment::where('processed_by', $userId)
                ->whereDate('payment_date', $date)
                ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('payment_method')
                ->get(),
        ];
    }

    /**
     * Search for patient and their pending charges
     * Matches Web CashierController::searchPatient()
     */
    public function searchPatient(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'search_term' => 'required|string|min:2'
            ]);

            $searchTerm = $request->search_term;
            $user = Auth::user();
            $branchId = $this->resolveUserBranchId('process_payments');

            if (!$branchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not assigned to any branch',
                    'patients' => []
                ], 403);
            }

            // Search patients by various criteria
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
                            'total_outstanding' => round($debtor->total_outstanding, 2),
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
                    \Log::error('Error processing patient in API search', [
                        'patient_id' => $patient->id ?? null,
                        'error' => $e->getMessage()
                    ]);
                    return null;
                }
            })->filter()->values();

            return response()->json([
                'success' => true,
                'patients' => $patientsData,
                'count' => $patientsData->count()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid search term. Please enter at least 2 characters.',
                'patients' => []
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error in API patient search', [
                'error' => $e->getMessage()
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
     * Matches Web CashierController::getPatientCharges()
     */
    public function getPatientCharges(Request $request, $patientId): JsonResponse
    {
        try {
            $user = Auth::user();
            $branchId = $this->resolveUserBranchId('process_payments');

            if (!$branchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not assigned to any branch'
                ], 403);
            }

            $patient = Patient::findOrFail($patientId);

            // Get pending charges using PendingChargesService
            $rawCharges = $this->pendingChargesService->getPatientPendingCharges($patientId, $branchId);
            $pendingCharges = app(ModulePricingService::class)->formatChargeLinesForApi($rawCharges);

            // Get outstanding invoices
            $outstandingInvoices = Invoice::where('patient_id', $patientId)
                ->where('branch_id', $branchId)
                ->where(function($query) {
                    $query->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
                          ->orWhereIn('status', ['pending', 'draft', 'partial', 'overdue']);
                })
                ->where('status', '!=', 'cancelled')
                ->where('balance_amount', '>', 0)
                ->with('payments')
                ->orderBy('invoice_date', 'desc')
                ->get();

            // Get debt information
            $debtor = $this->debtorService->createOrUpdateDebtor($patientId, $branchId, $user->id);
            $paymentPolicy = $this->paymentPolicyService->resolvePatientBillingContext((int) $patientId);
            $paymentSummary = $this->paymentPolicyService->getPaymentStatusSummary((int) $patientId, $branchId);

            return response()->json([
                'success' => true,
                'patient' => [
                    'id' => $patient->id,
                    'patient_number' => $patient->patient_number,
                    'name' => $patient->full_name,
                    'phone' => $patient->phone,
                    'email' => $patient->email
                ],
                'payment_policy' => $paymentPolicy,
                'payment_summary' => $paymentSummary,
                'pending_charges' => $pendingCharges,
                'outstanding_invoices' => $outstandingInvoices->map(function($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'status' => $invoice->status,
                        'payment_status' => $invoice->payment_status,
                        'total_amount' => round($invoice->total_amount, 2),
                        'paid_amount' => round($invoice->paid_amount, 2),
                        'balance_amount' => round($invoice->balance_amount, 2),
                        'invoice_date' => $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : null,
                        'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                        'is_overdue' => $invoice->due_date && $invoice->due_date < now()
                    ];
                }),
                'debt_info' => [
                    'total_outstanding' => round($debtor->total_outstanding, 2),
                    'total_paid' => round($debtor->total_paid, 2),
                    'total_invoiced' => round($debtor->total_invoiced, 2),
                    'debt_status' => $debtor->debt_status,
                    'days_overdue' => $debtor->days_overdue,
                    'outstanding_invoices_count' => $debtor->outstanding_invoices_count
                ],
                'total_pending_amount' => round(collect($rawCharges)->sum('amount'), 2),
                'total_outstanding_invoices' => round($outstandingInvoices->sum('balance_amount'), 2),
                'total_outstanding' => round(
                    collect($rawCharges)->sum('amount') + $outstandingInvoices->sum('balance_amount'),
                    2
                )
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error getting patient charges via API', [
                'patient_id' => $patientId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get patient charges: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get outstanding debts for the branch
     * Matches Web CashierController::getOutstandingDebts()
     * Includes both outstanding invoices and pending charges
     */
    public function getOutstandingDebts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $branchId = $this->resolveUserBranchId('process_payments');

            if (!$branchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not assigned to any branch'
                ], 403);
            }

            // 1. Get all debtors for this branch with outstanding invoices
            $debtors = \App\Models\Debtor::where('branch_id', $branchId)
                ->with(['patient', 'outstandingInvoices' => function($query) use ($branchId) {
                    $query->where('branch_id', $branchId)
                          ->where('balance_amount', '>', 0)
                          ->with('payments');
                }])
                ->where('total_outstanding', '>', 0)
                ->orderBy('total_outstanding', 'desc')
                ->get();

            // 2. Get all unpaid/partial invoices with balance > 0
            $unpaidInvoices = Invoice::where('branch_id', $branchId)
                ->where(function($query) {
                    $query->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
                          ->orWhereIn('status', ['pending', 'draft', 'partial', 'overdue']);
                })
                ->where('status', '!=', 'cancelled')
                ->where('balance_amount', '>', 0)
                ->with(['patient', 'payments'])
                ->orderBy('due_date', 'asc')
                ->get();

            // 3. Get pending charges (services not yet invoiced)
            $pendingCharges = $this->pendingChargesService->getPatientPendingCharges(null, $branchId);
            $pendingChargesByPatient = collect($pendingCharges)->groupBy('patient_id');

            // 4. Calculate totals
            $totalOutstandingFromInvoices = $unpaidInvoices->sum('balance_amount');
            $totalOutstandingFromPendingCharges = collect($pendingCharges)->sum('amount');
            $totalOutstanding = $totalOutstandingFromInvoices + $totalOutstandingFromPendingCharges;

            // 5. Get debt summary
            $debtSummary = [
                'total_debtors' => $debtors->count(),
                'total_outstanding' => round($totalOutstanding, 2),
                'total_outstanding_invoices' => round($totalOutstandingFromInvoices, 2),
                'total_pending_charges' => round($totalOutstandingFromPendingCharges, 2),
                'total_overdue' => round($debtors->where('debt_status', 'overdue')->sum('total_outstanding'), 2),
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
                    $invoices = $debtor->outstandingInvoices->map(function($invoice) {
                        $balance = $invoice->balance_amount ?? ($invoice->total_amount - $invoice->paid_amount);
                        
                        return [
                            'invoice_number' => $invoice->invoice_number,
                            'status' => $invoice->status,
                            'payment_status' => $invoice->payment_status,
                            'total_amount' => round($invoice->total_amount, 2),
                            'total_paid' => round($invoice->paid_amount, 2),
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
                    $balance = $invoice->balance_amount ?? ($invoice->total_amount - $invoice->paid_amount);
                    $dueDate = $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date) : null;
                    $daysOverdue = $dueDate && $dueDate < now() ? abs($dueDate->diffInDays(now(), false)) : 0;
                    
                    $paymentStatus = 'Unpaid';
                    if ($invoice->paid_amount > 0 && $balance > 0) {
                        $paymentStatus = 'Partial';
                    } elseif ($balance <= 0) {
                        $paymentStatus = 'Paid';
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

        } catch (\Exception $e) {
            \Log::error('Error getting outstanding debts via API', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get outstanding debts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process payment for patient charges
     * Matches Web CashierController::processPayment()
     */
    public function processPaymentForCharges(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'charges' => 'required|array|min:1',
                'charges.*.id' => 'required',
                'charges.*.type' => 'required|in:appointment,consultation,lab_test,prescription,radiology',
                'total_amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|' . \App\Enums\PaymentMethod::validationRule(true),
                'momo_phone' => 'nullable|string',
                'momo_network' => 'nullable|in:MTN,Vodafone,AirtelTigo',
                'momo_reference' => 'nullable|string',
                'amount_tendered' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:500'
            ]);

            $user = Auth::user();
            $branchId = $this->resolveUserBranchId('process_payments');

            if (!$branchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not assigned to any branch'
                ], 403);
            }

            $patient = Patient::findOrFail($request->patient_id);

            $this->paymentPolicyService->validateChargePaymentAmount(
                null,
                (float) $request->total_amount,
                (float) $request->total_amount,
                (int) $patient->id
            );

            DB::beginTransaction();

            try {
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
                    'created_by' => $user->id,
                    'items' => $this->prepareInvoiceItems($request->charges)
                ]);

                // Process each charge
                foreach ($request->charges as $charge) {
                    $this->processCharge($charge, $invoice->id, $branchId);
                }

                // Use PaymentService to record payment
                $paymentResult = $this->paymentService->recordPayment(
                    $invoice->id,
                    $request->total_amount,
                    $request->payment_method,
                    \App\Support\PaymentMetadata::fromRequest($request, [
                        'notes' => $request->notes,
                        'processed_by' => $user->id,
                        'reference_number' => $this->generatePaymentReference(),
                        'source_platform' => 'mobile',
                        'ip_address' => $request->ip(),
                    ])
                );

                if (!$paymentResult['success']) {
                    throw new \Exception($paymentResult['message']);
                }

                $payment = $paymentResult['payment'];

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'data' => [
                        'invoice' => [
                            'id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'total_amount' => round($invoice->total_amount, 2),
                            'paid_amount' => round($invoice->paid_amount, 2),
                            'balance_amount' => round($invoice->balance_amount, 2),
                            'status' => $invoice->status,
                            'payment_status' => $invoice->payment_status
                        ],
                        'payment' => [
                            'id' => $payment->id,
                            'amount' => round($payment->amount, 2),
                            'payment_method' => $payment->payment_method,
                            'reference_number' => $payment->reference_number,
                            'status' => $payment->status
                        ]
                    ]
                ], 201);

            } catch (\InvalidArgumentException $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'payment_policy_violation' => true,
                ], 422);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error processing payment via API', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate invoice number
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $lastInvoice = Invoice::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastInvoice ? (intval(substr($lastInvoice->invoice_number, -4)) + 1) : 1;
        
        return $prefix . '-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate payment reference number
     */
    private function generatePaymentReference(): string
    {
        return 'PAY-' . strtoupper(uniqid());
    }

    /**
     * Prepare invoice items from charges
     */
    private function prepareInvoiceItems(array $charges): array
    {
        return collect($charges)->map(function ($charge) {
            return [
                'id' => $charge['line_id'] ?? null,
                'type' => $charge['type'],
                'description' => $charge['description'] ?? 'Service',
                'quantity' => 1,
                'unit_price' => $charge['amount'],
                'total' => $charge['amount'],
                'charge_component' => $charge['charge_component'] ?? null,
                'parent_id' => $charge['id'] ?? null,
                'service_type' => $charge['type'],
            ];
        })->toArray();
    }

    /**
     * Process individual charge and update billing status
     */
    private function processCharge(array $charge, int $invoiceId, int $branchId): void
    {
        $model = null;
        
        switch ($charge['type']) {
            case 'consultation':
                $model = \App\Models\Consultation::find($charge['id']);
                break;
            case 'lab_test':
                $model = \App\Models\LabRequest::find($charge['id']);
                break;
            case 'prescription':
                $model = \App\Models\Prescription::find($charge['id']);
                break;
            case 'radiology':
                $model = \App\Models\RadiologyRequest::find($charge['id']);
                break;
            case 'appointment':
                $model = \App\Models\Appointment::find($charge['id']);
                break;
        }
        
        if ($model instanceof \Illuminate\Database\Eloquent\Model) {
            $model->update([
                'billing_status' => 'billed',
                'invoice_id' => $invoiceId
            ]);
        }
    }

    /**
     * Get patient's debt information
     * Matches Web CashierController::getPatientDebtInfo()
     */
    public function getPatientDebtInfo(Request $request, $patientId): JsonResponse
    {
        try {
            $user = Auth::user();
            $branchId = $this->resolveUserBranchId('process_payments');

            if (!$branchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not assigned to any branch'
                ], 403);
            }

            $patient = Patient::findOrFail($patientId);

            // Get or create debtor record
            $debtor = $this->debtorService->createOrUpdateDebtor($patient->id, $branchId, $user->id);
            
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
                    'total_outstanding' => round($debtor->total_outstanding, 2),
                    'total_paid' => round($debtor->total_paid, 2),
                    'total_invoiced' => round($debtor->total_invoiced, 2),
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
                        'total_amount' => round($invoice->total_amount, 2),
                        'balance_amount' => round($invoice->balance_amount, 2),
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
                        'total_amount' => round($invoice->total_amount, 2),
                        'balance_amount' => round($invoice->balance_amount, 2),
                        'days_overdue' => $invoice->due_date ? now()->diffInDays($invoice->due_date) : 0
                    ];
                }),
                'recent_payments' => $recentPayments->map(function($payment) {
                    return [
                        'id' => $payment->id,
                        'payment_amount' => round($payment->payment_amount, 2),
                        'payment_date' => $payment->payment_date,
                        'payment_method' => $payment->payment_method,
                        'reference_number' => $payment->reference_number,
                        'invoice_number' => $payment->invoice->invoice_number ?? null,
                        'processor_name' => $payment->processor ? ($payment->processor->first_name . ' ' . $payment->processor->last_name) : 'N/A'
                    ];
                })
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error getting patient debt info via API', [
                'patient_id' => $patientId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get patient debt information: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all pending payments for the branch
     * Matches Web CashierController::getAllPendingPayments()
     */
    public function getAllPendingPayments(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $branchId = $this->resolveUserBranchId('process_payments');

            if (!$branchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not assigned to any branch'
                ], 403);
            }

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
                        'total_amount' => round(collect($charges)->sum('amount'), 2),
                        'charges_count' => count($charges)
                    ];
                }
            }

            // Sort by total amount descending
            $patientsWithCharges = collect($patientsWithCharges)->sortByDesc('total_amount')->values();

            return response()->json([
                'success' => true,
                'patients_with_charges' => $patientsWithCharges,
                'total_pending_amount' => round(collect($pendingCharges)->sum('amount'), 2),
                'total_patients' => count($patientsWithCharges)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting all pending payments via API', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get pending payments: ' . $e->getMessage()
            ], 500);
        }
    }
}
