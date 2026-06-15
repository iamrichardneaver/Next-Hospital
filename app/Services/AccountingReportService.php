<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RevenueTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingReportService
{
    public const SERVICE_TYPE_LABELS = [
        'consultation' => 'Consultations',
        'lab' => 'Laboratory',
        'pharmacy' => 'Pharmacy',
        'imaging' => 'Radiology/Imaging',
        'surgery' => 'Surgery & Procedures',
        'ward' => 'Inpatient/Ward',
        'ecommerce' => 'E-Commerce',
        'insurance' => 'Insurance Payments',
        'other' => 'Other Services',
    ];

    public function resolveFilters(Request $request, ?int $defaultBranchId = null): array
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $asOfDate = $request->get('as_of_date', $endDate);
        $branchId = auth()->user()->hasRole('super_admin')
            ? ($request->filled('branch_id') ? (int) $request->branch_id : null)
            : $defaultBranchId;
        $period = $request->get('period', 'monthly');

        return compact('startDate', 'endDate', 'asOfDate', 'branchId', 'period');
    }

    public function getDashboardKpis(?int $branchId, string $startDate, string $endDate): array
    {
        $totalRevenue = $this->getTotalRevenue($branchId, $startDate, $endDate);
        $totalExpenses = $this->getTotalExpenses($branchId, $startDate, $endDate);
        $receivables = $this->getAccountsReceivable($branchId, $endDate);

        return [
            'total_revenue' => round($totalRevenue, 2),
            'total_expenses' => round($totalExpenses, 2),
            'net_income' => round($totalRevenue - $totalExpenses, 2),
            'outstanding_receivables' => round($receivables, 2),
            'today_revenue' => round($this->getTotalRevenue($branchId, now()->toDateString(), now()->toDateString()), 2),
            'month_revenue' => round($this->getTotalRevenue($branchId, now()->startOfMonth()->toDateString(), now()->toDateString()), 2),
            'expense_count' => $this->expenseQuery($branchId, $startDate, $endDate)->count(),
            'revenue_transactions' => $this->revenueQuery($branchId, $startDate, $endDate)->count(),
        ];
    }

    public function getTotalRevenue(?int $branchId, ?string $startDate = null, ?string $endDate = null): float
    {
        return (float) $this->revenueQuery($branchId, $startDate, $endDate)->sum('amount');
    }

    public function getRevenueComposition(?int $branchId, string $startDate, string $endDate): array
    {
        return $this->getRevenueByServiceType($branchId, $startDate, $endDate);
    }

    public function getRevenueByServiceType(?int $branchId, string $startDate, string $endDate): array
    {
        $rows = $this->revenueQuery($branchId, $startDate, $endDate)
            ->selectRaw('service_type, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('service_type')
            ->orderByDesc('total')
            ->get();

        $grandTotal = $rows->sum('total');

        return $rows->map(function ($row) use ($grandTotal) {
            return [
                'service_type' => $row->service_type,
                'label' => self::SERVICE_TYPE_LABELS[$row->service_type] ?? ucfirst(str_replace('_', ' ', $row->service_type ?? 'other')),
                'total' => round((float) $row->total, 2),
                'count' => (int) $row->count,
                'percentage' => $grandTotal > 0 ? round(($row->total / $grandTotal) * 100, 2) : 0,
            ];
        })->values()->all();
    }

    public function getRevenueByPaymentMethod(?int $branchId, string $startDate, string $endDate): array
    {
        $rows = $this->revenueQuery($branchId, $startDate, $endDate)
            ->whereNotNull('payment_method')
            ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        $grandTotal = $rows->sum('total');

        return $rows->map(function ($row) use ($grandTotal) {
            return [
                'payment_method' => $row->payment_method,
                'label' => ucfirst(str_replace('_', ' ', $row->payment_method)),
                'total' => round((float) $row->total, 2),
                'count' => (int) $row->count,
                'percentage' => $grandTotal > 0 ? round(($row->total / $grandTotal) * 100, 2) : 0,
            ];
        })->values()->all();
    }

    public function getTotalExpenses(?int $branchId, string $startDate, string $endDate, bool $approvedOnly = true): float
    {
        $query = $this->expenseQuery($branchId, $startDate, $endDate);

        if ($approvedOnly) {
            $query->approved();
        }

        return (float) $query->sum('amount');
    }

    public function getExpensesByCategory(?int $branchId, string $startDate, string $endDate): array
    {
        $rows = Expense::query()
            ->approved()
            ->byDateRange($startDate, $endDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.id as category_id, expense_categories.name as category, expense_categories.code, SUM(expenses.amount) as total, COUNT(*) as count')
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.code')
            ->orderByDesc('total')
            ->get();

        $grandTotal = $rows->sum('total');

        return $rows->map(function ($row) use ($grandTotal) {
            return [
                'category_id' => (int) $row->category_id,
                'category' => $row->category,
                'code' => $row->code,
                'total' => round((float) $row->total, 2),
                'count' => (int) $row->count,
                'percentage' => $grandTotal > 0 ? round(($row->total / $grandTotal) * 100, 2) : 0,
            ];
        })->values()->all();
    }

    public function getPendingExpenseCount(?int $branchId): int
    {
        return Expense::query()
            ->where('status', 'pending')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();
    }

    public function resolvePeriodDateRange(string $periodKey, string $period = 'monthly'): array
    {
        if ($period === 'quarterly' && preg_match('/^(\d{4})-Q(\d)$/', $periodKey, $matches)) {
            $year = (int) $matches[1];
            $quarter = (int) $matches[2];
            $startMonth = (($quarter - 1) * 3) + 1;

            return [
                Carbon::create($year, $startMonth, 1)->startOfMonth()->toDateString(),
                Carbon::create($year, $startMonth, 1)->addMonths(2)->endOfMonth()->toDateString(),
            ];
        }

        try {
            $start = Carbon::createFromFormat('Y-m', $periodKey)->startOfMonth();
        } catch (\Exception) {
            return [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
        }

        return [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()];
    }

    public function getExpensesByDepartment(?int $branchId, string $startDate, string $endDate): array
    {
        $rows = Expense::query()
            ->approved()
            ->byDateRange($startDate, $endDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw("COALESCE(department, 'general') as department, SUM(amount) as total, COUNT(*) as count")
            ->groupBy('department')
            ->orderByDesc('total')
            ->get();

        $grandTotal = $rows->sum('total');

        return $rows->map(function ($row) use ($grandTotal) {
            $label = Expense::DEPARTMENTS[$row->department] ?? ucfirst($row->department ?? 'general');

            return [
                'department' => $row->department,
                'label' => $label,
                'total' => round((float) $row->total, 2),
                'count' => (int) $row->count,
                'percentage' => $grandTotal > 0 ? round(($row->total / $grandTotal) * 100, 2) : 0,
            ];
        })->values()->all();
    }

    public function getBalanceSheet(?int $branchId, string $asOfDate): array
    {
        $cash = (float) Payment::query()
            ->where('status', 'completed')
            ->whereDate('payment_date', '<=', $asOfDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $receivables = $this->getAccountsReceivable($branchId, $asOfDate);

        $totalAssets = $cash + $receivables;

        $payables = (float) Expense::query()
            ->whereIn('status', ['approved', 'pending'])
            ->whereDate('expense_date', '<=', $asOfDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $cumulativeRevenue = (float) RevenueTransaction::query()
            ->where('status', 'completed')
            ->whereDate('transaction_date', '<=', $asOfDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $cumulativeExpenses = (float) Expense::query()
            ->approved()
            ->whereDate('expense_date', '<=', $asOfDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $retainedEarnings = $cumulativeRevenue - $cumulativeExpenses;
        $totalLiabilities = $payables;
        $totalEquity = $retainedEarnings;
        $totalLiabilitiesEquity = $totalLiabilities + $totalEquity;

        return [
            'as_of_date' => $asOfDate,
            'assets' => [
                'cash' => round($cash, 2),
                'accounts_receivable' => round($receivables, 2),
                'total_current_assets' => round($totalAssets, 2),
                'total_assets' => round($totalAssets, 2),
            ],
            'liabilities' => [
                'accounts_payable' => round($payables, 2),
                'total_liabilities' => round($totalLiabilities, 2),
            ],
            'equity' => [
                'retained_earnings' => round($retainedEarnings, 2),
                'total_equity' => round($totalEquity, 2),
            ],
            'totals' => [
                'total_liabilities_equity' => round($totalLiabilitiesEquity, 2),
                'balanced' => abs($totalAssets - $totalLiabilitiesEquity) < 0.01,
            ],
            'notes' => [
                'Cash reflects cumulative completed payments received.',
                'Receivables are unpaid/partial/overdue invoice balances.',
                'Payables include pending and approved unpaid expenses.',
            ],
        ];
    }

    public function getCashFlow(?int $branchId, string $startDate, string $endDate): array
    {
        $operatingInflows = $this->getTotalRevenue($branchId, $startDate, $endDate);

        $operatingOutflows = (float) Expense::query()
            ->approved()
            ->byDateRange($startDate, $endDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $refunds = (float) Payment::query()
            ->where('status', 'cancelled')
            ->byDateRange($startDate, $endDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $netOperating = $operatingInflows - $operatingOutflows - $refunds;
        $investing = 0.0;
        $financing = 0.0;
        $netChange = $netOperating + $investing + $financing;

        $dailyFlows = $this->getDailyCashFlow($branchId, $startDate, $endDate);

        return [
            'operating' => [
                'inflows' => round($operatingInflows, 2),
                'outflows' => round($operatingOutflows, 2),
                'refunds' => round($refunds, 2),
                'net' => round($netOperating, 2),
            ],
            'investing' => [
                'inflows' => 0,
                'outflows' => 0,
                'net' => round($investing, 2),
            ],
            'financing' => [
                'inflows' => 0,
                'outflows' => 0,
                'net' => round($financing, 2),
            ],
            'net_change_in_cash' => round($netChange, 2),
            'daily_flows' => $dailyFlows,
        ];
    }

    public function getRevenueVsExpenses(?int $branchId, string $startDate, string $endDate, string $period = 'monthly'): array
    {
        $groupFormat = $period === 'quarterly' ? '%Y-Q' : '%Y-%m';
        $labelFormat = $period === 'quarterly' ? 'Y-\QQ' : 'M Y';

        $revenueRows = $this->revenueQuery($branchId, $startDate, $endDate)
            ->selectRaw("DATE_FORMAT(transaction_date, '{$groupFormat}') as period_key, SUM(amount) as total")
            ->groupBy('period_key')
            ->pluck('total', 'period_key');

        $expenseRows = Expense::query()
            ->approved()
            ->byDateRange($startDate, $endDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw("DATE_FORMAT(expense_date, '{$groupFormat}') as period_key, SUM(amount) as total")
            ->groupBy('period_key')
            ->pluck('total', 'period_key');

        $periods = collect($revenueRows->keys()->merge($expenseRows->keys())->unique()->sort()->values());

        $comparison = $periods->map(function ($key) use ($revenueRows, $expenseRows, $period) {
            $revenue = (float) ($revenueRows[$key] ?? 0);
            $expenses = (float) ($expenseRows[$key] ?? 0);

            if ($period === 'quarterly' && preg_match('/^(\d{4})-Q(\d)$/', $key, $m)) {
                $label = 'Q' . $m[2] . ' ' . $m[1];
            } else {
                try {
                    $label = Carbon::createFromFormat('Y-m', $key)->format('M Y');
                } catch (\Exception) {
                    $label = $key;
                }
            }

            return [
                'period_key' => $key,
                'label' => $label,
                'revenue' => round($revenue, 2),
                'expenses' => round($expenses, 2),
                'net' => round($revenue - $expenses, 2),
            ];
        })->values()->all();

        $totalRevenue = array_sum(array_column($comparison, 'revenue'));
        $totalExpenses = array_sum(array_column($comparison, 'expenses'));

        return [
            'periods' => $comparison,
            'totals' => [
                'revenue' => round($totalRevenue, 2),
                'expenses' => round($totalExpenses, 2),
                'net' => round($totalRevenue - $totalExpenses, 2),
            ],
        ];
    }

    protected function revenueQuery(?int $branchId, ?string $startDate, ?string $endDate)
    {
        $query = RevenueTransaction::query()->where('status', 'completed');

        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->whereDate('transaction_date', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('transaction_date', '<=', $endDate);
        }

        return $query->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    protected function expenseQuery(?int $branchId, string $startDate, string $endDate)
    {
        return Expense::query()
            ->approved()
            ->byDateRange($startDate, $endDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    protected function getAccountsReceivable(?int $branchId, string $asOfDate): float
    {
        return (float) Invoice::query()
            ->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
            ->whereDate('invoice_date', '<=', $asOfDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('balance_amount');
    }

    protected function getDailyCashFlow(?int $branchId, string $startDate, string $endDate): array
    {
        $inflows = RevenueTransaction::query()
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->select(DB::raw('DATE(transaction_date) as date'), DB::raw('SUM(amount) as inflow'))
            ->groupBy('date')
            ->pluck('inflow', 'date');

        $outflows = Expense::query()
            ->approved()
            ->byDateRange($startDate, $endDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->select(DB::raw('DATE(expense_date) as date'), DB::raw('SUM(amount) as outflow'))
            ->groupBy('date')
            ->pluck('outflow', 'date');

        $dates = collect($inflows->keys()->merge($outflows->keys())->unique()->sort()->values());

        return $dates->map(function ($date) use ($inflows, $outflows) {
            $in = (float) ($inflows[$date] ?? 0);
            $out = (float) ($outflows[$date] ?? 0);

            return [
                'date' => $date,
                'inflow' => round($in, 2),
                'outflow' => round($out, 2),
                'net' => round($in - $out, 2),
            ];
        })->values()->all();
    }
}
