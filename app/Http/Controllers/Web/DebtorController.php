<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\Debtor;
use App\Models\Patient;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\DebtorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DebtorController extends Controller
{
    use ExportsListData, ResolvesUserBranch;

    protected $debtorService;

    public function __construct(DebtorService $debtorService)
    {
        $this->debtorService = $debtorService;
    }

    /**
     * Display a listing of debtors with comprehensive analytics.
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'status', 'branch_id', 'min_amount', 'max_amount', 
            'min_days_overdue', 'max_days_overdue', 'search'
        ]);

        if (empty($filters['branch_id']) && !auth()->user()->hasRole('super_admin')) {
            $filters['branch_id'] = $this->resolveUserBranchId(['view_debtors', 'manage_debtors']);
        }

        $debtors = $this->debtorService->getDebtors($filters)->paginate(20);
        $statistics = $this->debtorService->getDebtorStatistics($filters['branch_id'] ?? null);
        
        // Get aging analysis
        $agingAnalysis = $this->getAgingAnalysis($filters['branch_id'] ?? null);
        
        // Get collection trends (last 6 months)
        $collectionTrends = $this->getCollectionTrends($filters['branch_id'] ?? null);
        
        // Get top debtors
        $topDebtors = $this->getTopDebtors($filters['branch_id'] ?? null, 10);
        
        $branches = Branch::where('is_active', true)->get();

        return view('debtors.index', compact(
            'debtors', 
            'statistics', 
            'agingAnalysis',
            'collectionTrends',
            'topDebtors',
            'branches', 
            'filters'
        ));
    }

    /**
     * Show the form for creating a new debtor.
     */
    public function create()
    {
        $patients = Patient::latest()->get();
        $branches = Branch::where('is_active', true)->get();
        
        return view('debtors.create', compact('patients', 'branches'));
    }

    /**
     * Store a newly created debtor.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string'
        ]);

        try {
            $debtor = $this->debtorService->createOrUpdateDebtor(
                $validated['patient_id'],
                $validated['branch_id'],
                auth()->id()
            );

            if ($request->has('notes')) {
                $debtor->update(['notes' => $validated['notes']]);
            }

            return redirect()->route('debtors.show', $debtor)
                ->with('success', 'Debtor record created successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create debtor record: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified debtor.
     */
    public function show(Debtor $debtor)
    {
        // Recalculate debtor amounts to ensure accuracy
        $this->debtorService->calculateDebtorAmounts($debtor);
        
        $debtor->load(['patient', 'branch', 'creator', 'paymentHistory.processor']);
        
        // Refresh to get updated calculated values
        $debtor->refresh();
        
        // Get outstanding invoices
        $outstandingInvoices = $debtor->outstandingInvoices()->get();
        $overdueInvoices = $debtor->overdueInvoices()->get();
        
        // Get payment history
        $paymentHistory = $debtor->paymentHistory()
            ->with(['invoice', 'payment', 'processor'])
            ->latest('payment_date')
            ->paginate(10);

        return view('debtors.show', compact('debtor', 'outstandingInvoices', 'overdueInvoices', 'paymentHistory'));
    }

    /**
     * Show the form for editing the specified debtor.
     */
    public function edit(Debtor $debtor)
    {
        $debtor->load(['patient', 'branch']);
        
        return view('debtors.edit', compact('debtor'));
    }

    /**
     * Update the specified debtor.
     */
    public function update(Request $request, Debtor $debtor)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        try {
            $debtor->update($validated);
            
            return redirect()->route('debtors.show', $debtor)
                ->with('success', 'Debtor record updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update debtor record: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified debtor.
     */
    public function destroy(Debtor $debtor)
    {
        try {
            $debtor->delete();
            
            return redirect()->route('debtors.index')
                ->with('success', 'Debtor record deleted successfully!');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete debtor record: ' . $e->getMessage());
        }
    }

    /**
     * Show debtor payment history.
     */
    public function paymentHistory(Debtor $debtor, Request $request)
    {
        $debtor->load(['patient', 'branch']);

        $filters = $request->only(['date_from', 'date_to', 'payment_method']);
        $paymentHistory = $this->debtorService->getDebtorPaymentHistory($debtor->id, $filters)->paginate(20);
        
        return view('debtors.payment-history', compact('debtor', 'paymentHistory', 'filters'));
    }

    /**
     * Show debtor outstanding invoices.
     */
    public function outstandingInvoices(Debtor $debtor)
    {
        $debtor->load(['patient', 'branch']);

        // Recalculate debtor amounts to ensure accuracy
        $this->debtorService->calculateDebtorAmounts($debtor);
        
        // Refresh the debtor model to get updated values
        $debtor->refresh();
        
        $outstandingInvoices = $debtor->outstandingInvoices()->paginate(20);
        $overdueInvoices = $debtor->overdueInvoices()->get();
        
        return view('debtors.outstanding-invoices', compact('debtor', 'outstandingInvoices', 'overdueInvoices'));
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
        $branches = Branch::where('is_active', true)->get();

        return view('debtors.report', compact('report', 'branches', 'filters'));
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
            'reminders' => $reminders,
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
                'message' => 'Debtor status updated successfully',
                'debtor' => $debtor->fresh()
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
     * Get debtor statistics for dashboard.
     */
    public function getStatistics(Request $request)
    {
        $branchId = $request->input('branch_id');
        $statistics = $this->debtorService->getDebtorStatistics($branchId);
        
        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Get aging analysis for outstanding debts.
     */
    private function getAgingAnalysis($branchId = null)
    {
        $query = Invoice::whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));
        
        $today = now();
        
        $aging = [
            'current' => ['amount' => 0, 'count' => 0],      // 0-30 days
            '30_days' => ['amount' => 0, 'count' => 0],      // 31-60 days
            '60_days' => ['amount' => 0, 'count' => 0],      // 61-90 days
            '90_plus_days' => ['amount' => 0, 'count' => 0]  // 90+ days
        ];
        
        $invoices = $query->get();
        
        foreach ($invoices as $invoice) {
            $dueDate = Carbon::parse($invoice->due_date);
            $daysOverdue = $today->diffInDays($dueDate, false);
            $outstandingAmount = $invoice->balance_amount ?? 0;
            
            if ($daysOverdue <= 30 && $daysOverdue >= 0) {
                $aging['current']['amount'] += $outstandingAmount;
                $aging['current']['count']++;
            } elseif ($daysOverdue <= 60) {
                $aging['30_days']['amount'] += $outstandingAmount;
                $aging['30_days']['count']++;
            } elseif ($daysOverdue <= 90) {
                $aging['60_days']['amount'] += $outstandingAmount;
                $aging['60_days']['count']++;
            } else {
                $aging['90_plus_days']['amount'] += $outstandingAmount;
                $aging['90_plus_days']['count']++;
            }
        }
        
        $totalOutstanding = array_sum(array_column($aging, 'amount'));
        
        foreach ($aging as &$category) {
            $category['percentage'] = $totalOutstanding > 0 
                ? round(($category['amount'] / $totalOutstanding) * 100, 2) 
                : 0;
        }
        
        return [
            'categories' => $aging,
            'total_outstanding' => $totalOutstanding,
            'total_invoices' => $invoices->count()
        ];
    }

    /**
     * Get collection trends over time.
     */
    private function getCollectionTrends($branchId = null, $months = 6)
    {
        $trends = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $startDate = $month->copy()->startOfMonth();
            $endDate = $month->copy()->endOfMonth();
            
            $collected = Payment::whereBetween('payment_date', [$startDate, $endDate])
                ->where('status', 'completed')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->sum('amount');
            
            $invoiced = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->sum('total_amount');
            
            $trends[] = [
                'month' => $month->format('M Y'),
                'collected' => round($collected, 2),
                'invoiced' => round($invoiced, 2),
                'collection_rate' => $invoiced > 0 ? round(($collected / $invoiced) * 100, 2) : 0
            ];
        }
        
        return $trends;
    }

    /**
     * Get top debtors by outstanding amount.
     */
    private function getTopDebtors($branchId = null, $limit = 10)
    {
        return Debtor::with(['patient', 'branch'])
            ->where('is_active', true)
            ->where('total_outstanding', '>', 0)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('total_outstanding', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Export debt recovery report.
     */
    public function export(Request $request)
    {
        $filters = $request->only([
            'status', 'branch_id', 'min_amount', 'max_amount', 
            'min_days_overdue', 'max_days_overdue', 'search'
        ]);
        
        $debtors = $this->debtorService->getDebtors($filters)->get();
        
        $filename = 'debt-recovery-report-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        
        $callback = function() use ($debtors) {
            $file = fopen('php://output', 'w');
            
            // Report header
            fputcsv($file, ['Debt Recovery Report']);
            fputcsv($file, ['Generated:', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []);
            
            // Column headers
            fputcsv($file, [
                'Patient Number',
                'Patient Name',
                'Contact',
                'Branch',
                'Total Outstanding (GHS)',
                'Total Invoiced (GHS)',
                'Total Paid (GHS)',
                'Outstanding Invoices',
                'Overdue Invoices',
                'Days Overdue',
                'Debt Status',
                'Last Payment Date'
            ]);
            
            foreach ($debtors as $debtor) {
                fputcsv($file, [
                    $debtor->patient_number_display,
                    $debtor->patient_display_name,
                    $debtor->patient->contact ?? 'N/A',
                    $debtor->branch->name ?? 'N/A',
                    number_format($debtor->total_outstanding, 2),
                    number_format($debtor->total_invoiced, 2),
                    number_format($debtor->total_paid, 2),
                    $debtor->outstanding_invoices_count,
                    $debtor->overdue_invoices_count,
                    $debtor->days_overdue,
                    strtoupper($debtor->debt_status),
                    $debtor->last_payment_date ? $debtor->last_payment_date->format('Y-m-d') : 'Never'
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
