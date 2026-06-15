<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Branch;
use App\Models\ServicePricing;
use App\Models\Drug;
use App\Models\Prescription;
use App\Models\DrugOrder;
use App\Models\LabRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RevenueAnalyticsController extends Controller
{
    /**
     * Get revenue summary
     */
    public function getSummary(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());
            $branchId = $request->get('branch_id');

            $stats = $this->getOverallStats($startDate, $endDate, $branchId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch revenue summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue by department
     */
    public function getRevenueByDepartment(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());
            $branchId = $request->get('branch_id');

            $data = $this->calculateRevenueByDepartment($startDate, $endDate, $branchId);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch department revenue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue by payment method
     */
    public function getRevenueByPaymentMethod(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());
            $branchId = $request->get('branch_id');

            $data = $this->calculateRevenueByPaymentMethod($startDate, $endDate, $branchId);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment method revenue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get daily revenue trend
     */
    public function getDailyTrend(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->subDays(30)->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());
            $branchId = $request->get('branch_id');

            $data = $this->calculateDailyRevenueTrend($startDate, $endDate, $branchId);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch daily trend: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top revenue generating services
     */
    public function getTopServices(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());
            $branchId = $request->get('branch_id');
            $limit = $request->get('limit', 10);

            $data = $this->calculateTopServices($startDate, $endDate, $branchId, $limit);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch top services: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top revenue generating drugs
     */
    public function getTopDrugs(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());
            $branchId = $request->get('branch_id');
            $limit = $request->get('limit', 10);

            $data = $this->calculateTopDrugs($startDate, $endDate, $branchId, $limit);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch top drugs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get branch comparison
     */
    public function getBranchComparison(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());

            $data = $this->calculateBranchComparison($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branch comparison: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get outstanding payments
     */
    public function getOutstandingPayments(Request $request): JsonResponse
    {
        try {
            $branchId = $request->get('branch_id');

            $data = $this->calculateOutstandingPayments($branchId);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch outstanding payments: ' . $e->getMessage()
            ], 500);
        }
    }

    // Private helper methods
    private function getOverallStats($startDate, $endDate, $branchId = null)
    {
        $query = Payment::whereBetween('payment_date', [$startDate, $endDate]);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return [
            'total_revenue' => $query->sum('amount'),
            'total_transactions' => $query->count(),
            'average_transaction' => $query->avg('amount'),
            'cash_payments' => Payment::whereBetween('payment_date', [$startDate, $endDate])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where('payment_method', 'cash')
                ->sum('amount'),
            'card_payments' => Payment::whereBetween('payment_date', [$startDate, $endDate])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where('payment_method', 'card')
                ->sum('amount'),
            'insurance_payments' => Payment::whereBetween('payment_date', [$startDate, $endDate])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where('payment_method', 'insurance')
                ->sum('amount'),
        ];
    }

    private function calculateRevenueByDepartment($startDate, $endDate, $branchId = null)
    {
        // This would need to be customized based on your invoice items structure
        return [
            'consultation' => 0,
            'laboratory' => 0,
            'pharmacy' => 0,
            'radiology' => 0,
            'other' => 0,
        ];
    }

    private function calculateRevenueByPaymentMethod($startDate, $endDate, $branchId = null)
    {
        $query = Payment::whereBetween('payment_date', [$startDate, $endDate]);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();
    }

    private function calculateDailyRevenueTrend($startDate, $endDate, $branchId = null)
    {
        $query = Payment::whereBetween('payment_date', [$startDate, $endDate]);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->select(
                DB::raw('DATE(payment_date) as date'),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as transactions')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
    }

    private function calculateTopServices($startDate, $endDate, $branchId = null, $limit = 10)
    {
        // This would need invoice_items or similar table
        return [];
    }

    private function calculateTopDrugs($startDate, $endDate, $branchId = null, $limit = 10)
    {
        $query = DrugOrder::with('drug')
            ->whereBetween('created_at', [$startDate, $endDate]);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->select('drug_id', DB::raw('SUM(quantity * unit_price) as revenue'), DB::raw('SUM(quantity) as quantity_sold'))
            ->groupBy('drug_id')
            ->orderBy('revenue', 'desc')
            ->limit($limit)
            ->get();
    }

    private function calculateBranchComparison($startDate, $endDate)
    {
        return Branch::withCount(['payments' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('payment_date', [$startDate, $endDate]);
            }])
            ->with(['payments' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('payment_date', [$startDate, $endDate])
                    ->select('branch_id', DB::raw('SUM(amount) as total_revenue'));
            }])
            ->get();
    }

    private function calculateOutstandingPayments($branchId = null)
    {
        $query = Invoice::where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled');
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return [
            'total_outstanding' => $query->sum(DB::raw('total_amount - paid_amount')),
            'count' => $query->count(),
            'oldest_unpaid' => $query->oldest()->first(),
            'by_status' => $query->select('status', DB::raw('SUM(total_amount - paid_amount) as amount'))
                ->groupBy('status')
                ->get(),
        ];
    }
}
