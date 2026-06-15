<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Visit;
use App\Models\Consultation;
use App\Models\LabRequest;
use App\Models\Prescription;
use App\Models\Invoice;
use App\Models\Expense;
use App\Services\DashboardCacheService;
use App\Services\DashboardStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ResolvesUserBranch;

    public function __construct(
        protected DashboardStatsService $statsService
    ) {}

    /**
     * Show the application dashboard
     * Routes to appropriate dashboard based on user PERMISSIONS, not role names
     * This allows flexible cross-role permission assignment
     * 
     * Note: Super Admin is checked FIRST and gets full admin dashboard
     */
    public function index()
    {
        $user = auth()->user();
        
        // Check if user has dashboard permission
        if (!$user->can('view_dashboard')) {
            abort(403, 'You do not have permission to view the dashboard.');
        }
        
        // Determine dashboard type based on PERMISSIONS (not role names)
        // This allows flexible permission assignment - e.g., lab tech can have receptionist permissions
        
        // Priority 0: Super Admin (ALWAYS FIRST - has all permissions)
        if ($user->hasRole('super_admin')) {
            return $this->adminDashboard($user);
        }

        // Priority 1: Patient portal (before staff permission checks)
        if ($user->hasRole('patient')) {
            return $this->patientDashboard($user);
        }
        
        // Priority 2: Admin (broadest permissions after super admin)
        if ($user->hasRole('admin') || $user->can('manage_roles') || $user->can('manage_system_settings')) {
            return $this->adminDashboard($user);
        }
        
        // Priority 3: Accountant (financial focus)
        if ($user->hasRole('accountant') || ($user->can('view_financial_dashboard') && $user->can('view_revenue_analytics'))) {
            return $this->accountantDashboard($user);
        }
        
        // Priority 4: Doctor (clinical focus)
        if ($user->can('create_consultations') && $user->can('create_prescriptions')) {
            return $this->doctorDashboard($user);
        }
        
        // Priority 5: Nurse (vitals and patient care)
        if ($user->can('record_vitals') && $user->can('create_visits')) {
            return $this->nurseDashboard($user);
        }
        
        // Priority 6: Pharmacist
        if ($user->can('dispense_drugs') && $user->can('view_prescriptions')) {
            return $this->pharmacistDashboard($user);
        }
        
        // Priority 7: Lab Technician
        if ($user->can('process_lab_requests') || $user->can('enter_lab_results')) {
            return $this->labTechnicianDashboard($user);
        }
        
        // Priority 8: Radiologist
        if ($user->can('process_radiology_requests') || $user->can('upload_radiology_results')) {
            return $this->radiologistDashboard($user);
        }
        
        // Default: Admin dashboard (for any other user with dashboard access)
        return $this->adminDashboard($user);
    }
    
    /**
     * Admin Dashboard
     * Statistics are cached for 5 minutes to improve performance
     */
    protected function adminDashboard($user)
    {
        $userRole = 'Admin';
        $branchId = $this->statsService->resolveBranchId($user);

        $statistics = $this->statsService->getAdminStatistics($user, $branchId);
        $recentActivities = $this->getAdminRecentActivities($user, $branchId);
        $chartData = $this->statsService->getChartData($user, $branchId);
        $quickStats = $this->statsService->getAdminQuickStats($user, $branchId);

        return view('dashboard.index', compact('userRole', 'statistics', 'recentActivities', 'chartData', 'quickStats'));
    }
    
    /**
     * Doctor Dashboard
     */
    protected function doctorDashboard($user)
    {
        $userRole = 'Doctor';
        $branchId = $this->statsService->resolveBranchId($user);

        $statistics = $this->statsService->attachRoleRevenueStats(
            $this->statsService->getDoctorStatistics($user, $branchId),
            $user,
            $branchId
        );
        
        // Get recent consultations intelligently - one per patient, prioritizing ongoing/pending
        // First, get all consultations ordered by priority, then group by patient_id to get unique patients
        $recentConsultations = Consultation::with(['patient'])
            ->where('doctor_id', $user->id)
            ->whereHas('patient')
            ->whereIn('consultation_status', ['ongoing', 'pending', 'completed'])
            ->orderByRaw("
                CASE 
                    WHEN consultation_status = 'ongoing' AND is_draft = false THEN 1
                    WHEN consultation_status = 'ongoing' AND is_draft = true THEN 2
                    WHEN consultation_status = 'pending' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('consultation_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('patient_id') // Keep only the first (highest priority) consultation per patient
            ->take(10)
            ->values();

        // Get assigned visits that don't have consultations yet
        $assignedVisits = Visit::with(['patient'])
            ->where('assigned_doctor_id', $user->id)
            ->where('status', 'active')
            ->whereHas('patient')
            ->whereDoesntHave('consultations', function($query) use ($user) {
                $query->where('doctor_id', $user->id)
                      ->whereIn('consultation_status', ['ongoing', 'pending']);
            })
            ->latest('check_in_time')
            ->take(5)
            ->get();

        $recentActivities = [
            'appointments' => Appointment::with(['patient'])
                ->where('doctor_id', $user->id)
                ->whereHas('patient')
                ->latest()
                ->take(10)
                ->get(),
            'consultations' => $recentConsultations,
            'assigned_visits' => $assignedVisits,
        ];
        
        $chartData = $this->statsService->getDoctorChartData($user, $branchId);
        $quickStats = [];
        
        return view('dashboard.index', compact('userRole', 'statistics', 'recentActivities', 'chartData', 'quickStats'));
    }
    
    /**
     * Nurse Dashboard
     */
    protected function nurseDashboard($user)
    {
        $userRole = 'Nurse';
        $branchId = $this->statsService->resolveBranchId($user);

        $statistics = $this->statsService->attachRoleRevenueStats(
            $this->statsService->getNurseStatistics($user, $branchId),
            $user,
            $branchId
        );
        
        $recentActivities = [
            'visits' => Visit::with(['patient', 'assignedDoctor'])
                ->where('assigned_nurse_id', $user->id)
                ->whereHas('patient')
                ->latest()
                ->take(10)
                ->get(),
        ];
        
        $chartData = [];
        $quickStats = [];
        
        return view('dashboard.index', compact('userRole', 'statistics', 'recentActivities', 'chartData', 'quickStats'));
    }
    
    /**
     * Pharmacist Dashboard
     */
    protected function pharmacistDashboard($user)
    {
        $userRole = 'Pharmacist';
        $branchId = $this->statsService->resolveBranchId($user);

        $statistics = $this->statsService->attachRoleRevenueStats(
            $this->statsService->getPharmacistStatistics($user, $branchId),
            $user,
            $branchId
        );
        
        $recentActivities = [
            'prescriptions' => Prescription::with(['patient', 'doctor'])
                ->whereHas('patient')
                ->latest()
                ->take(10)
                ->get(),
            'low_stock' => DB::table('drug_stocks')
                ->join('drugs', 'drug_stocks.drug_id', '=', 'drugs.id')
                ->where('drug_stocks.current_stock', '<', DB::raw('drug_stocks.reorder_level'))
                ->select('drugs.name', 'drug_stocks.current_stock', 'drug_stocks.reorder_level')
                ->orderBy('drug_stocks.current_stock')
                ->take(10)
                ->get(),
            // Add recent pharmacy queue items
            'pharmacy_queues' => \App\Models\Queue::with(['patient:id,patient_number,first_name,last_name', 'visit:id,visit_token'])
                ->where('queue_type', 'Pharmacy')
                ->where('branch_id', $branchId)
                ->whereHas('patient')
                ->latest('queued_at')
                ->take(10)
                ->get(),
        ];
        
        $chartData = [];
        $quickStats = [];
        
        return view('dashboard.index', compact('userRole', 'statistics', 'recentActivities', 'chartData', 'quickStats'));
    }
    
    /**
     * Lab Technician Dashboard
     */
    protected function labTechnicianDashboard($user)
    {
        $userRole = 'Lab Technician';
        $branchId = $this->statsService->resolveBranchId($user);

        $statistics = $this->statsService->attachRoleRevenueStats(
            $this->statsService->getLabTechnicianStatistics($user, $branchId),
            $user,
            $branchId
        );
        
        $recentActivities = [
            'lab_requests' => LabRequest::with([
                'patient:id,first_name,last_name,patient_number',
                'doctor:id,first_name,last_name',
                'creator:id,first_name,last_name',
                'template:id,template_name',
                'branch:id,name'
            ])
                ->whereHas('patient')
                ->latest()
                ->take(10)
                ->get(),
            // Add recent lab queue items
            'lab_queues' => \App\Models\Queue::with(['patient:id,patient_number,first_name,last_name', 'visit:id,visit_token'])
                ->where('queue_type', 'Lab')
                ->where('branch_id', $branchId)
                ->whereHas('patient')
                ->latest('queued_at')
                ->take(10)
                ->get(),
        ];
        
        $chartData = [];
        $quickStats = [];
        
        return view('dashboard.index', compact('userRole', 'statistics', 'recentActivities', 'chartData', 'quickStats'));
    }
    
    /**
     * Get statistics based on user role
     */
    protected function getStatistics($user)
    {
        $branchId = $this->statsService->resolveBranchId($user);

        return $this->statsService->getAdminStatistics($user, $branchId);
    }
    
    /**
     * Get recent activities
     */
    protected function getRecentActivities($user)
    {
        // Get recent appointments
        $recentAppointments = Appointment::with(['patient', 'doctor'])
            ->whereHas('patient')
            ->latest()
            ->take(5)
            ->get();
        
        return [
            'appointments' => $recentAppointments,
        ];
    }

    /**
     * Get real-time dashboard data via AJAX
     */
    public function getRealtimeData(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->can('view_dashboard')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $userRole = $this->statsService->resolveDashboardRole($user);
        $branchId = $this->statsService->resolveBranchId(
            $user,
            $request->filled('branch_id') ? (int) $request->get('branch_id') : null
        );

        $data = $this->getRoleSpecificRealtimeData($user, $userRole, $branchId);
        
        return response()->json([
            'success' => true,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'user_role' => $userRole,
            'cached' => false,
        ]);
    }

    /**
     * Get role-specific real-time data
     */
    protected function getRoleSpecificRealtimeData($user, $userRole, $branchId)
    {
        switch (strtolower($userRole)) {
            case 'doctor':
                return $this->getDoctorRealtimeData($user, $branchId);
            case 'nurse':
                return $this->getNurseRealtimeData($user, $branchId);
            case 'pharmacist':
                return $this->getPharmacistRealtimeData($user, $branchId);
            case 'lab technician':
            case 'lab_technician':
                return $this->getLabTechnicianRealtimeData($user, $branchId);
            case 'radiologist':
                return $this->getRadiologistRealtimeData($user, $branchId);
            case 'accountant':
                return $this->getAccountantRealtimeData($user, $branchId);
            default:
                return $this->getAdminRealtimeData($user, $branchId);
        }
    }

    /**
     * Get admin real-time data
     */
    protected function getAdminRealtimeData($user, $branchId)
    {
        return [
            'statistics' => $this->statsService->getAdminStatistics($user, $branchId),
            'quick_stats' => $this->statsService->getAdminQuickStats($user, $branchId),
            'recent_activities' => $this->getAdminRecentActivities($user, $branchId),
            'chart_data' => $this->statsService->getChartData($user, $branchId),
            'queue_status' => $this->statsService->getQueueStatus($branchId),
            'notifications' => $this->getNotifications($user),
        ];
    }

    /**
     * Get doctor real-time data
     */
    protected function getDoctorRealtimeData($user, $branchId)
    {
        return [
            'statistics' => $this->statsService->getDoctorStatistics($user, $branchId),
            'recent_activities' => [
                'consultations' => Consultation::with(['patient:id,first_name,last_name'])
                    ->where('doctor_id', $user->id)
                    ->whereHas('patient')
                    ->latest()
                    ->take(10)
                    ->get(),
                'assigned_visits' => Visit::with(['patient:id,first_name,last_name'])
                    ->where('assigned_doctor_id', $user->id)
                    ->whereHas('patient')
                    ->latest()
                    ->take(10)
                    ->get(),
            ],
        ];
    }

    /**
     * Get nurse real-time data
     */
    protected function getNurseRealtimeData($user, $branchId)
    {
        return [
            'statistics' => $this->statsService->getNurseStatistics($user, $branchId),
            'recent_activities' => [
                'visits' => Visit::with(['patient:id,first_name,last_name'])
                    ->where('assigned_nurse_id', $user->id)
                    ->whereHas('patient')
                    ->latest()
                    ->take(10)
                    ->get(),
            ],
        ];
    }

    /**
     * Get pharmacist real-time data
     */
    protected function getPharmacistRealtimeData($user, $branchId)
    {
        return [
            'statistics' => $this->statsService->getPharmacistStatistics($user, $branchId),
            'recent_activities' => [
                'prescriptions' => Prescription::with(['patient:id,first_name,last_name'])
                    ->whereHas('patient')
                    ->latest()
                    ->take(10)
                    ->get(),
            ],
        ];
    }

    /**
     * Get lab technician real-time data
     */
    protected function getLabTechnicianRealtimeData($user, $branchId)
    {
        $branchId = $branchId ?? $this->statsService->resolveBranchId($user);

        return [
            'statistics' => $this->statsService->getLabTechnicianStatistics($user, $branchId),
            'recent_activities' => [
                'lab_requests' => LabRequest::with([
                    'patient:id,first_name,last_name,patient_number',
                    'doctor:id,first_name,last_name',
                    'creator:id,first_name,last_name',
                    'template:id,template_name',
                    'branch:id,name'
                ])
                    ->whereHas('patient')
                    ->latest()
                    ->take(10)
                    ->get(),
                'lab_queues' => \App\Models\Queue::with(['patient:id,patient_number,first_name,last_name', 'visit:id,visit_token'])
                    ->where('queue_type', 'Lab')
                    ->where('branch_id', $branchId)
                    ->whereHas('patient')
                    ->latest('queued_at')
                    ->take(10)
                    ->get(),
            ],
        ];
    }

    /**
     * Get accountant real-time data
     */
    protected function getAccountantRealtimeData($user, $branchId)
    {
        return [
            'statistics' => $this->statsService->getAccountantStatistics($user, $branchId),
            'recent_activities' => [
                'invoices' => Invoice::with(['patient:id,first_name,last_name'])
                    ->when($branchId, fn ($query) => $this->statsService->applyBranchScope($query, $branchId))
                    ->whereHas('patient')
                    ->latest()
                    ->take(10)
                    ->get(),
                'debtors' => \App\Models\Debtor::with(['patient:id,first_name,last_name'])
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->where('total_outstanding', '>', 0)
                    ->orderBy('total_outstanding', 'desc')
                    ->take(10)
                    ->get(),
                'pending_expenses' => Expense::with(['creator:id,first_name,last_name', 'category:id,name'])
                    ->where('status', 'pending')
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->latest()
                    ->take(5)
                    ->get(),
            ],
        ];
    }

    /**
     * Get queue status for real-time updates
     */
    protected function getAdminRecentActivities($user, ?int $branchId): array
    {
        $activities = [];

        if ($user->can('view_appointments')) {
            $appointmentQuery = Appointment::with(['patient:id,patient_number,first_name,last_name', 'doctor:id,first_name,last_name'])
                ->whereHas('patient');
            $this->statsService->applyBranchScope($appointmentQuery, $branchId);
            $activities['appointments'] = $appointmentQuery->latest()->take(5)->get();
        }

        if ($user->can('view_visits')) {
            $visitQuery = Visit::with(['patient:id,patient_number,first_name,last_name'])
                ->whereHas('patient');
            $this->statsService->applyBranchScope($visitQuery, $branchId);
            $activities['visits'] = $visitQuery->latest()->take(5)->get();
        }

        return $activities;
    }
    
    /**
     * Get radiologist real-time data
     */
    protected function getRadiologistRealtimeData($user, $branchId)
    {
        $branchId = $branchId ?? $this->statsService->resolveBranchId($user);

        return [
            'statistics' => $this->statsService->getRadiologistStatistics($user, $branchId),
            'recent_activities' => [
                'studies' => \App\Models\RadiologyStudy::with([
                    'patient:id,first_name,last_name,patient_number',
                    'request.doctor:id,first_name,last_name',
                    'modality:id,name,code',
                    'request:id,priority,clinical_question',
                ])
                    ->whereHas('patient')
                    ->latest()
                    ->take(10)
                    ->get(),
                'radiology_queues' => \App\Models\Queue::with(['patient:id,patient_number,first_name,last_name', 'visit:id,visit_token'])
                    ->where('queue_type', 'Radiology')
                    ->where('branch_id', $branchId)
                    ->whereHas('patient')
                    ->latest('queued_at')
                    ->take(10)
                    ->get(),
            ],
        ];
    }
    
    /**
     * Get real-time notifications
     */
    protected function getNotifications($user)
    {
        $cacheService = new DashboardCacheService();
        return $cacheService->getNotifications($user);
    }

    /**
     * Clear dashboard cache
     */
    public function clearCache(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->can('view_dashboard')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $cacheService = new DashboardCacheService();
        
        if ($request->has('user_id')) {
            $cacheService->clearUserCache($request->user_id);
            $message = 'User cache cleared successfully';
        } else {
            $cacheService->clearAllDashboardCache();
            $message = 'All dashboard cache cleared successfully';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->can('view_dashboard')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $cacheService = new DashboardCacheService();
        $stats = $cacheService->getCacheStats();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get chart data
     */
    protected function getChartData($user)
    {
        $branchId = $this->statsService->resolveBranchId($user);

        return $this->statsService->getChartData($user, $branchId);
    }
    
    /**
     * Get quick stats for dashboard cards
     */
    protected function getQuickStats($user)
    {
        $branchId = $this->statsService->resolveBranchId($user);

        return $this->statsService->getAdminQuickStats($user, $branchId);
    }
    
    /**
     * Accountant Dashboard
     */
    protected function accountantDashboard($user)
    {
        $userRole = 'Accountant';
        $branchId = $this->statsService->resolveBranchId($user);

        $statistics = $this->statsService->getAccountantStatistics($user, $branchId);
        
        $recentActivities = [
            'invoices' => Invoice::with(['patient'])
                ->when($branchId, fn ($query) => $this->statsService->applyBranchScope($query, $branchId))
                ->whereHas('patient')
                ->latest()
                ->take(10)
                ->get(),
            'debtors' => \App\Models\Debtor::with(['patient'])
                ->when($branchId, function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->where('total_outstanding', '>', 0)
                ->orderBy('total_outstanding', 'desc')
                ->take(10)
                ->get(),
            'pending_expenses' => Expense::with(['creator:id,first_name,last_name', 'category:id,name'])
                ->where('status', 'pending')
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->latest()
                ->take(5)
                ->get(),
        ];
        
        $chartData = $this->getChartData($user);
        $quickStats = $this->getQuickStats($user);
        
        return view('dashboard.index', compact('userRole', 'statistics', 'recentActivities', 'chartData', 'quickStats'));
    }
    
    /**
     * Radiologist Dashboard
     */
    protected function radiologistDashboard($user)
    {
        $userRole = 'Radiologist';
        $branchId = $this->statsService->resolveBranchId($user);

        $statistics = $this->statsService->attachRoleRevenueStats(
            $this->statsService->getRadiologistStatistics($user, $branchId),
            $user,
            $branchId
        );
        
        $recentActivities = [
            'studies' => \App\Models\RadiologyStudy::with([
                'patient:id,first_name,last_name,patient_number',
                'request.doctor:id,first_name,last_name',
                'modality:id,name,code',
                'request:id,priority,clinical_question',
            ])
                ->whereHas('patient')
                ->latest()
                ->take(10)
                ->get(),
            // Add recent radiology queue items
            'radiology_queues' => \App\Models\Queue::with(['patient:id,patient_number,first_name,last_name', 'visit:id,visit_token'])
                ->where('queue_type', 'Radiology')
                ->where('branch_id', $branchId)
                ->whereHas('patient')
                ->latest('queued_at')
                ->take(10)
                ->get(),
        ];
        
        $chartData = [];
        $quickStats = [];
        
        return view('dashboard.index', compact('userRole', 'statistics', 'recentActivities', 'chartData', 'quickStats'));
    }
    
    /**
     * Get chart data for doctor dashboard
     */
    protected function getDoctorChartData($user)
    {
        $branchId = $this->statsService->resolveBranchId($user);

        return $this->statsService->getDoctorChartData($user, $branchId);
    }
    
    /**
     * Patient Dashboard
     * Shows patient-specific information and appointments
     */
    protected function patientDashboard($user)
    {
        $userRole = 'Patient';
        
        // Get patient record
        $patient = Patient::where('user_id', $user->id)->first();
        
        if (!$patient) {
            // If no patient record exists, create one
            $patient = Patient::create([
                'user_id' => $user->id,
                'first_name' => $user->first_name ?? 'Patient',
                'last_name' => $user->last_name ?? 'User',
                'patient_number' => 'PAT/' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                'gender' => 'Male', // Default
                'date_of_birth' => '1990-01-01', // Default
                'phone' => $user->phone ?? '',
                'email' => $user->email,
                'branch_id' => 1, // Default branch
                'registration_source' => 'web', // Tag as registered from web
                'created_by' => $user->id,
            ]);
            try {
                app(\App\Services\RegistrationFeeService::class)->createInvoiceForPatient($patient, $patient->branch_id);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Registration fee invoice creation failed for new patient', ['patient_id' => $patient->id, 'error' => $e->getMessage()]);
            }
        }
        
        $statistics = $this->statsService->getPatientStatistics($user, $patient);
        
        // Get recent activities
        $recentActivities = [
            'appointments' => Appointment::where('patient_id', $patient->id)
                ->with(['doctor'])
                ->latest()
                ->take(5)
                ->get(),
            'consultations' => Consultation::where('patient_id', $patient->id)
                ->with(['doctor'])
                ->latest()
                ->take(5)
                ->get(),
            'lab_requests' => LabRequest::where('patient_id', $patient->id)
                ->with(['testType.template', 'testType.category'])
                ->latest()
                ->take(5)
                ->get(),
            'prescriptions' => Prescription::where('patient_id', $patient->id)
                ->with(['doctor'])
                ->latest()
                ->take(5)
                ->get(),
        ];
        
        // Get upcoming appointments
        $upcomingAppointments = Appointment::where('patient_id', $patient->id)
            ->where('appointment_date', '>=', now())
            ->where('status', 'scheduled')
            ->with(['doctor'])
            ->orderBy('appointment_date')
            ->take(3)
            ->get();
        
        // Get recent lab results
        // Only show lab results that are verified and approved
        $recentLabResults = LabRequest::where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->whereHas('results', function($q) {
                $q->whereNotNull('result_verified_at')
                  ->whereNotNull('result_approved_at')
                  ->whereNotNull('result_entered_at');
            })
            ->with([
                'testType.template', 
                'testType.category', 
                'results' => function($q) {
                    // Only load verified and approved results
                    $q->whereNotNull('result_verified_at')
                      ->whereNotNull('result_approved_at')
                      ->whereNotNull('result_entered_at')
                      ->orderBy('parameter_id');
                }
            ])
            ->latest()
            ->take(3)
            ->get();
        
        return view('dashboard.patient', compact(
            'userRole', 
            'statistics', 
            'recentActivities', 
            'upcomingAppointments', 
            'recentLabResults',
            'patient'
        ));
    }
}
