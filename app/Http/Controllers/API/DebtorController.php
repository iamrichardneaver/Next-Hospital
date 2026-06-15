<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Debtor;
use App\Services\DebtorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DebtorController extends Controller
{
    protected $debtorService;

    public function __construct(DebtorService $debtorService)
    {
        $this->debtorService = $debtorService;
    }

    /**
     * Display a listing of debtors.
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'status', 'branch_id', 'min_amount', 'max_amount', 
            'min_days_overdue', 'max_days_overdue', 'search'
        ]);

        $debtors = $this->debtorService->getDebtors($filters)->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $debtors,
            'message' => 'Debtors retrieved successfully'
        ]);
    }

    /**
     * Store a newly created debtor.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
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
            $debtor = $this->debtorService->createOrUpdateDebtor(
                $request->patient_id,
                $request->branch_id,
                auth()->id()
            );

            if ($request->has('notes')) {
                $debtor->update(['notes' => $request->notes]);
            }

            return response()->json([
                'success' => true,
                'data' => $debtor->load(['patient', 'branch', 'creator']),
                'message' => 'Debtor record created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create debtor record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified debtor.
     */
    public function show(Debtor $debtor)
    {
        $debtor->load(['patient', 'branch', 'creator', 'paymentHistory.processor']);
        
        // Get outstanding invoices
        $outstandingInvoices = $debtor->outstandingInvoices()->get();
        $overdueInvoices = $debtor->overdueInvoices()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'debtor' => $debtor,
                'outstanding_invoices' => $outstandingInvoices,
                'overdue_invoices' => $overdueInvoices
            ],
            'message' => 'Debtor retrieved successfully'
        ]);
    }

    /**
     * Update the specified debtor.
     */
    public function update(Request $request, Debtor $debtor)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $debtor->update($request->only(['notes', 'is_active']));
            
            return response()->json([
                'success' => true,
                'data' => $debtor->fresh(['patient', 'branch']),
                'message' => 'Debtor record updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update debtor record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified debtor.
     */
    public function destroy(Debtor $debtor)
    {
        try {
            $debtor->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Debtor record deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete debtor record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get debtor payment history.
     */
    public function paymentHistory(Debtor $debtor, Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'payment_method']);
        $paymentHistory = $this->debtorService->getDebtorPaymentHistory($debtor->id, $filters)->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $paymentHistory,
            'message' => 'Payment history retrieved successfully'
        ]);
    }

    /**
     * Get debtor outstanding invoices.
     */
    public function outstandingInvoices(Debtor $debtor)
    {
        $outstandingInvoices = $debtor->outstandingInvoices()->paginate(20);
        $overdueInvoices = $debtor->overdueInvoices()->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'outstanding_invoices' => $outstandingInvoices,
                'overdue_invoices' => $overdueInvoices
            ],
            'message' => 'Outstanding invoices retrieved successfully'
        ]);
    }

    /**
     * Generate debtor report.
     */
    public function report(Request $request)
    {
        $filters = $request->only([
            'status', 'branch_id', 'min_amount', 'max_amount', 
            'min_days_overdue', 'max_days_overdue', 'search'
        ]);

        $report = $this->debtorService->generateDebtorReport($filters);
        
        return response()->json([
            'success' => true,
            'data' => $report,
            'message' => 'Debtor report generated successfully'
        ]);
    }

    /**
     * Send payment reminders.
     */
    public function sendReminders(Request $request)
    {
        $debtorIds = $request->input('debtor_ids', []);
        $reminders = $this->debtorService->sendPaymentReminders($debtorIds);
        
        return response()->json([
            'success' => true,
            'data' => $reminders,
            'message' => 'Payment reminders generated successfully'
        ]);
    }

    /**
     * Update debtor status.
     */
    public function updateStatus(Debtor $debtor)
    {
        try {
            $this->debtorService->calculateDebtorAmounts($debtor);
            $debtor->updateStatus();
            
            return response()->json([
                'success' => true,
                'data' => $debtor->fresh(),
                'message' => 'Debtor status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update debtor status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update all debtors.
     */
    public function bulkUpdate()
    {
        try {
            $count = $this->debtorService->updateAllDebtors();
            
            return response()->json([
                'success' => true,
                'data' => ['updated_count' => $count],
                'message' => "Updated {$count} debtor records successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update debtors: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get debtor statistics.
     */
    public function getStatistics(Request $request)
    {
        $branchId = $request->input('branch_id');
        $statistics = $this->debtorService->getDebtorStatistics($branchId);
        
        return response()->json([
            'success' => true,
            'data' => $statistics,
            'message' => 'Debtor statistics retrieved successfully'
        ]);
    }

    /**
     * Record payment for debtor.
     */
    public function recordPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|exists:payments,id',
            'invoice_id' => 'required|exists:invoices,id',
            'debtor_id' => 'nullable|exists:debtors,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $debtor = $this->debtorService->recordPayment(
                $request->payment_id,
                $request->invoice_id,
                $request->debtor_id
            );
            
            return response()->json([
                'success' => true,
                'data' => $debtor->fresh(['patient', 'branch']),
                'message' => 'Payment recorded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment: ' . $e->getMessage()
            ], 500);
        }
    }
}
