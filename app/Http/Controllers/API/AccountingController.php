<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsAccountingPdfs;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AccountingExportService;
use App\Services\AccountingReportService;
use App\Services\DebtorService;
use App\Services\InventoryAccountingService;
use App\Services\RevenueReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    use ResolvesUserBranch, ExportsAccountingPdfs;

    public function __construct(
        protected AccountingReportService $reportService,
        protected RevenueReportService $revenueReportService,
        protected InventoryAccountingService $inventoryAccountingService,
        protected AccountingExportService $exportService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $filters = $this->reportService->resolveFilters($request, $this->resolveBranchId($request));
        extract($filters);

        $debtStatistics = app(DebtorService::class)->getDebtorStatistics($branchId);
        $kpis = $this->reportService->getDashboardKpis($branchId, $startDate, $endDate);
        $kpis['pending_invoices'] = Invoice::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
            ->count();
        $kpis['outstanding_debt'] = $debtStatistics['total_outstanding'] ?? 0;
        $kpis['total_debtors'] = $debtStatistics['total_debtors'] ?? 0;
        $kpis['pending_expenses'] = $this->reportService->getPendingExpenseCount($branchId);

        $paymentQuery = Payment::query()
            ->where('status', 'completed')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $paymentsByMethod = (clone $paymentQuery)
            ->whereDate('payment_date', now()->toDateString())
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        $recentPayments = Payment::with(['patient:id,first_name,last_name,patient_number', 'invoice:id,invoice_number'])
            ->where('status', 'completed')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('payment_date')
            ->limit(10)
            ->get();

        $recentInvoices = Invoice::with('patient:id,first_name,last_name,patient_number')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => $kpis,
                'payments_by_method' => $paymentsByMethod,
                'recent_payments' => $recentPayments,
                'recent_invoices' => $recentInvoices,
                'revenue_streams' => $this->reportService->getRevenueByServiceType($branchId, $startDate, $endDate),
                'expenses_by_department' => $this->reportService->getExpensesByDepartment($branchId, $startDate, $endDate),
                'inventory_purchases' => $this->inventoryAccountingService->getInventoryPurchaseTotals($branchId, $startDate, $endDate),
                'filters' => compact('branchId', 'startDate', 'endDate'),
            ],
        ]);
    }

    public function revenue(Request $request)
    {
        $filters = $this->reportService->resolveFilters($request, $this->resolveBranchId($request));
        extract($filters);

        $revenueByService = $this->revenueReportService->getRevenueComposition($branchId, $startDate, $endDate);
        $revenueByMethod = $this->reportService->getRevenueByPaymentMethod($branchId, $startDate, $endDate);
        $totalRevenue = $this->reportService->getTotalRevenue($branchId, $startDate, $endDate);

        $export = $this->exportService->resolveExport($request->get('export'));
        if ($export === 'csv') {
            return $this->exportRevenueCsv($revenueByService, $revenueByMethod, $totalRevenue, $startDate, $endDate);
        }
        if ($export === 'pdf') {
            return $this->exportRevenuePdf($this->exportService, $revenueByService, $revenueByMethod, $totalRevenue, $branchId, $startDate, $endDate);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'revenue_by_service' => $revenueByService,
                'revenue_by_method' => $revenueByMethod,
                'total_revenue' => round($totalRevenue, 2),
                'filters' => compact('branchId', 'startDate', 'endDate'),
            ],
        ]);
    }

    public function revenueDrillDown(Request $request, string $serviceType)
    {
        $user = $request->user();
        $this->assertUserCanViewServiceType($user, $serviceType);

        $filters = $this->reportService->resolveFilters($request, $this->resolveBranchId($request));
        extract($filters);

        $doctorId = $this->revenueReportService->userNeedsDoctorScope($user) ? $user->id : null;
        $rows = $this->revenueReportService->getRevenueDrillDown(
            $serviceType,
            $branchId,
            $startDate,
            $endDate,
            $doctorId
        );

        $serviceLabel = AccountingReportService::SERVICE_TYPE_LABELS[$serviceType]
            ?? ucfirst(str_replace('_', ' ', $serviceType));
        $streamTotal = $rows->sum('amount');

        $export = $this->exportService->resolveExport($request->get('export'));
        if ($export === 'csv') {
            $csvRows = $rows->map(fn ($row) => [
                $row['transaction_reference'],
                $row['transaction_date'],
                $row['patient_name'],
                $row['patient_number'] ?? '',
                $row['invoice_number'] ?? ($row['invoice_id'] ? '#' . $row['invoice_id'] : ''),
                number_format($row['amount'], 2),
                $row['payment_method'] ?? '',
                $row['branch_name'] ?? '',
            ])->all();

            return $this->exportService->streamCsv(
                ['Reference', 'Date', 'Patient', 'Patient Number', 'Invoice', 'Amount (GHS)', 'Payment Method', 'Branch'],
                $csvRows,
                'revenue-' . strtolower(preg_replace('/[^a-z0-9]+/i', '-', $serviceLabel)) . "-{$startDate}-to-{$endDate}"
            );
        }
        if ($export === 'pdf') {
            return $this->exportRevenueDrillDownPdf($this->exportService, $rows, $serviceLabel, $serviceType, $streamTotal, $branchId, $startDate, $endDate);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'service_type' => $serviceType,
                'service_label' => $serviceLabel,
                'stream_total' => round($streamTotal, 2),
                'rows' => $rows,
                'filters' => compact('branchId', 'startDate', 'endDate'),
            ],
        ]);
    }

    public function revenueVsExpenses(Request $request)
    {
        $filters = $this->reportService->resolveFilters($request, $this->resolveBranchId($request));
        extract($filters);

        $comparison = $this->reportService->getRevenueVsExpenses($branchId, $startDate, $endDate, $period);
        $expensesByCategory = $this->reportService->getExpensesByCategory($branchId, $startDate, $endDate);

        $export = $this->exportService->resolveExport($request->get('export'));
        if ($export === 'csv') {
            $rows = [];
            foreach ($comparison['periods'] as $row) {
                $rows[] = ['Period Comparison', $row['label'], number_format($row['revenue'], 2), number_format($row['expenses'], 2), number_format($row['net'], 2)];
            }
            $rows[] = ['Period Comparison', 'Total', number_format($comparison['totals']['revenue'], 2), number_format($comparison['totals']['expenses'], 2), number_format($comparison['totals']['net'], 2)];
            foreach ($expensesByCategory as $cat) {
                $rows[] = ['Expenses by Category', $cat['category'], '', number_format($cat['total'], 2), number_format($cat['percentage'], 1) . '%'];
            }

            return $this->exportService->streamCsv(
                ['Section', 'Period / Category', 'Revenue (GHS)', 'Expenses (GHS)', 'Net / Share'],
                $rows,
                "revenue-vs-expenses-{$startDate}-to-{$endDate}"
            );
        }
        if ($export === 'pdf') {
            return $this->exportRevenueVsExpensesPdf($this->exportService, $comparison, $expensesByCategory, $branchId, $startDate, $endDate, $period);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'comparison' => $comparison,
                'expenses_by_category' => $expensesByCategory,
                'filters' => compact('branchId', 'startDate', 'endDate', 'period'),
            ],
        ]);
    }

    public function balanceSheet(Request $request)
    {
        $filters = $this->reportService->resolveFilters($request, $this->resolveBranchId($request));
        extract($filters);

        $balanceSheet = $this->reportService->getBalanceSheet($branchId, $asOfDate);

        $export = $this->exportService->resolveExport($request->get('export'));
        if ($export === 'csv') {
            $rows = [
                ['Assets', 'Cash & Cash Equivalents', number_format($balanceSheet['assets']['cash'], 2)],
                ['Assets', 'Accounts Receivable', number_format($balanceSheet['assets']['accounts_receivable'], 2)],
                ['Assets', 'Total Assets', number_format($balanceSheet['assets']['total_assets'], 2)],
                ['Liabilities', 'Accounts Payable', number_format($balanceSheet['liabilities']['accounts_payable'], 2)],
                ['Liabilities', 'Total Liabilities', number_format($balanceSheet['liabilities']['total_liabilities'], 2)],
                ['Equity', 'Retained Earnings', number_format($balanceSheet['equity']['retained_earnings'], 2)],
                ['Equity', 'Total Equity', number_format($balanceSheet['equity']['total_equity'], 2)],
                ['Totals', 'Total Liabilities & Equity', number_format($balanceSheet['totals']['total_liabilities_equity'], 2)],
            ];

            return $this->exportService->streamCsv(['Section', 'Line Item', 'Amount (GHS)'], $rows, "balance-sheet-{$asOfDate}");
        }
        if ($export === 'pdf') {
            return $this->exportBalanceSheetPdf($this->exportService, $balanceSheet, $branchId, $asOfDate);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'balance_sheet' => $balanceSheet,
                'filters' => compact('branchId', 'asOfDate'),
            ],
        ]);
    }

    public function cashFlow(Request $request)
    {
        $filters = $this->reportService->resolveFilters($request, $this->resolveBranchId($request));
        extract($filters);

        $cashFlow = $this->reportService->getCashFlow($branchId, $startDate, $endDate);

        $export = $this->exportService->resolveExport($request->get('export'));
        if ($export === 'csv') {
            $rows = [
                ['Summary', 'Operating Inflows', number_format($cashFlow['operating']['inflows'], 2)],
                ['Summary', 'Operating Outflows', number_format($cashFlow['operating']['outflows'], 2)],
                ['Summary', 'Refunds / Cancellations', number_format($cashFlow['operating']['refunds'], 2)],
                ['Summary', 'Net Operating', number_format($cashFlow['operating']['net'], 2)],
                ['Summary', 'Net Change in Cash', number_format($cashFlow['net_change_in_cash'], 2)],
            ];
            foreach ($cashFlow['daily_flows'] as $day) {
                $rows[] = ['Daily', $day['date'], number_format($day['inflow'], 2), number_format($day['outflow'], 2), number_format($day['net'], 2)];
            }

            return $this->exportService->streamCsv(
                ['Section', 'Date / Line Item', 'Inflow (GHS)', 'Outflow (GHS)', 'Net (GHS)'],
                $rows,
                "cash-flow-{$startDate}-to-{$endDate}"
            );
        }
        if ($export === 'pdf') {
            return $this->exportCashFlowPdf($this->exportService, $cashFlow, $branchId, $startDate, $endDate);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'cash_flow' => $cashFlow,
                'filters' => compact('branchId', 'startDate', 'endDate'),
            ],
        ]);
    }

    protected function exportRevenueCsv(array $revenueByService, array $revenueByMethod, float $totalRevenue, string $startDate, string $endDate)
    {
        $rows = [];
        foreach ($revenueByService as $row) {
            $rows[] = ['Service Module', $row['label'], (string) $row['count'], number_format($row['total'], 2), number_format($row['percentage'], 1) . '%'];
        }
        $rows[] = ['', '', '', '', ''];
        $rows[] = ['Payment Method', 'Label', 'Count', 'Amount (GHS)', 'Share'];
        foreach ($revenueByMethod as $row) {
            $rows[] = ['Payment Method', $row['label'], (string) $row['count'], number_format($row['total'], 2), number_format($row['percentage'], 1) . '%'];
        }
        $rows[] = ['', '', '', number_format($totalRevenue, 2), '100%'];

        return $this->exportService->streamCsv(
            ['Section', 'Name', 'Transactions', 'Amount (GHS)', 'Share'],
            $rows,
            "revenue-streams-{$startDate}-to-{$endDate}"
        );
    }

    protected function resolveBranchId(Request $request): ?int
    {
        if ($request->user()->hasRole('super_admin')) {
            return $request->filled('branch_id') ? (int) $request->branch_id : null;
        }

        return $this->resolveUserBranchId([
            'view_financial_dashboard',
            'view_financial_reports',
            'view_revenue_analytics',
            'view_revenue_reports',
            'view_balance_sheet',
            'view_cash_flow',
            'view_expenses',
        ]);
    }

    protected function assertUserCanViewServiceType($user, string $serviceType): void
    {
        if ($this->revenueReportService->userHasFullRevenueAccess($user)) {
            return;
        }

        $allowed = $this->revenueReportService->resolveUserRevenueServiceTypes($user);
        if (!in_array($serviceType, $allowed, true)) {
            abort(403, 'You do not have access to this revenue stream.');
        }
    }
}
