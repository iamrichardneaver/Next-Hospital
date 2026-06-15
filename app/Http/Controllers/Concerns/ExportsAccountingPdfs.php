<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Branch;
use App\Services\AccountingExportService;

trait ExportsAccountingPdfs
{
    protected function exportRevenuePdf(
        AccountingExportService $exportService,
        array $revenueByService,
        array $revenueByMethod,
        float $totalRevenue,
        ?int $branchId,
        string $startDate,
        string $endDate
    ) {
        return $exportService->downloadPdf('accounting.pdf.report', [
            'documentTitle' => 'Revenue Streams Report',
            'filterSummary' => $exportService->buildFilterSummary($branchId, $startDate, $endDate),
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
        ], $exportService->pdfFilename('revenue-streams', $startDate, $endDate));
    }

    protected function exportRevenueDrillDownPdf(
        AccountingExportService $exportService,
        $rows,
        string $serviceLabel,
        string $serviceType,
        float $streamTotal,
        ?int $branchId,
        string $startDate,
        string $endDate
    ) {
        return $exportService->downloadPdf('accounting.pdf.report', [
            'documentTitle' => $serviceLabel . ' Revenue Detail',
            'filterSummary' => $exportService->buildFilterSummary($branchId, $startDate, $endDate),
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
        ], $exportService->pdfFilename("revenue-{$serviceType}", $startDate, $endDate));
    }

    protected function exportBalanceSheetPdf(
        AccountingExportService $exportService,
        array $balanceSheet,
        ?int $branchId,
        string $asOfDate
    ) {
        return $exportService->downloadPdf('accounting.pdf.report', [
            'documentTitle' => 'Balance Sheet',
            'filterSummary' => $exportService->buildFilterSummary($branchId, null, null, $asOfDate),
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
        ], $exportService->pdfFilename('balance-sheet', $asOfDate, $asOfDate));
    }

    protected function exportCashFlowPdf(
        AccountingExportService $exportService,
        array $cashFlow,
        ?int $branchId,
        string $startDate,
        string $endDate
    ) {
        return $exportService->downloadPdf('accounting.pdf.report', [
            'documentTitle' => 'Cash Flow Statement',
            'filterSummary' => $exportService->buildFilterSummary($branchId, $startDate, $endDate),
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
        ], $exportService->pdfFilename('cash-flow', $startDate, $endDate));
    }

    protected function exportRevenueVsExpensesPdf(
        AccountingExportService $exportService,
        array $comparison,
        array $expensesByCategory,
        ?int $branchId,
        string $startDate,
        string $endDate,
        string $period
    ) {
        return $exportService->downloadPdf('accounting.pdf.report', [
            'documentTitle' => 'Revenue vs Expenses',
            'filterSummary' => $exportService->buildFilterSummary($branchId, $startDate, $endDate, null, $period),
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
        ], $exportService->pdfFilename('revenue-vs-expenses', $startDate, $endDate));
    }
}
