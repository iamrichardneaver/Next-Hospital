<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Branch;
use App\Models\ServicePricing;
use App\Models\Drug;
use App\Models\Prescription;
use App\Models\DrugOrder;
use App\Models\LabRequest;
use App\Models\Consultation;
use App\Models\ActivityLog;
use App\Models\RevenueTransaction;
use App\Services\RevenueTransactionTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RevenueAnalyticsController extends Controller
{
    /**
     * Display revenue analytics dashboard.
     */
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());
        $branchId = $request->get('branch_id');
        
        // Overall Revenue Statistics
        $overallStats = $this->getOverallStats($startDate, $endDate, $branchId);
        
        // Revenue by Department/Service Type
        $revenueByDepartment = $this->getRevenueByDepartment($startDate, $endDate, $branchId);
        
        // Revenue by Payment Method
        $revenueByPaymentMethod = $this->getRevenueByPaymentMethod($startDate, $endDate, $branchId);
        
        // Daily Revenue Trend
        $dailyRevenueTrend = $this->getDailyRevenueTrend($startDate, $endDate, $branchId);
        
        // Top Revenue Generating Services
        $topServices = $this->getTopServices($startDate, $endDate, $branchId);
        
        // Top Revenue Generating Drugs
        $topDrugs = $this->getTopDrugs($startDate, $endDate, $branchId);
        
        // Branch Comparison (if multi-branch)
        $branchComparison = $this->getBranchComparison($startDate, $endDate);
        
        // Outstanding/Pending Payments
        $outstandingPayments = $this->getOutstandingPayments($branchId);
        
        $branches = Branch::where('is_active', true)->get();
        
        // Log revenue analytics access
        RevenueTransactionTracker::logRevenueAnalyticsAccess([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'branch_id' => $branchId
        ]);
        
        return view('revenue.index', compact(
            'overallStats',
            'revenueByDepartment',
            'revenueByPaymentMethod',
            'dailyRevenueTrend',
            'topServices',
            'topDrugs',
            'branchComparison',
            'outstandingPayments',
            'branches',
            'startDate',
            'endDate',
            'branchId'
        ));
    }

    /**
     * Get overall revenue statistics using revenue_transactions table.
     */
    private function getOverallStats($startDate, $endDate, $branchId = null)
    {
        // Use revenue_transactions for accurate completed revenue
        $revenueQuery = RevenueTransaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));
        
        $totalRevenue = $revenueQuery->sum('amount');
        $totalTransactions = $revenueQuery->count();
        
        // Invoice statistics
        $invoiceQuery = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));
        
        $totalInvoices = $invoiceQuery->count();
        $paidInvoices = $invoiceQuery->where('payment_status', 'paid')->count();
        $pendingAmount = $invoiceQuery->whereIn('payment_status', ['unpaid', 'partial'])->sum('balance_amount');
        $overdueAmount = $invoiceQuery->where('payment_status', 'overdue')->sum('balance_amount');
        
        // Previous period comparison
        $daysDiff = Carbon::parse($endDate)->diffInDays($startDate);
        $previousStart = Carbon::parse($startDate)->subDays($daysDiff)->toDateString();
        $previousEnd = Carbon::parse($startDate)->subDay()->toDateString();
        
        $previousRevenue = RevenueTransaction::whereBetween('transaction_date', [$previousStart, $previousEnd])
            ->where('status', 'completed')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('amount');
        
        $revenueGrowth = $previousRevenue > 0 
            ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100 
            : 0;
        
        return [
            'total_revenue' => round($totalRevenue, 2),
            'total_transactions' => $totalTransactions,
            'total_invoices' => $totalInvoices,
            'paid_invoices' => $paidInvoices,
            'pending_amount' => round($pendingAmount, 2),
            'overdue_amount' => round($overdueAmount, 2),
            'average_transaction_value' => $totalTransactions > 0 ? round($totalRevenue / $totalTransactions, 2) : 0,
            'collection_rate' => $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 2) : 0,
            'revenue_growth' => round($revenueGrowth, 2),
            'previous_revenue' => round($previousRevenue, 2)
        ];
    }

    /**
     * Get revenue breakdown by department/service type using revenue_transactions table.
     */
    private function getRevenueByDepartment($startDate, $endDate, $branchId = null)
    {
        // Use NEW revenue_transactions table for accurate, fast reporting
        $query = RevenueTransaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));
        
        $revenueByService = $query->selectRaw('service_type, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('service_type')
            ->get();
        
        // Map service types to department names
        $departmentMapping = [
            'consultation' => 'Consultations',
            'lab' => 'Laboratory',
            'pharmacy' => 'Pharmacy',
            'imaging' => 'Radiology/Imaging',
            'surgery' => 'Surgery & Procedures',
            'ward' => 'Inpatient/Ward',
            'ecommerce' => 'E-Commerce/Online Shop',
            'insurance' => 'Insurance Payments',
            'other' => 'Other Services'
        ];
        
        $totalRevenue = $revenueByService->sum('total');
        
        $departmentRevenue = $revenueByService->map(function($item) use ($departmentMapping, $totalRevenue) {
            return [
                'department' => $departmentMapping[$item->service_type] ?? ucfirst($item->service_type),
                'service_type' => $item->service_type,
                'revenue' => round($item->total, 2),
                'transaction_count' => $item->count,
                'percentage' => $totalRevenue > 0 ? round(($item->total / $totalRevenue) * 100, 2) : 0
            ];
        })->sortByDesc('revenue')->values()->toArray();
        
        return $departmentRevenue;
    }

    /**
     * Get revenue breakdown by payment method using revenue_transactions table.
     */
    private function getRevenueByPaymentMethod($startDate, $endDate, $branchId = null)
    {
        // Use revenue_transactions for accurate tracking
        $methodRevenue = RevenueTransaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->whereNotNull('payment_method')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->selectRaw('payment_method, SUM(amount) as total_amount, COUNT(*) as count')
            ->groupBy('payment_method')
            ->get();
        
        $totalRevenue = $methodRevenue->sum('total_amount');
        
        return $methodRevenue->map(function($item) use ($totalRevenue) {
            return [
                'payment_method' => ucfirst(str_replace('_', ' ', $item->payment_method)),
                'amount' => round($item->total_amount, 2),
                'count' => $item->count,
                'percentage' => $totalRevenue > 0 ? round(($item->total_amount / $totalRevenue) * 100, 2) : 0
            ];
        })->sortByDesc('amount')->values();
    }

    /**
     * Get daily revenue trend using revenue_transactions table.
     */
    private function getDailyRevenueTrend($startDate, $endDate, $branchId = null)
    {
        // Use revenue_transactions for accurate daily tracking
        $dailyRevenue = RevenueTransaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return $dailyRevenue->map(function($item) {
            return [
                'date' => $item->date,
                'revenue' => round($item->revenue, 2),
                'transaction_count' => $item->transaction_count
            ];
        });
    }

    /**
     * Get top revenue generating services.
     */
    private function getTopServices($startDate, $endDate, $branchId = null, $limit = 10)
    {
        $invoices = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();
        
        $serviceRevenue = [];
        
        foreach ($invoices as $invoice) {
            if ($invoice->items && is_array($invoice->items)) {
                foreach ($invoice->items as $item) {
                    $serviceName = $item['service_name'] ?? 'Unknown Service';
                    $amount = $item['amount'] ?? 0;
                    $quantity = $item['quantity'] ?? 1;
                    
                    if (!isset($serviceRevenue[$serviceName])) {
                        $serviceRevenue[$serviceName] = [
                            'service_name' => $serviceName,
                            'revenue' => 0,
                            'count' => 0
                        ];
                    }
                    
                    $serviceRevenue[$serviceName]['revenue'] += $amount;
                    $serviceRevenue[$serviceName]['count'] += $quantity;
                }
            }
        }
        
        // Sort by revenue and limit
        usort($serviceRevenue, function($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });
        
        return array_slice($serviceRevenue, 0, $limit);
    }

    /**
     * Get top revenue generating drugs.
     */
    private function getTopDrugs($startDate, $endDate, $branchId = null, $limit = 10)
    {
        $drugOrders = DrugOrder::whereBetween('created_at', [$startDate, $endDate])
            ->with(['drug', 'prescription'])
            ->when($branchId, function($q) use ($branchId) {
                $q->whereHas('prescription', function($prescriptionQuery) use ($branchId) {
                    $prescriptionQuery->where('branch_id', $branchId);
                });
            })
            ->get();
        
        $drugRevenue = [];
        
        foreach ($drugOrders as $order) {
            if ($order->drug) {
                $drugName = $order->drug->name;
                $revenue = $order->quantity * $order->drug->selling_price;
                
                if (!isset($drugRevenue[$drugName])) {
                    $drugRevenue[$drugName] = [
                        'drug_name' => $drugName,
                        'revenue' => 0,
                        'quantity' => 0
                    ];
                }
                
                $drugRevenue[$drugName]['revenue'] += $revenue;
                $drugRevenue[$drugName]['quantity'] += $order->quantity;
            }
        }
        
        // Sort by revenue and limit
        usort($drugRevenue, function($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });
        
        return array_slice($drugRevenue, 0, $limit);
    }

    /**
     * Get branch comparison.
     */
    private function getBranchComparison($startDate, $endDate)
    {
        $branchRevenue = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->join('branches', 'invoices.branch_id', '=', 'branches.id')
            ->select(
                'branches.id',
                'branches.name',
                DB::raw('SUM(invoices.total_amount) as revenue'),
                DB::raw('COUNT(invoices.id) as invoice_count')
            )
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('revenue')
            ->get();
        
        return $branchRevenue;
    }

    /**
     * Get outstanding/pending payments.
     */
    private function getOutstandingPayments($branchId = null)
    {
        $outstanding = Invoice::where('status', '!=', 'paid')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['patient', 'branch'])
            ->orderBy('invoice_date', 'desc')
            ->limit(20)
            ->get();
        
        return $outstanding;
    }

    /**
     * Export revenue report to CSV.
     */
    public function export(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());
        $branchId = $request->get('branch_id');
        
        $revenueByDepartment = $this->getRevenueByDepartment($startDate, $endDate, $branchId);
        
        $filename = 'revenue-report-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        
        $callback = function() use ($revenueByDepartment, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            
            // Report header
            fputcsv($file, ['Revenue Report']);
            fputcsv($file, ['Period:', $startDate . ' to ' . $endDate]);
            fputcsv($file, []);
            
            // Department revenue
            fputcsv($file, ['Department', 'Revenue (GHS)', 'Percentage (%)']);
            
            foreach ($revenueByDepartment as $dept) {
                fputcsv($file, [
                    $dept['department'],
                    number_format($dept['revenue'], 2),
                    $dept['percentage']
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get transaction trail for a specific invoice.
     */
    public function getInvoiceTransactionTrail(Request $request, $invoiceId)
    {
        $invoice = Invoice::with(['patient', 'branch', 'payments'])->findOrFail($invoiceId);
        $transactionTrail = RevenueTransactionTracker::getInvoiceTransactionTrail($invoice);
        
        return response()->json([
            'invoice' => $invoice,
            'transaction_trail' => $transactionTrail,
            'summary' => [
                'total_transactions' => $transactionTrail->count(),
                'created_at' => $invoice->created_at,
                'last_activity' => $transactionTrail->first()?->created_at,
                'status_changes' => $transactionTrail->where('event', 'updated')->count(),
                'payments_received' => $transactionTrail->where('subject_type', Payment::class)->count()
            ]
        ]);
    }

    /**
     * Get transaction trail for a specific payment.
     */
    public function getPaymentTransactionTrail(Request $request, $paymentId)
    {
        $payment = Payment::with(['invoice.patient', 'invoice.branch', 'processor'])->findOrFail($paymentId);
        $transactionTrail = RevenueTransactionTracker::getPaymentTransactionTrail($payment);
        
        return response()->json([
            'payment' => $payment,
            'transaction_trail' => $transactionTrail,
            'summary' => [
                'total_transactions' => $transactionTrail->count(),
                'created_at' => $payment->created_at,
                'last_activity' => $transactionTrail->first()?->created_at,
                'status_changes' => $transactionTrail->where('event', 'updated')->count(),
                'processed_by' => $payment->processor?->name ?? 'System'
            ]
        ]);
    }

    /**
     * Get revenue transactions by department/service type.
     */
    public function getTransactionsByServiceType(Request $request)
    {
        $serviceType = $request->get('service_type');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());
        
        $transactions = RevenueTransactionTracker::getRevenueTransactionsByServiceType($serviceType, $startDate, $endDate);
        
        return response()->json([
            'service_type' => $serviceType,
            'transactions' => $transactions,
            'summary' => [
                'total_transactions' => $transactions->count(),
                'total_amount' => $transactions->where('subject_type', Payment::class)->sum('properties.amount'),
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]
        ]);
    }

    /**
     * Get revenue transactions by payment method.
     */
    public function getTransactionsByPaymentMethod(Request $request)
    {
        $paymentMethod = $request->get('payment_method');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());
        
        $transactions = RevenueTransactionTracker::getRevenueTransactionsByPaymentMethod($paymentMethod, $startDate, $endDate);
        
        return response()->json([
            'payment_method' => $paymentMethod,
            'transactions' => $transactions,
            'summary' => [
                'total_transactions' => $transactions->count(),
                'total_amount' => $transactions->sum('properties.amount'),
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]
        ]);
    }

    /**
     * Get detailed revenue transaction trail.
     */
    public function getDetailedTransactionTrail(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());
        $branchId = $request->get('branch_id');
        
        $transactions = RevenueTransactionTracker::getRevenueTransactionsByDateRange($startDate, $endDate, $branchId);
        
        return response()->json([
            'transactions' => $transactions,
            'summary' => [
                'total_transactions' => $transactions->count(),
                'invoices_created' => $transactions->where('subject_type', Invoice::class)->count(),
                'payments_received' => $transactions->where('subject_type', Payment::class)->count(),
                'total_revenue' => $transactions->where('subject_type', Payment::class)->sum('properties.amount'),
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'branch_id' => $branchId
            ]
        ]);
    }
}

