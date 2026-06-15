<?php

namespace App\Services;

use App\Models\Debtor;
use App\Models\DebtorPaymentHistory;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Patient;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DebtorService
{
    /**
     * Create or update debtor record for a patient.
     */
    public function createOrUpdateDebtor($patientId, $branchId, $createdBy = null)
    {
        $debtor = Debtor::where('patient_id', $patientId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$debtor) {
            $debtor = Debtor::create([
                'patient_id' => $patientId,
                'branch_id' => $branchId,
                'created_by' => $createdBy ?? auth()->id(),
                'is_active' => true
            ]);
        }

        // Calculate outstanding amounts
        $this->calculateDebtorAmounts($debtor);
        
        // Update status
        $debtor->updateStatus();
        
        return $debtor;
    }

    /**
     * Calculate outstanding amounts for a debtor.
     */
    public function calculateDebtorAmounts(Debtor $debtor)
    {
        // Get all invoices for this patient and branch
        $invoices = Invoice::where('patient_id', $debtor->patient_id)
            ->where('branch_id', $debtor->branch_id)
            ->get();

        // Calculate totals using new paid_amount field from invoices
        $debtor->total_invoiced = $invoices->sum('total_amount');
        $debtor->total_paid = $invoices->sum('paid_amount');
        $debtor->total_outstanding = $invoices->sum('balance_amount');

        // Get outstanding invoices (pending, partial, overdue, or unpaid)
        $outstandingInvoices = $invoices->filter(function($invoice) {
            return in_array($invoice->payment_status, ['unpaid', 'partial', 'overdue']) ||
                   in_array($invoice->status, ['pending', 'partial', 'overdue']);
        });

        $debtor->outstanding_invoices_count = $outstandingInvoices->count();

        // Get overdue invoices
        $overdueInvoices = $outstandingInvoices->filter(function($invoice) {
            return $invoice->isOverdue() || $invoice->payment_status === 'overdue';
        });

        $debtor->overdue_invoices_count = $overdueInvoices->count();

        // Calculate days overdue (positive number of days past due)
        $oldestOverdue = $overdueInvoices->min('due_date');
        $debtor->days_overdue = $oldestOverdue ? abs(now()->diffInDays($oldestOverdue, false)) : 0;

        // Set first outstanding date
        if ($outstandingInvoices->isNotEmpty() && !$debtor->first_outstanding_date) {
            $debtor->first_outstanding_date = $outstandingInvoices->min('invoice_date');
        }

        // Set last invoice date
        $debtor->last_invoice_date = $invoices->max('invoice_date');

        // Set last payment date
        $lastPayment = Payment::where('patient_id', $debtor->patient_id)
            ->where('branch_id', $debtor->branch_id)
            ->where('status', 'completed')
            ->latest('payment_date')
            ->first();
        
        $debtor->last_payment_date = $lastPayment ? $lastPayment->payment_date : null;

        // Set largest outstanding amount (using balance_amount)
        $debtor->largest_outstanding_amount = $outstandingInvoices->max('balance_amount') ?? 0;

        $debtor->save();
    }

    /**
     * Record payment in debtor history.
     */
    public function recordPayment($paymentId, $invoiceId, $debtorId = null)
    {
        $payment = Payment::with('invoice')->findOrFail($paymentId);
        $invoice = $payment->invoice;
        
        if (!$debtorId) {
            $debtor = Debtor::where('patient_id', $invoice->patient_id)
                ->where('branch_id', $invoice->branch_id)
                ->first();
            
            if (!$debtor) {
                $debtor = $this->createOrUpdateDebtor($invoice->patient_id, $invoice->branch_id);
            }
        } else {
            $debtor = Debtor::findOrFail($debtorId);
        }

        // Calculate remaining balance after this payment (use balance_amount field)
        $remainingBalance = $invoice->balance_amount ?? ($invoice->total_amount - $invoice->paid_amount);

        // Create payment history record
        DebtorPaymentHistory::create([
            'debtor_id' => $debtor->id,
            'invoice_id' => $invoiceId,
            'payment_id' => $paymentId,
            'payment_amount' => $payment->amount,
            'remaining_balance' => $remainingBalance,
            'payment_date' => $payment->payment_date,
            'payment_method' => $payment->payment_method,
            'reference_number' => $payment->reference_number,
            'notes' => $payment->notes,
            'processed_by' => $payment->processed_by
        ]);

        // Update debtor amounts
        $this->calculateDebtorAmounts($debtor);
        $debtor->updateStatus();

        return $debtor;
    }

    /**
     * Get debtors with filters.
     */
    public function getDebtors($filters = [])
    {
        $query = Debtor::with(['patient', 'branch', 'creator'])
            ->active()
            ->orderBy('total_outstanding', 'desc');

        // Apply filters
        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['branch_id'])) {
            $query->byBranch($filters['branch_id']);
        }

        if (isset($filters['min_amount'])) {
            $query->where('total_outstanding', '>=', $filters['min_amount']);
        }

        if (isset($filters['max_amount'])) {
            $query->where('total_outstanding', '<=', $filters['max_amount']);
        }

        if (isset($filters['min_days_overdue'])) {
            $query->where('days_overdue', '>=', $filters['min_days_overdue']);
        }

        if (isset($filters['max_days_overdue'])) {
            $query->where('days_overdue', '<=', $filters['max_days_overdue']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('patient', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('patient_number', 'like', "%{$search}%")
                  ->orWhere('contact', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Get debtor statistics.
     */
    public function getDebtorStatistics($branchId = null)
    {
        $query = Debtor::active();
        
        if ($branchId) {
            $query->byBranch($branchId);
        }

        $totalDebtors = $query->count();
        $totalOutstanding = $query->sum('total_outstanding');
        $totalPaid = $query->sum('total_paid');
        $totalInvoiced = $query->sum('total_invoiced');

        $currentDebtors = $query->clone()->byStatus('current')->count();
        $overdueDebtors = $query->clone()->byStatus('overdue')->count();
        $criticalDebtors = $query->clone()->byStatus('critical')->count();
        $resolvedDebtors = $query->clone()->byStatus('resolved')->count();

        $averageOutstanding = $totalDebtors > 0 ? $totalOutstanding / $totalDebtors : 0;
        $collectionRate = $totalInvoiced > 0 ? ($totalPaid / $totalInvoiced) * 100 : 0;

        return [
            'total_debtors' => $totalDebtors,
            'total_outstanding' => $totalOutstanding,
            'total_paid' => $totalPaid,
            'total_invoiced' => $totalInvoiced,
            'current_debtors' => $currentDebtors,
            'overdue_debtors' => $overdueDebtors,
            'critical_debtors' => $criticalDebtors,
            'resolved_debtors' => $resolvedDebtors,
            'average_outstanding' => $averageOutstanding,
            'collection_rate' => round($collectionRate, 2)
        ];
    }

    /**
     * Get debtor payment history.
     */
    public function getDebtorPaymentHistory($debtorId, $filters = [])
    {
        $query = DebtorPaymentHistory::with(['invoice', 'payment', 'processor'])
            ->where('debtor_id', $debtorId)
            ->orderBy('payment_date', 'desc');

        if (isset($filters['date_from'])) {
            $query->whereDate('payment_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('payment_date', '<=', $filters['date_to']);
        }

        if (isset($filters['payment_method'])) {
            $query->byPaymentMethod($filters['payment_method']);
        }

        return $query;
    }

    /**
     * Generate debtor report.
     */
    public function generateDebtorReport($filters = [])
    {
        $debtors = $this->getDebtors($filters)->get();
        
        $report = [
            'summary' => $this->getDebtorStatistics($filters['branch_id'] ?? null),
            'debtors' => $debtors->map(function($debtor) {
                return [
                    'id' => $debtor->id,
                    'patient_name' => $debtor->patient_display_name,
                    'patient_number' => $debtor->patient_number_display,
                    'contact' => $debtor->patient->contact ?? 'N/A',
                    'branch_name' => $debtor->branch->name ?? 'N/A',
                    'total_outstanding' => $debtor->total_outstanding,
                    'total_paid' => $debtor->total_paid,
                    'debt_status' => $debtor->debt_status,
                    'days_overdue' => $debtor->days_overdue,
                    'outstanding_invoices_count' => $debtor->outstanding_invoices_count,
                    'last_payment_date' => $debtor->last_payment_date,
                    'last_invoice_date' => $debtor->last_invoice_date
                ];
            })
        ];

        return $report;
    }

    /**
     * Send payment reminders.
     */
    public function sendPaymentReminders($debtorIds = [])
    {
        $query = Debtor::active()->overdue();
        
        if (!empty($debtorIds)) {
            $query->whereIn('id', $debtorIds);
        }

        $debtors = $query->with(['patient', 'outstandingInvoices'])->get();
        
        $reminders = [];
        foreach ($debtors as $debtor) {
            $reminders[] = [
                'debtor_id' => $debtor->id,
                'patient_name' => $debtor->patient_display_name,
                'outstanding_amount' => $debtor->total_outstanding,
                'days_overdue' => $debtor->days_overdue,
                'outstanding_invoices' => $debtor->outstandingInvoices()->get()->map(function($invoice) {
                    return [
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $invoice->total_amount,
                        'due_date' => $invoice->due_date,
                        'days_overdue' => now()->diffInDays($invoice->due_date)
                    ];
                })
            ];
        }

        return $reminders;
    }

    /**
     * Update all debtors (for batch processing).
     */
    public function updateAllDebtors()
    {
        $debtors = Debtor::active()->get();
        
        foreach ($debtors as $debtor) {
            $this->calculateDebtorAmounts($debtor);
            $debtor->updateStatus();
        }

        return $debtors->count();
    }
}
