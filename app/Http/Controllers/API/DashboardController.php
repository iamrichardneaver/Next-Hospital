<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PharmacyInventoryAlertService;
use App\Services\DashboardStatsService;
use App\Services\AccountingReportService;
use App\Services\RevenueReportService;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();
            $branchId = $request->get('branch_id') ? (int) $request->get('branch_id') : null;

            // Get patient statistics
            $totalPatientsQuery = DB::table('patients');
            if ($branchId) {
                $totalPatientsQuery->where('branch_id', $branchId);
            }
            $totalPatients = $totalPatientsQuery->count();
            
            $activePatients = $totalPatients; // All patients are considered active
            
            $newPatientsTodayQuery = DB::table('patients')
                ->whereDate('created_at', $today);
            if ($branchId) {
                $newPatientsTodayQuery->where('branch_id', $branchId);
            }
            $newPatientsToday = $newPatientsTodayQuery->count();

            // Get revenue statistics
            $todayRevenueQuery = DB::table('invoices')
                ->whereDate('created_at', $today)
                ->where('status', 'paid');
            if ($branchId) {
                $todayRevenueQuery->where('branch_id', $branchId);
            }
            $todayRevenue = $todayRevenueQuery->sum('total_amount');

            $cashPaymentsQuery = DB::table('payments')
                ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->whereDate('payments.created_at', $today)
                ->where('payments.payment_method', 'cash');
            if ($branchId) {
                $cashPaymentsQuery->where('invoices.branch_id', $branchId);
            }
            $cashPayments = $cashPaymentsQuery->sum('payments.amount');

            $insurancePaymentsQuery = DB::table('payments')
                ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->whereDate('payments.created_at', $today)
                ->where('payments.payment_method', 'insurance');
            if ($branchId) {
                $insurancePaymentsQuery->where('invoices.branch_id', $branchId);
            }
            $insurancePayments = $insurancePaymentsQuery->sum('payments.amount');

            // Get appointment statistics
            $appointmentsTodayQuery = DB::table('appointments')
                ->whereDate('appointment_date', $today);
            if ($branchId) {
                $appointmentsTodayQuery->where('branch_id', $branchId);
            }
            $appointmentsToday = $appointmentsTodayQuery->count();

            $pendingAppointmentsQuery = DB::table('appointments')
                ->whereDate('appointment_date', $today)
                ->where('status', 'scheduled');
            if ($branchId) {
                $pendingAppointmentsQuery->where('branch_id', $branchId);
            }
            $pendingAppointments = $pendingAppointmentsQuery->count();

            $completedAppointmentsQuery = DB::table('appointments')
                ->whereDate('appointment_date', $today)
                ->where('status', 'completed');
            if ($branchId) {
                $completedAppointmentsQuery->where('branch_id', $branchId);
            }
            $completedAppointments = $completedAppointmentsQuery->count();

            $cancelledAppointmentsQuery = DB::table('appointments')
                ->whereDate('appointment_date', $today)
                ->where('status', 'cancelled');
            if ($branchId) {
                $cancelledAppointmentsQuery->where('branch_id', $branchId);
            }
            $cancelledAppointments = $cancelledAppointmentsQuery->count();

            // Get staff statistics
            $activeStaffQuery = DB::table('users');
            // Note: users table doesn't have branch_id column, so we can't filter by branch
            $activeStaff = $activeStaffQuery->count();

            $doctorsCountQuery = DB::table('users')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('roles.name', 'doctor');
            // Note: users table doesn't have branch_id column, so we can't filter by branch
            $doctorsCount = $doctorsCountQuery->count();

            $nursesCountQuery = DB::table('users')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('roles.name', 'nurse');
            // Note: users table doesn't have branch_id column, so we can't filter by branch
            $nursesCount = $nursesCountQuery->count();

            $stats = [
                'total_patients' => $totalPatients,
                'active_patients' => $activePatients,
                'new_patients_today' => $newPatientsToday,
                'today_revenue' => $todayRevenue,
                'cash_payments' => $cashPayments,
                'insurance_payments' => $insurancePayments,
                'appointments_today' => $appointmentsToday,
                'pending_appointments' => $pendingAppointments,
                'completed_appointments' => $completedAppointments,
                'cancelled_appointments' => $cancelledAppointments,
                'active_staff' => $activeStaff,
                'doctors_count' => $doctorsCount,
                'nurses_count' => $nursesCount,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patient statistics for charts
     */
    public function getPatientStatistics(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 7);
            $branchId = $request->get('branch_id') ? (int) $request->get('branch_id') : null;
            $startDate = Carbon::now()->subDays($days);

            $patientStatsQuery = DB::table('patients')
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as visits'),
                    DB::raw('COUNT(CASE WHEN DATE(created_at) = DATE(created_at) THEN 1 END) as new_patients')
                )
                ->where('created_at', '>=', $startDate);
            
            if ($branchId) {
                $patientStatsQuery->where('branch_id', $branchId);
            }
            
            $patientStats = $patientStatsQuery
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $patientStats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch patient statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue analytics with role-scoped dashboard revenue (parity with web).
     */
    public function getRevenueAnalytics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $statsService = app(DashboardStatsService::class);
            $branchId = $statsService->resolveBranchId(
                $user,
                $request->filled('branch_id') ? (int) $request->get('branch_id') : null
            );

            $revenueReportService = app(RevenueReportService::class);
            $revenueData = $revenueReportService->getDashboardRevenue($user, $branchId);

            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());
            $accountingService = app(AccountingReportService::class);

            $composition = $revenueReportService->userHasFullRevenueAccess($user)
                ? $accountingService->getRevenueByServiceType($branchId, $startDate, $endDate)
                : array_values(array_filter(
                    $accountingService->getRevenueByServiceType($branchId, $startDate, $endDate),
                    fn ($row) => in_array($row['service_type'], $revenueReportService->resolveUserRevenueServiceTypes($user), true)
                ));

            return response()->json([
                'success' => true,
                'data' => [
                    'dashboard_revenue' => $revenueData,
                    'revenue_by_service' => $composition,
                    'branch_id' => $branchId,
                    'period' => compact('startDate', 'endDate'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch revenue analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get department performance data
     */
    public function getDepartmentPerformance(Request $request): JsonResponse
    {
        try {
            $today = Carbon::today();
            $branchId = $request->get('branch_id') ? (int) $request->get('branch_id') : null;

            // Since we don't have departments table, let's group by appointment type
            $departmentPerformanceQuery = DB::table('appointments')
                ->select(
                    'appointment_type as department',
                    DB::raw('COUNT(id) as patients')
                )
                ->whereDate('appointment_date', $today);
            
            if ($branchId) {
                $departmentPerformanceQuery->where('branch_id', $branchId);
            }
            
            $departmentPerformance = $departmentPerformanceQuery
                ->groupBy('appointment_type')
                ->get();

            $totalPatients = $departmentPerformance->sum('patients');

            $departmentPerformance = $departmentPerformance->map(function ($item) use ($totalPatients) {
                $item->percentage = $totalPatients > 0 ? round(($item->patients / $totalPatients) * 100, 2) : 0;
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $departmentPerformance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch department performance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent appointments
     */
    public function getRecentAppointments(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $branchId = $request->get('branch_id') ? (int) $request->get('branch_id') : null;

            $recentAppointmentsQuery = DB::table('appointments')
                ->join('patients', 'appointments.patient_id', '=', 'patients.id')
                ->join('users', 'appointments.doctor_id', '=', 'users.id')
                ->select(
                    'appointments.id',
                    'patients.first_name',
                    'patients.last_name',
                    'patients.patient_number',
                    'appointments.appointment_type as department',
                    'appointments.appointment_time',
                    'appointments.appointment_date',
                    'appointments.status',
                    'users.name as doctor_name'
                );
            
            if ($branchId) {
                $recentAppointmentsQuery->where('appointments.branch_id', $branchId);
            }
            
            $recentAppointments = $recentAppointmentsQuery
                ->orderBy('appointments.appointment_date', 'desc')
                ->orderBy('appointments.appointment_time', 'desc')
                ->limit($limit)
                ->get();

            $formattedAppointments = $recentAppointments->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'patient_name' => $appointment->first_name . ' ' . $appointment->last_name,
                    'patient_id' => $appointment->patient_number,
                    'department' => $appointment->department,
                    'appointment_time' => Carbon::parse($appointment->appointment_time)->format('g:i A'),
                    'appointment_date' => Carbon::parse($appointment->appointment_date)->format('M d, Y'),
                    'status' => $appointment->status,
                    'doctor_name' => $appointment->doctor_name,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedAppointments
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all dashboard data in one call
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        try {
            $branchId = $request->get('branch_id') ? (int) $request->get('branch_id') : null;
            
            // Get all the individual data
            $statsResponse = $this->getStats($request);
            $patientStatsResponse = $this->getPatientStatistics(new Request(['days' => 7, 'branch_id' => $branchId]));
            $revenueAnalyticsResponse = $this->getRevenueAnalytics($request);
            $departmentPerformanceResponse = $this->getDepartmentPerformance($request);
            $recentAppointmentsResponse = $this->getRecentAppointments(new Request(['limit' => 10, 'branch_id' => $branchId]));

            // Check if any of the requests failed
            if (!$statsResponse->getData()->success) {
                throw new \Exception('Failed to fetch stats data');
            }

            $dashboardData = [
                'stats' => $statsResponse->getData()->data,
                'patient_statistics' => $patientStatsResponse->getData()->success ? $patientStatsResponse->getData()->data : [],
                'revenue_analytics' => $revenueAnalyticsResponse->getData()->success ? $revenueAnalyticsResponse->getData()->data : [],
                'department_performance' => $departmentPerformanceResponse->getData()->success ? $departmentPerformanceResponse->getData()->data : [],
                'recent_appointments' => $recentAppointmentsResponse->getData()->success ? $recentAppointmentsResponse->getData()->data : [],
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboardData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get doctor dashboard data
     */
    public function getDoctorDashboard(Request $request): JsonResponse
    {
        try {
            $branchId = $request->get('branch_id') ? (int) $request->get('branch_id') : null;
            $doctorId = $request->get('doctor_id') ? (int) $request->get('doctor_id') : null;

            $today = Carbon::today();

            // Doctor-specific stats
            $stats = [
                'today_appointments' => 0,
                'completed_consultations' => 0,
                'pending_consultations' => 0,
                'total_patients_treated' => 0,
                'prescriptions_written' => 0,
                'lab_orders_created' => 0,
                'monthly_patients' => 0,
                'weekly_patients' => 0,
                'dispensed_today' => 0,
                'dispensed_week' => 0,
                'ready_prescriptions' => 0,
                'stock_issues' => 0,
                'lab_results_ready' => 0,
                'lab_orders_pending' => 0,
            ];

            // Get today's appointments for the doctor
            if ($doctorId) {
                $stats['today_appointments'] = DB::table('appointments')
                    ->whereDate('appointment_date', $today)
                    ->where('doctor_id', $doctorId)
                    ->count();

                $stats['completed_consultations'] = DB::table('consultations')
                    ->whereDate('consultation_date', $today)
                    ->where('doctor_id', $doctorId)
                    ->where('consultation_status', 'completed')
                    ->count();

                $stats['pending_consultations'] = DB::table('consultations')
                    ->whereDate('consultation_date', $today)
                    ->where('doctor_id', $doctorId)
                    ->where('consultation_status', 'ongoing')
                    ->count();
            }

            // Get today's appointments for the doctor
            $todayAppointments = [];
            if ($doctorId) {
                $todayAppointments = DB::table('appointments')
                    ->join('patients', 'appointments.patient_id', '=', 'patients.id')
                    ->select(
                        'appointments.id',
                        DB::raw("CONCAT(patients.first_name, ' ', patients.last_name) as patient_name"),
                        'patients.patient_number as patient_id',
                        'appointments.appointment_time',
                        'appointments.appointment_date',
                        'appointments.status',
                        'appointments.appointment_type as consultation_type'
                    )
                    ->whereDate('appointments.appointment_date', $today)
                    ->where('appointments.doctor_id', $doctorId)
                    ->orderBy('appointments.appointment_time')
                    ->limit(10)
                    ->get();
            }

            // Get recent consultations
            $recentConsultations = [];
            if ($doctorId) {
                $recentConsultations = DB::table('consultations')
                    ->join('patients', 'consultations.patient_id', '=', 'patients.id')
                    ->select(
                        'consultations.id',
                        DB::raw("CONCAT(patients.first_name, ' ', patients.last_name) as patient_name"),
                        'patients.patient_number as patient_id',
                        'consultations.consultation_date',
                        'consultations.chief_complaint',
                        DB::raw("'pending' as diagnosis")
                    )
                    ->where('consultations.doctor_id', $doctorId)
                    ->orderBy('consultations.consultation_date', 'desc')
                    ->limit(5)
                    ->get();
            }

            $dashboardData = [
                'stats' => $stats,
                'today_appointments' => $todayAppointments,
                'recent_consultations' => $recentConsultations,
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboardData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch doctor dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get nurse dashboard data
     */
    public function getNurseDashboard(Request $request): JsonResponse
    {
        try {
            $branchId = $request->get('branch_id') ? (int) $request->get('branch_id') : null;
            $nurseId = $request->get('nurse_id') ? (int) $request->get('nurse_id') : null;

            $today = Carbon::today();

            // Nurse-specific stats
            $stats = [
                'patients_assigned' => 0,
                'vitals_recorded' => 0,
                'medications_administered' => 0,
                'pending_tasks' => 0,
                'completed_tasks' => 0,
                'emergency_alerts' => 0,
                'stable_patients' => 0,
                'critical_patients' => 0,
                'normal_vitals' => 0,
                'abnormal_vitals' => 0,
                'medications_ontime' => 0,
                'medications_pending' => 0,
                'high_priority_tasks' => 0,
            ];

            // Get today's tasks for the nurse
            $todayTasks = [];
            if ($nurseId) {
                $todayTasks = DB::table('nurse_tasks')
                    ->join('patients', 'nurse_tasks.patient_id', '=', 'patients.id')
                    ->select(
                        'nurse_tasks.id',
                        DB::raw("CONCAT(patients.first_name, ' ', patients.last_name) as patient_name"),
                        'patients.patient_number as patient_id',
                        'nurse_tasks.task_type',
                        'nurse_tasks.priority',
                        'nurse_tasks.status',
                        'nurse_tasks.due_time'
                    )
                    ->whereDate('nurse_tasks.created_at', $today)
                    ->where('nurse_tasks.assigned_nurse_id', $nurseId)
                    ->orderBy('nurse_tasks.priority', 'desc')
                    ->limit(10)
                    ->get();

                $stats['pending_tasks'] = count($todayTasks->where('status', 'pending'));
                $stats['completed_tasks'] = count($todayTasks->where('status', 'completed'));
                $stats['high_priority_tasks'] = count($todayTasks->where('priority', 'high'));
            }

            // Get emergency alerts
            $emergencyAlerts = DB::table('emergency_alerts')
                ->join('patients', 'emergency_alerts.patient_id', '=', 'patients.id')
                ->select(
                    'emergency_alerts.id',
                    DB::raw("CONCAT(patients.first_name, ' ', patients.last_name) as patient_name"),
                    'patients.patient_number as patient_id',
                    'emergency_alerts.alert_type',
                    'emergency_alerts.created_at as alert_time'
                )
                ->where('emergency_alerts.status', 'active')
                ->orderBy('emergency_alerts.created_at', 'desc')
                ->limit(5)
                ->get();

            $stats['emergency_alerts'] = count($emergencyAlerts);

            $dashboardData = [
                'stats' => $stats,
                'today_tasks' => $todayTasks,
                'emergency_alerts' => $emergencyAlerts,
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboardData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch nurse dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pharmacist dashboard data
     */
    public function getPharmacistDashboard(Request $request): JsonResponse
    {
        try {
            $branchId = $request->get('branch_id') ? (int) $request->get('branch_id') : null;
            $pharmacistId = $request->get('pharmacist_id') ? (int) $request->get('pharmacist_id') : null;

            $today = Carbon::today();

            // Pharmacist-specific stats
            $stats = [
                'prescriptions_dispensed' => 0,
                'pending_prescriptions' => 0,
                'low_stock_items' => 0,
                'expired_items' => 0,
                'revenue_today' => 0,
                'total_inventory_value' => 0,
                'dispensed_today' => 0,
                'dispensed_week' => 0,
                'ready_prescriptions' => 0,
                'stock_issues' => 0,
                'out_of_stock' => 0,
            ];

            // Get pending prescriptions
            $pendingPrescriptions = DB::table('prescriptions')
                ->join('patients', 'prescriptions.patient_id', '=', 'patients.id')
                ->select(
                    'prescriptions.id',
                    DB::raw("CONCAT(patients.first_name, ' ', patients.last_name) as patient_name"),
                    'patients.patient_number as patient_id',
                    'prescriptions.created_at as prescription_date',
                    'prescriptions.status',
                    DB::raw('0 as total_amount'),
                    DB::raw('1 as items_count')
                )
                ->whereIn('prescriptions.status', ['pending', 'ready'])
                ->orderBy('prescriptions.created_at', 'desc')
                ->limit(10)
                ->get();

            $stats['pending_prescriptions'] = count($pendingPrescriptions);
            $stats['ready_prescriptions'] = count($pendingPrescriptions->where('status', 'ready'));

            $alertService = app(PharmacyInventoryAlertService::class);
            $alertCounts = $alertService->getAlertCounts($branchId);

            $lowStockAlerts = DB::table('drug_stocks')
                ->join('drugs', 'drug_stocks.drug_id', '=', 'drugs.id')
                ->select(
                    'drug_stocks.id',
                    'drugs.name as drug_name',
                    'drugs.category as drug_category',
                    'drug_stocks.current_stock',
                    'drugs.unit',
                    DB::raw("CASE WHEN drug_stocks.current_stock = 0 THEN 'out_of_stock' ELSE 'low_stock' END as alert_type")
                )
                ->where('drug_stocks.is_active', true)
                ->where(function ($query) use ($alertService) {
                    $threshold = $alertService->lowStockThreshold();
                    $query->where('drug_stocks.current_stock', 0)
                        ->orWhere(function ($q) {
                            $q->whereNotNull('drug_stocks.reorder_level')
                                ->whereColumn('drug_stocks.current_stock', '<=', 'drug_stocks.reorder_level');
                        })
                        ->orWhere(function ($q) use ($threshold) {
                            $q->whereNull('drug_stocks.reorder_level')
                                ->where('drug_stocks.current_stock', '<=', $threshold);
                        });
                })
                ->when($branchId, fn ($q) => $q->where('drug_stocks.branch_id', $branchId))
                ->orderBy('drug_stocks.current_stock', 'asc')
                ->limit(10)
                ->get();

            $stats['low_stock_items'] = $alertCounts['low_stock'];
            $stats['out_of_stock'] = $alertCounts['out_of_stock'];
            $stats['expired_items'] = $alertCounts['expired'];
            $stats['expiring_soon_items'] = $alertCounts['expiring_soon'];

            $dashboardData = [
                'stats' => $stats,
                'pending_prescriptions' => $pendingPrescriptions,
                'low_stock_alerts' => $lowStockAlerts,
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboardData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pharmacist dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lab technician dashboard data
     */
    public function getLabTechnicianDashboard(Request $request): JsonResponse
    {
        try {
            $branchId = $request->get('branch_id') ? (int) $request->get('branch_id') : null;
            $technicianId = $request->get('technician_id') ? (int) $request->get('technician_id') : null;

            $today = Carbon::today();

            // Lab technician-specific stats
            $stats = [
                'tests_pending' => 0,
                'tests_completed' => 0,
                'tests_rejected' => 0,
                'critical_results' => 0,
                'equipment_maintenance_due' => 0,
                'samples_collected' => 0,
                'high_priority_tests' => 0,
                'due_today' => 0,
                'normal_results' => 0,
                'abnormal_results' => 0,
                'unreported_critical' => 0,
                'reported_critical' => 0,
                'operational_equipment' => 0,
                'out_of_service' => 0,
            ];

            // Get pending tests
            $pendingTests = DB::table('lab_requests')
                ->join('patients', 'lab_requests.patient_id', '=', 'patients.id')
                ->select(
                    'lab_requests.id',
                    DB::raw("CONCAT(patients.first_name, ' ', patients.last_name) as patient_name"),
                    'patients.patient_number as patient_id',
                    'lab_requests.test_name',
                    'lab_requests.test_type',
                    'lab_requests.status',
                    'lab_requests.priority',
                    'lab_requests.created_at as collection_time'
                )
                ->whereIn('lab_requests.status', ['pending', 'sample_collected'])
                ->orderBy('lab_requests.priority', 'desc')
                ->orderBy('lab_requests.created_at', 'asc')
                ->limit(10)
                ->get();

            $stats['tests_pending'] = count($pendingTests);
            $stats['high_priority_tests'] = count($pendingTests->where('priority', 'high'));
            $stats['due_today'] = count($pendingTests->where('created_at', '>=', $today));

            // Get critical results
            $criticalResults = DB::table('lab_results')
                ->join('lab_requests', 'lab_results.lab_request_id', '=', 'lab_requests.id')
                ->join('patients', 'lab_requests.patient_id', '=', 'patients.id')
                ->select(
                    'lab_results.id',
                    DB::raw("CONCAT(patients.first_name, ' ', patients.last_name) as patient_name"),
                    'patients.patient_number as patient_id',
                    'lab_requests.test_name',
                    'lab_results.result_value',
                    'lab_results.unit',
                    'lab_requests.id as test_id'
                )
                ->where('lab_results.is_critical', true)
                ->where('lab_results.status', 'unreported')
                ->orderBy('lab_results.created_at', 'desc')
                ->limit(5)
                ->get();

            $stats['critical_results'] = count($criticalResults);
            $stats['unreported_critical'] = count($criticalResults);

            $dashboardData = [
                'stats' => $stats,
                'pending_tests' => $pendingTests,
                'critical_results' => $criticalResults,
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboardData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch lab technician dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get receptionist dashboard data
     */
    public function getReceptionistDashboard(Request $request): JsonResponse
    {
        try {
            $branchId = $request->get('branch_id') ? (int) $request->get('branch_id') : null;
            $receptionistId = $request->get('receptionist_id') ? (int) $request->get('receptionist_id') : null;

            $today = Carbon::today();

            // Receptionist-specific stats
            $stats = [
                'patients_registered' => 0,
                'appointments_scheduled' => 0,
                'walk_in_patients' => 0,
                'payments_processed' => 0,
                'pending_registrations' => 0,
                'queue_length' => 0,
                'new_patients' => 0,
                'returning_patients' => 0,
                'appointments_today' => 0,
                'appointments_tomorrow' => 0,
                'waiting_patients' => 0,
                'avg_wait_time' => 0,
                'cash_payments' => 0,
                'card_payments' => 0,
            ];

            // Get current queue
            $currentQueue = DB::table('queues')
                ->join('patients', 'queues.patient_id', '=', 'patients.id')
                ->select(
                    'queues.id',
                    DB::raw("CONCAT(patients.first_name, ' ', patients.last_name) as patient_name"),
                    'patients.patient_number as patient_id',
                    'queues.service_type',
                    'queues.arrival_time',
                    'queues.estimated_wait_time as estimated_wait',
                    'queues.priority',
                    'queues.status'
                )
                ->where('queues.status', 'waiting')
                ->orderBy('queues.priority', 'desc')
                ->orderBy('queues.arrival_time', 'asc')
                ->limit(10)
                ->get();

            $stats['queue_length'] = count($currentQueue);
            $stats['waiting_patients'] = count($currentQueue);

            $dashboardData = [
                'stats' => $stats,
                'current_queue' => $currentQueue,
                'pending_registrations' => [],
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboardData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch receptionist dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get accountant dashboard data (uses shared stats service).
     */
    public function getAccountantDashboard(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $statsService = app(DashboardStatsService::class);
            $branchId = $statsService->resolveBranchId(
                $user,
                $request->filled('branch_id') ? (int) $request->get('branch_id') : null
            );

            $stats = $statsService->getAccountantStatistics($user, $branchId);

            $recentTransactions = Payment::with(['patient:id,first_name,last_name,patient_number', 'invoice:id,invoice_number'])
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('status', 'completed')
                ->latest('payment_date')
                ->limit(10)
                ->get();

            $pendingExpenses = Expense::with(['creator:id,first_name,last_name', 'category:id,name'])
                ->where('status', 'pending')
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->latest()
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_transactions' => $recentTransactions,
                    'pending_expenses' => $pendingExpenses,
                    'branch_id' => $branchId,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch accountant dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Role-scoped realtime dashboard data (parity with web /dashboard/realtime-data).
     */
    public function getRealtimeData(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('view_dashboard')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $statsService = app(DashboardStatsService::class);
        $userRole = $statsService->resolveDashboardRole($user);
        $branchId = $statsService->resolveBranchId(
            $user,
            $request->filled('branch_id') ? (int) $request->get('branch_id') : null
        );

        $data = match (strtolower($userRole)) {
            'doctor' => ['statistics' => $statsService->getDoctorStatistics($user, $branchId)],
            'nurse' => ['statistics' => $statsService->getNurseStatistics($user, $branchId)],
            'pharmacist' => ['statistics' => $statsService->getPharmacistStatistics($user, $branchId)],
            'lab technician', 'lab_technician' => ['statistics' => $statsService->getLabTechnicianStatistics($user, $branchId)],
            'radiologist' => ['statistics' => $statsService->getRadiologistStatistics($user, $branchId)],
            'accountant' => [
                'statistics' => $statsService->getAccountantStatistics($user, $branchId),
                'pending_expenses' => Expense::with(['creator:id,first_name,last_name', 'category:id,name'])
                    ->where('status', 'pending')
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->latest()
                    ->limit(5)
                    ->get(),
            ],
            default => [
                'statistics' => $statsService->getAdminStatistics($user, $branchId),
                'quick_stats' => $statsService->getAdminQuickStats($user, $branchId),
            ],
        };

        return response()->json([
            'success' => true,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'user_role' => $userRole,
            'branch_id' => $branchId,
        ]);
    }
}