<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RevenueTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FinancialReportWebController extends Controller
{
    use ResolvesUserBranch;

    public function index(Request $request)
    {
        $branchId = auth()->user()->hasRole('super_admin')
            ? $request->get('branch_id')
            : $this->resolveUserBranchId(['view_financial_reports', 'view_revenue_analytics']);

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $summary = [
            'total_revenue' => RevenueTransaction::whereBetween('transaction_date', [$startDate, $endDate])
                ->where('status', 'completed')
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->sum('amount'),
            'invoice_count' => Invoice::whereBetween('invoice_date', [$startDate, $endDate])
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->count(),
            'payments_count' => Payment::whereBetween('payment_date', [$startDate, $endDate])
                ->where('status', 'completed')
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->count(),
            'outstanding' => Invoice::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
                ->sum('balance_amount'),
        ];

        $recentPayments = Payment::with(['patient', 'invoice'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('payment_date')
            ->limit(25)
            ->get();

        return view('reports.financial-index', compact('summary', 'recentPayments', 'startDate', 'endDate', 'branchId'));
    }
}
