<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AccountingExportService;
use App\Services\AccountingReportService;
use App\Services\DebtorService;
use App\Services\InventoryAccountingService;
use App\Services\RevenueReportService;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    use ResolvesUserBranch;

    public function __construct(
        protected AccountingReportService $reportService,
        protected RevenueReportService $revenueReportService,
        protected InventoryAccountingService $inventoryAccountingService,
        protected AccountingExportService $exportService
    ) {}

    public function index(Request $request)
    {
        $filters = $this->reportService->resolveFilters(
            $request,
            $this->resolveBranchId($request)
        );
        extract($filters);

        $debtorService = new DebtorService();
        $debtStatistics = $debtorService->getDebtorStatistics($branchId);

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

        $recentPayments = Payment::with(['patient', 'invoice'])
            ->where('status', 'completed')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('payment_date')
            ->limit(10)
            ->get();

        $recentInvoices = Invoice::with('patient')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->limit(10)
            ->get();

        $revenueStreams = $this->reportService->getRevenueByServiceType($branchId, $startDate, $endDate);
        $expensesByDepartment = $this->reportService->getExpensesByDepartment($branchId, $startDate, $endDate);
        $inventoryPurchases = $this->inventoryAccountingService->getInventoryPurchaseTotals($branchId, $startDate, $endDate);
        $branches = $this->getBranches();

        return view('accounting.index', compact(
            'kpis',
            'paymentsByMethod',
            'recentPayments',
            'recentInvoices',
            'revenueStreams',
            'expensesByDepartment',
            'inventoryPurchases',
            'branches',
            'branchId',
            'startDate',
            'endDate'
        ));
    }

    public function revenue(Request $request)
    {
        $filters = $this->reportService->resolveFilters(
            $request,
            $this->resolveBranchId($request)
        );
        extract($filters);

        $revenueByService = $this->revenueReportService->getRevenueComposition($branchId, $startDate, $endDate);
        $revenueByMethod = $this->reportService->getRevenueByPaymentMethod($branchId, $startDate, $endDate);
        $totalRevenue = $this->reportService->getTotalRevenue($branchId, $startDate, $endDate);
        $branches = $this->getBranches();

        $export = $this->exportService->resolveExport($request->get('export'));
        if ($export === 'csv') {
            return $this->exportRevenueCsv($revenueByService, $revenueByMethod, $totalRevenue, $startDate, $endDate);
        }
        if ($export === 'pdf') {
            return $this->exportRevenuePdf($revenueByService, $revenueByMethod, $totalRevenue, $branchId, $startDate, $endDate);
        }

        return view('accounting.revenue', compact(
            'revenueByService',
            'revenueByMethod',
            'totalRevenue',
            'branches',
            'branchId',
            'startDate',
            'endDate'
        ));
    }

    public function revenueDrillDown(Request $request, string $serviceType)
    {
        $filters = $this->reportService->resolveFilters(
            $request,
            $this->resolveBranchId($request)
        );
        extract($filters);

        $rows = $this->revenueReportService->getRevenueDrillDown(
            $serviceType,
            $branchId,
            $startDate,
            $endDate
        );

        $serviceLabel = AccountingReportService::SERVICE_TYPE_LABELS[$serviceType]
            ?? ucfirst(str_replace('_', ' ', $serviceType));
        $streamTotal = $rows->sum('amount');
        $branches = $this->getBranches();

        $export = $this->exportService->resolveExport($request->get('export'));
        if ($export === 'csv') {
            return $this->exportRevenueDrillDownCsv($rows, $serviceLabel, $startDate, $endDate);
        }
        if ($export === 'pdf') {
            return $this->exportRevenueDrillDownPdf($rows, $serviceLabel, $serviceType, $streamTotal, $branchId, $startDate, $endDate);
        }

        return view('accounting.revenue-drill-down', compact(
            'rows',
            'serviceType',
            'serviceLabel',
            'streamTotal',
            'branches',
            'branchId',
            'startDate',
            'endDate'
        ));
    }

    public function balanceSheet(Request $request)
    {
        $filters = $this->reportService->resolveFilters(
            $request,
            $this->resolveBranchId($request)
        );
        extract($filters);

        $balanceSheet = $this->reportService->getBalanceSheet($branchId, $asOfDate);
        $branches = $this->getBranches();

        $export = $this->exportService->resolveExport($request->get('export'));
        if ($export === 'csv') {
            return $this->exportBalanceSheetCsv($balanceSheet, $asOfDate);
        }
        if ($export === 'pdf') {
            return $this->exportBalanceSheetPdf($balanceSheet, $branchId, $asOfDate);
        }

        return view('accounting.balance-sheet', compact(
            'balanceSheet',
            'branches',
            'branchId',
            'asOfDate'
        ));
    }

    public function cashFlow(Request $request)
    {
        $filters = $this->reportService->resolveFilters(
            $request,
            $this->resolveBranchId($request)
        );
        extract($filters);

        $cashFlow = $this->reportService->getCashFlow($branchId, $startDate, $endDate);
        $branches = $this->getBranches();

        $export = $this->exportService->resolveExport($request->get('export'));
        if ($export === 'csv') {
            return $this->exportCashFlowCsv($cashFlow, $startDate, $endDate);
        }
        if ($export === 'pdf') {
            return $this->exportCashFlowPdf($cashFlow, $branchId, $startDate, $endDate);
        }

        return view('accounting.cash-flow', compact(
            'cashFlow',
            'branches',
            'branchId',
            'startDate',
            'endDate'
        ));
    }

    public function revenueVsExpenses(Request $request)
    {
        $filters = $this->reportService->resolveFilters(
            $request,
            $this->resolveBranchId($request)
        );
        extract($filters);

        $comparison = $this->reportService->getRevenueVsExpenses($branchId, $startDate, $endDate, $period);
        $expensesByCategory = $this->reportService->getExpensesByCategory($branchId, $startDate, $endDate);
        $branches = $this->getBranches();

        $export = $this->exportService->resolveExport($request->get('export'));
        if ($export === 'csv') {
            return $this->exportRevenueVsExpensesCsv($comparison, $expensesByCategory, $startDate, $endDate);
        }
        if ($export === 'pdf') {
            return $this->exportRevenueVsExpensesPdf($comparison, $expensesByCategory, $branchId, $startDate, $endDate, $period);
        }

        return view('accounting.revenue-vs-expenses', compact(
            'comparison',
            'expensesByCategory',
            'branches',
            'branchId',
            'startDate',
            'endDate',
            'period'
        ));
    }

    protected function exportRevenueCsv(array $revenueByService, array $revenueByMethod, float $totalRevenue, string $startDate, string $endDate)
    {
        $rows = [];

        foreach ($revenueByService as $row) {
            $rows[] = [
                'Service Module',
                $row['label'],
                (string) $row['count'],
                number_format($row['total'], 2),
                number_format($row['percentage'], 1) . '%',
            ];
        }

        $rows[] = ['', '', '', '', ''];
        $rows[] = ['Payment Method', 'Label', 'Count', 'Amount (GHS)', 'Share'];

        foreach ($revenueByMethod as $row) {
            $rows[] = [
                'Payment Method',
                $row['label'],
                (string) $row['count'],
                number_format($row['total'], 2),
                number_format($row['percentage'], 1) . '%',
            ];
        }

        $rows[] = ['', '', '', number_format($totalRevenue, 2), '100%'];

        return $this->exportService->streamCsv(
            ['Section', 'Name', 'Transactions', 'Amount (GHS)', 'Share'],
            $rows,
            "revenue-streams-{$startDate}-to-{$endDate}"
        );
    }

    protected function exportRevenuePdf(
        array $revenueByService,
        array $revenueByMethod,
        float $totalRevenue,
        ?int $branchId,
        string $startDate,
        string $endDate
    ) {
        return $this->exportService->downloadPdf('accounting.pdf.report', [
            'documentTitle' => 'Revenue Streams Report',
            'filterSummary' => $this->exportService->buildFilterSummary($branchId, $startDate, $endDate),
            'branch' => $branchId ? Branch::find($branchId) : null,
            'summaryLines' => [
                ['label' => 'Total Revenue', 'value' => 'GH₵' . number_format($totalRevenue, 2)],
            ],
            'sections' => [
                [
                    'title' => 'Revenue by Service Module',
                    'headers' => ['Service Module', 'Transactions', 'Revenue (GH₵)', 'Share'],
                    'align' => ['', 'right', 'right', 'right'],
                    'rows' => array_map(fn ($row) => [
                        $row['label'],
                        number_format($row['count']),
                        number_format($row['total'], 2),
                        number_format($row['percentage'], 1) . '%',
                    ], $revenueByService),
                    'footer' => ['Total', number_format(array_sum(array_column($revenueByService, 'count'))), number_format($totalRevenue, 2), '100%'],
                ],
                [
                    'title' => 'Revenue by Payment Method',
                    'headers' => ['Payment Method', 'Transactions', 'Revenue (GH₵)', 'Share'],
                    'align' => ['', 'right', 'right', 'right'],
                    'rows' => array_map(fn ($row) => [
                        $row['label'],
                        number_format($row['count']),
                        number_format($row['total'], 2),
                        number_format($row['percentage'], 1) . '%',
                    ], $revenueByMethod),
                ],
            ],
        ], $this->exportService->pdfFilename('revenue-streams', $startDate, $endDate));
    }

    protected function exportRevenueDrillDownCsv($rows, string $serviceLabel, string $startDate, string $endDate)
    {
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

    protected function exportRevenueDrillDownPdf(
        $rows,
        string $serviceLabel,
        string $serviceType,
        float $streamTotal,
        ?int $branchId,
        string $startDate,
        string $endDate
    ) {
        return $this->exportService->downloadPdf('accounting.pdf.report', [
            'documentTitle' => $serviceLabel . ' Revenue Detail',
            'filterSummary' => $this->exportService->buildFilterSummary($branchId, $startDate, $endDate),
            'branch' => $branchId ? Branch::find($branchId) : null,
            'summaryLines' => [
                ['label' => 'Service Module', 'value' => $serviceLabel],
                ['label' => 'Stream Total', 'value' => 'GH₵' . number_format($streamTotal, 2)],
                ['label' => 'Transactions', 'value' => number_format($rows->count())],
            ],
            'sections' => [
                [
                    'title' => 'Transaction Line Items',
                    'headers' => ['Date', 'Reference', 'Patient', 'Invoice', 'Method', 'Amount (GH₵)'],
                    'align' => ['', '', '', '', '', 'right'],
                    'rows' => $rows->map(fn ($row) => [
                        $row['transaction_date'],
                        $row['transaction_reference'],
                        $row['patient_name'],
                        $row['invoice_number'] ?? ($row['invoice_id'] ? '#' . $row['invoice_id'] : '—'),
                        ucfirst(str_replace('_', ' ', $row['payment_method'] ?? '—')),
                        number_format($row['amount'], 2),
                    ])->all(),
                    'footer' => ['', '', '', '', 'Total', number_format($streamTotal, 2)],
                ],
            ],
        ], $this->exportService->pdfFilename("revenue-{$serviceType}", $startDate, $endDate));
    }

    protected function exportBalanceSheetCsv(array $balanceSheet, string $asOfDate)
    {
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

        return $this->exportService->streamCsv(
            ['Section', 'Line Item', 'Amount (GHS)'],
            $rows,
            "balance-sheet-{$asOfDate}"
        );
    }

    protected function exportBalanceSheetPdf(array $balanceSheet, ?int $branchId, string $asOfDate)
    {
        return $this->exportService->downloadPdf('accounting.pdf.report', [
            'documentTitle' => 'Balance Sheet',
            'filterSummary' => $this->exportService->buildFilterSummary($branchId, null, null, $asOfDate),
            'branch' => $branchId ? Branch::find($branchId) : null,
            'summaryLines' => [
                ['label' => 'Total Assets', 'value' => 'GH₵' . number_format($balanceSheet['assets']['total_assets'], 2)],
                ['label' => 'Total Liabilities & Equity', 'value' => 'GH₵' . number_format($balanceSheet['totals']['total_liabilities_equity'], 2)],
            ],
            'sections' => [
                [
                    'title' => 'Assets',
                    'headers' => ['Line Item', 'Amount (GH₵)'],
                    'align' => ['', 'right'],
                    'rows' => [
                        ['Cash & Cash Equivalents', number_format($balanceSheet['assets']['cash'], 2)],
                        ['Accounts Receivable', number_format($balanceSheet['assets']['accounts_receivable'], 2)],
                    ],
                    'footer' => ['Total Assets', number_format($balanceSheet['assets']['total_assets'], 2)],
                ],
                [
                    'title' => 'Liabilities & Equity',
                    'headers' => ['Line Item', 'Amount (GH₵)'],
                    'align' => ['', 'right'],
                    'rows' => [
                        ['Accounts Payable (Expenses)', number_format($balanceSheet['liabilities']['accounts_payable'], 2)],
                        ['Retained Earnings (Revenue − Expenses)', number_format($balanceSheet['equity']['retained_earnings'], 2)],
                    ],
                    'footer' => ['Total Liabilities & Equity', number_format($balanceSheet['totals']['total_liabilities_equity'], 2)],
                ],
            ],
        ], $this->exportService->pdfFilename('balance-sheet', $asOfDate, $asOfDate));
    }

    protected function exportCashFlowCsv(array $cashFlow, string $startDate, string $endDate)
    {
        $rows = [
            ['Summary', 'Operating Inflows', number_format($cashFlow['operating']['inflows'], 2)],
            ['Summary', 'Operating Outflows', number_format($cashFlow['operating']['outflows'], 2)],
            ['Summary', 'Refunds / Cancellations', number_format($cashFlow['operating']['refunds'], 2)],
            ['Summary', 'Net Operating', number_format($cashFlow['operating']['net'], 2)],
            ['Summary', 'Net Change in Cash', number_format($cashFlow['net_change_in_cash'], 2)],
        ];

        foreach ($cashFlow['daily_flows'] as $day) {
            $rows[] = [
                'Daily',
                $day['date'],
                number_format($day['inflow'], 2),
                number_format($day['outflow'], 2),
                number_format($day['net'], 2),
            ];
        }

        return $this->exportService->streamCsv(
            ['Section', 'Date / Line Item', 'Inflow (GHS)', 'Outflow (GHS)', 'Net (GHS)'],
            $rows,
            "cash-flow-{$startDate}-to-{$endDate}"
        );
    }

    protected function exportCashFlowPdf(array $cashFlow, ?int $branchId, string $startDate, string $endDate)
    {
        return $this->exportService->downloadPdf('accounting.pdf.report', [
            'documentTitle' => 'Cash Flow Statement',
            'filterSummary' => $this->exportService->buildFilterSummary($branchId, $startDate, $endDate),
            'branch' => $branchId ? Branch::find($branchId) : null,
            'summaryLines' => [
                ['label' => 'Operating Inflows', 'value' => 'GH₵' . number_format($cashFlow['operating']['inflows'], 2)],
                ['label' => 'Operating Outflows', 'value' => 'GH₵' . number_format($cashFlow['operating']['outflows'], 2)],
                ['label' => 'Net Change in Cash', 'value' => 'GH₵' . number_format($cashFlow['net_change_in_cash'], 2)],
            ],
            'sections' => [
                [
                    'title' => 'Operating Activities',
                    'headers' => ['Line Item', 'Amount (GH₵)'],
                    'align' => ['', 'right'],
                    'rows' => [
                        ['Patient payments received', number_format($cashFlow['operating']['inflows'], 2)],
                        ['Operating expenses paid', '-' . number_format($cashFlow['operating']['outflows'], 2)],
                        ['Refunds / cancellations', '-' . number_format($cashFlow['operating']['refunds'], 2)],
                    ],
                    'footer' => ['Net from operating', number_format($cashFlow['operating']['net'], 2)],
                ],
                [
                    'title' => 'Daily Cash Movement',
                    'headers' => ['Date', 'Inflows (GH₵)', 'Outflows (GH₵)', 'Net (GH₵)'],
                    'align' => ['', 'right', 'right', 'right'],
                    'rows' => array_map(fn ($day) => [
                        $day['date'],
                        number_format($day['inflow'], 2),
                        number_format($day['outflow'], 2),
                        number_format($day['net'], 2),
                    ], $cashFlow['daily_flows']),
                ],
            ],
        ], $this->exportService->pdfFilename('cash-flow', $startDate, $endDate));
    }

    protected function exportRevenueVsExpensesCsv(array $comparison, array $expensesByCategory, string $startDate, string $endDate)
    {
        $rows = [];

        foreach ($comparison['periods'] as $row) {
            $rows[] = [
                'Period Comparison',
                $row['label'],
                number_format($row['revenue'], 2),
                number_format($row['expenses'], 2),
                number_format($row['net'], 2),
            ];
        }

        $rows[] = [
            'Period Comparison',
            'Total',
            number_format($comparison['totals']['revenue'], 2),
            number_format($comparison['totals']['expenses'], 2),
            number_format($comparison['totals']['net'], 2),
        ];

        foreach ($expensesByCategory as $cat) {
            $rows[] = [
                'Expenses by Category',
                $cat['category'],
                '',
                number_format($cat['total'], 2),
                number_format($cat['percentage'], 1) . '%',
            ];
        }

        return $this->exportService->streamCsv(
            ['Section', 'Period / Category', 'Revenue (GHS)', 'Expenses (GHS)', 'Net / Share'],
            $rows,
            "revenue-vs-expenses-{$startDate}-to-{$endDate}"
        );
    }

    protected function exportRevenueVsExpensesPdf(
        array $comparison,
        array $expensesByCategory,
        ?int $branchId,
        string $startDate,
        string $endDate,
        string $period
    ) {
        return $this->exportService->downloadPdf('accounting.pdf.report', [
            'documentTitle' => 'Revenue vs Expenses',
            'filterSummary' => $this->exportService->buildFilterSummary($branchId, $startDate, $endDate, null, $period),
            'branch' => $branchId ? Branch::find($branchId) : null,
            'summaryLines' => [
                ['label' => 'Total Revenue', 'value' => 'GH₵' . number_format($comparison['totals']['revenue'], 2)],
                ['label' => 'Total Expenses', 'value' => 'GH₵' . number_format($comparison['totals']['expenses'], 2)],
                ['label' => 'Net Result', 'value' => 'GH₵' . number_format($comparison['totals']['net'], 2)],
            ],
            'sections' => [
                [
                    'title' => 'Period Comparison',
                    'headers' => ['Period', 'Revenue (GH₵)', 'Expenses (GH₵)', 'Net (GH₵)'],
                    'align' => ['', 'right', 'right', 'right'],
                    'rows' => array_map(fn ($row) => [
                        $row['label'],
                        number_format($row['revenue'], 2),
                        number_format($row['expenses'], 2),
                        number_format($row['net'], 2),
                    ], $comparison['periods']),
                    'footer' => [
                        'Total',
                        number_format($comparison['totals']['revenue'], 2),
                        number_format($comparison['totals']['expenses'], 2),
                        number_format($comparison['totals']['net'], 2),
                    ],
                ],
                [
                    'title' => 'Expenses by Category',
                    'headers' => ['Category', 'Amount (GH₵)', 'Share'],
                    'align' => ['', 'right', 'right'],
                    'rows' => array_map(fn ($cat) => [
                        $cat['category'],
                        number_format($cat['total'], 2),
                        number_format($cat['percentage'], 1) . '%',
                    ], $expensesByCategory),
                ],
            ],
        ], $this->exportService->pdfFilename('revenue-vs-expenses', $startDate, $endDate));
    }

    protected function resolveBranchId(Request $request): ?int
    {
        if (auth()->user()->hasRole('super_admin')) {
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

    protected function getBranches()
    {
        return Branch::where('is_active', true)->orderBy('name')->get();
    }
}
