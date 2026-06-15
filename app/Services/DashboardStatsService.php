<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\DrugStock;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\LabRequest;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Queue;
use App\Models\RadiologyReport;
use App\Models\RadiologyStudy;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    public function resolveBranchId(User $user, ?int $requestedBranchId = null): ?int
    {
        if ($user->hasRole('super_admin')) {
            return $requestedBranchId;
        }

        $branchId = $user->staffProfile?->branch_id
            ?? $user->patient?->branch_id
            ?? $user->branches()->first()?->id
            ?? ($user->current_branch_id ?? null)
            ?? session('user_branch_id');

        if (!$branchId && $user->can('view_dashboard')) {
            $branchId = app(BranchAssignmentService::class)
                ->resolveBranchId(null, $user, $user->roles->pluck('name')->all());
        }

        return $branchId ? (int) $branchId : null;
    }

    public function applyBranchScope(Builder|\Illuminate\Database\Query\Builder $query, ?int $branchId, string $column = 'branch_id'): Builder|\Illuminate\Database\Query\Builder
    {
        if ($branchId !== null) {
            $query->where($column, $branchId);
        }

        return $query;
    }

    public function getAdminStatistics(User $user, ?int $branchId = null): array
    {
        $branchId = $this->resolveBranchId($user, $branchId);
        $today = Carbon::today();

        $stats = [];

        if ($user->can('view_patients')) {
            $stats['total_patients'] = $this->applyBranchScope(Patient::query(), $branchId)->count();
        }

        if ($user->can('view_users')) {
            if ($branchId !== null) {
                $stats['total_users'] = DB::table('facility_users')
                    ->where('branch_id', $branchId)
                    ->where('is_active', true)
                    ->distinct('user_id')
                    ->count('user_id');
            } else {
                $stats['total_users'] = User::count();
            }
        }

        if ($user->can('view_branches')) {
            $stats['total_branches'] = $branchId !== null
                ? 1
                : DB::table('branches')->where('is_active', true)->count();
        }

        if ($user->can('view_visits')) {
            $stats['active_visits'] = $this->applyBranchScope(
                Visit::where('status', 'active'),
                $branchId
            )->count();
        }

        if ($user->can('view_appointments')) {
            $stats['today_appointments'] = $this->applyBranchScope(
                Appointment::whereDate('appointment_date', $today),
                $branchId
            )->count();
        }

        if ($user->can('view_invoices')) {
            $stats['pending_invoices'] = $this->applyBranchScope(
                Invoice::where('status', 'pending'),
                $branchId
            )->count();
        }

        $stats = array_merge($stats, app(RevenueReportService::class)->getDashboardRevenue($user, $branchId));

        return $stats;
    }

    public function getAdminQuickStats(User $user, ?int $branchId = null): array
    {
        $branchId = $this->resolveBranchId($user, $branchId);
        $today = Carbon::today();

        $stats = [];

        if ($user->can('view_visits')) {
            $stats['today_opd'] = $this->applyBranchScope(
                Visit::whereDate('created_at', $today)->where('visit_type', 'OPD'),
                $branchId
            )->count();

            $stats['today_ipd'] = $this->applyBranchScope(
                Visit::whereDate('created_at', $today)->where('visit_type', 'IPD'),
                $branchId
            )->count();
        }

        if ($user->can('view_lab_requests')) {
            $stats['today_lab'] = $this->applyBranchScope(
                LabRequest::whereDate('created_at', $today),
                $branchId
            )->count();
        }

        if ($user->can('view_prescriptions')) {
            $stats['today_pharmacy'] = $this->applyBranchScope(
                Prescription::whereDate('created_at', $today),
                $branchId
            )->count();
        }

        return $stats;
    }

    public function getDoctorStatistics(User $user, ?int $branchId = null): array
    {
        $branchId = $this->resolveBranchId($user, $branchId);
        $today = Carbon::today();

        $appointmentQuery = fn () => Appointment::where('doctor_id', $user->id);
        $consultationQuery = fn () => Consultation::where('doctor_id', $user->id);
        $visitQuery = fn () => Visit::where('assigned_doctor_id', $user->id);

        if ($branchId !== null) {
            $appointmentQuery = fn () => $this->applyBranchScope(Appointment::where('doctor_id', $user->id), $branchId);
            $consultationQuery = fn () => $this->applyBranchScope(Consultation::where('doctor_id', $user->id), $branchId);
            $visitQuery = fn () => $this->applyBranchScope(Visit::where('assigned_doctor_id', $user->id), $branchId);
        }

        return [
            'my_appointments_today' => $appointmentQuery()->whereDate('appointment_date', $today)->count(),
            'my_consultations_today' => $consultationQuery()->whereDate('consultation_date', $today)->count(),
            'total_consultations' => $consultationQuery()->count(),
            'pending_consultations' => $consultationQuery()
                ->where('consultation_status', 'ongoing')
                ->where('is_draft', true)
                ->count(),
            'completed_consultations' => $consultationQuery()->where('consultation_status', 'completed')->count(),
            'assigned_visits_today' => $visitQuery()->whereDate('check_in_time', $today)->count(),
            'active_assigned_visits' => $visitQuery()->where('status', 'active')->count(),
            'total_visits_assigned' => $visitQuery()->count(),
            'assigned_visits' => $visitQuery()->where('status', 'active')->count(),
            'my_patients' => $consultationQuery()->distinct()->count('patient_id'),
            'total_appointments' => $appointmentQuery()->count(),
        ];
    }

    public function getNurseStatistics(User $user, ?int $branchId = null): array
    {
        $branchId = $this->resolveBranchId($user, $branchId);
        $today = Carbon::today();

        $patientQuery = Patient::query();
        $visitQuery = Visit::query();
        $consultationQuery = Consultation::query();

        if ($branchId !== null) {
            $this->applyBranchScope($patientQuery, $branchId);
            $this->applyBranchScope($visitQuery, $branchId);
            $this->applyBranchScope($consultationQuery, $branchId);
        }

        $assignedVisitQuery = (clone $visitQuery)
            ->where('assigned_nurse_id', $user->id)
            ->where('status', 'active');

        return [
            'total_patients' => $patientQuery->count(),
            'active_visits' => (clone $visitQuery)->where('status', 'active')->count(),
            'vitals_pending' => (clone $consultationQuery)
                ->whereDoesntHave('vitals')
                ->where('consultation_status', 'ongoing')
                ->count(),
            'assigned_visits' => $assignedVisitQuery->count(),
            'my_vitals_today' => DB::table('vitals')
                ->where('recorded_by', $user->id)
                ->whereDate('created_at', $today)
                ->count(),
            'my_pending_expenses' => $this->countMyPendingExpenses($user, $branchId),
        ];
    }

    public function getPharmacistStatistics(User $user, ?int $branchId = null): array
    {
        $branchId = $this->resolveBranchId($user, $branchId);
        $today = Carbon::today();

        $prescriptionQuery = Prescription::query();
        $stockQuery = DrugStock::query();

        if ($branchId !== null) {
            $this->applyBranchScope($prescriptionQuery, $branchId);
            $this->applyBranchScope($stockQuery, $branchId);
        }

        $queueBase = Queue::where('queue_type', 'Pharmacy');
        if ($branchId !== null) {
            $queueBase->where('branch_id', $branchId);
        }

        return [
            'pending_prescriptions' => (clone $prescriptionQuery)->where('status', 'pending')->count(),
            'low_stock_drugs' => (clone $stockQuery)
                ->whereColumn('current_stock', '<', 'reorder_level')
                ->count(),
            'out_of_stock' => (clone $stockQuery)->where('current_stock', 0)->count(),
            'dispensed_today' => (clone $prescriptionQuery)
                ->whereDate('updated_at', $today)
                ->where('status', 'dispensed')
                ->count(),
            'pharmacy_queue_waiting' => (clone $queueBase)->where('status', 'waiting')->count(),
            'pharmacy_queue_serving' => (clone $queueBase)->where('status', 'serving')->count(),
            'my_pending_expenses' => $this->countMyPendingExpenses($user, $branchId),
        ];
    }

    public function getLabTechnicianStatistics(User $user, ?int $branchId = null): array
    {
        $branchId = $this->resolveBranchId($user, $branchId);
        $today = Carbon::today();

        $labQuery = LabRequest::query();
        $patientQuery = Patient::query();

        if ($branchId !== null) {
            $this->applyBranchScope($labQuery, $branchId);
            $this->applyBranchScope($patientQuery, $branchId);
        }

        $queueBase = Queue::where('queue_type', 'Lab');
        if ($branchId !== null) {
            $queueBase->where('branch_id', $branchId);
        }

        return [
            'pending_tests' => (clone $labQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $labQuery)->where('status', 'in_progress')->count(),
            'completed_today' => (clone $labQuery)
                ->whereDate('completed_at', $today)
                ->where('status', 'completed')
                ->count(),
            'total_tests' => (clone $labQuery)->count(),
            'total_patients' => $patientQuery->count(),
            'completed_this_week' => (clone $labQuery)
                ->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->where('status', 'completed')
                ->count(),
            'completed_this_month' => (clone $labQuery)
                ->whereMonth('completed_at', now()->month)
                ->whereYear('completed_at', now()->year)
                ->where('status', 'completed')
                ->count(),
            'lab_queue_waiting' => (clone $queueBase)->where('status', 'waiting')->count(),
            'lab_queue_serving' => (clone $queueBase)->where('status', 'serving')->count(),
            'my_pending_expenses' => $this->countMyPendingExpenses($user, $branchId),
        ];
    }

    public function getRadiologistStatistics(User $user, ?int $branchId = null): array
    {
        $branchId = $this->resolveBranchId($user, $branchId);
        $today = Carbon::today();

        $studyQuery = RadiologyStudy::query();
        $reportQuery = RadiologyReport::query();

        if ($branchId !== null) {
            $studyQuery->where(function ($query) use ($branchId) {
                $query->whereHas('request', fn ($q) => $q->where('branch_id', $branchId))
                    ->orWhereHas('patient', fn ($q) => $q->where('branch_id', $branchId));
            });

            $reportQuery->whereHas('study', function ($query) use ($branchId) {
                $query->where(function ($inner) use ($branchId) {
                    $inner->whereHas('request', fn ($q) => $q->where('branch_id', $branchId))
                        ->orWhereHas('patient', fn ($q) => $q->where('branch_id', $branchId));
                });
            });
        }

        $queueBase = Queue::where('queue_type', 'Radiology');
        if ($branchId !== null) {
            $queueBase->where('branch_id', $branchId);
        }

        return [
            'pending_studies' => (clone $studyQuery)
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->count(),
            'awaiting_reports' => (clone $studyQuery)
                ->where('status', 'completed')
                ->whereDoesntHave('report')
                ->count(),
            'drafts_to_sign' => (clone $reportQuery)
                ->whereIn('status', ['draft', 'preliminary'])
                ->count(),
            'completed_today' => (clone $reportQuery)
                ->whereDate('signed_date', $today)
                ->where('status', 'final')
                ->count(),
            'total_studies' => (clone $studyQuery)->count(),
            'critical_findings' => (clone $reportQuery)
                ->where('status', 'final')
                ->where('findings', 'like', '%critical%')
                ->whereDate('signed_date', '>=', now()->subDays(7))
                ->count(),
            'radiology_queue_waiting' => (clone $queueBase)->where('status', 'waiting')->count(),
            'radiology_queue_serving' => (clone $queueBase)->where('status', 'serving')->count(),
            'my_pending_expenses' => $this->countMyPendingExpenses($user, $branchId),
        ];
    }

    public function getAccountantStatistics(User $user, ?int $branchId = null): array
    {
        $branchId = $this->resolveBranchId($user, $branchId);

        $invoiceQuery = Invoice::query();
        if ($branchId !== null) {
            $this->applyBranchScope($invoiceQuery, $branchId);
        }

        $debtStatistics = app(DebtorService::class)->getDebtorStatistics($branchId);

        $revenueStats = app(RevenueReportService::class)->getDashboardRevenue($user, $branchId);

        return array_merge([
            'pending_invoices' => (clone $invoiceQuery)->where('status', 'pending')->count(),
            'total_invoices' => (clone $invoiceQuery)->count(),
            'total_debtors' => $debtStatistics['total_debtors'],
            'total_outstanding' => $debtStatistics['total_outstanding'],
            'overdue_debtors' => $debtStatistics['overdue_debtors'],
            'critical_debtors' => $debtStatistics['critical_debtors'],
            'collection_rate' => $debtStatistics['collection_rate'],
            'pending_expense_approvals' => $this->countPendingExpenseApprovals($branchId),
        ], $revenueStats);
    }

    public function attachRoleRevenueStats(array $stats, User $user, ?int $branchId): array
    {
        return array_merge($stats, app(RevenueReportService::class)->getDashboardRevenue($user, $branchId));
    }

    public function countMyPendingExpenses(User $user, ?int $branchId = null): int
    {
        if (!$user->can('view_own_expenses')) {
            return 0;
        }

        return Expense::query()
            ->ownedBy($user->id)
            ->where('status', 'pending')
            ->when($branchId !== null, fn ($q) => $q->where('branch_id', $branchId))
            ->count();
    }

    public function countPendingExpenseApprovals(?int $branchId = null): int
    {
        return Expense::query()
            ->where('status', 'pending')
            ->when($branchId !== null, fn ($q) => $q->where('branch_id', $branchId))
            ->count();
    }

    public function getPatientStatistics(User $user, Patient $patient): array
    {
        return [
            'total_appointments' => Appointment::where('patient_id', $patient->id)->count(),
            'upcoming_appointments' => Appointment::where('patient_id', $patient->id)
                ->where('appointment_date', '>=', now())
                ->where('status', 'scheduled')
                ->count(),
            'completed_consultations' => Consultation::where('patient_id', $patient->id)
                ->where('consultation_status', 'completed')
                ->count(),
            'pending_prescriptions' => Prescription::where('patient_id', $patient->id)
                ->where('status', 'pending')
                ->count(),
            'lab_results_available' => LabRequest::where('patient_id', $patient->id)
                ->where('status', 'completed')
                ->count(),
            'total_visits' => Visit::where('patient_id', $patient->id)->count(),
        ];
    }

    public function getChartData(User $user, ?int $branchId = null): array
    {
        $branchId = $this->resolveBranchId($user, $branchId);
        $labels = [];
        $appointmentData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('M d');

            $query = Appointment::whereDate('appointment_date', $date);
            $this->applyBranchScope($query, $branchId);
            $appointmentData[] = $query->count();
        }

        return [
            'labels' => $labels,
            'appointments' => $appointmentData,
        ];
    }

    public function getDoctorChartData(User $user, ?int $branchId = null): array
    {
        $branchId = $this->resolveBranchId($user, $branchId);
        $labels = [];
        $consultationData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('M d');

            $query = Consultation::where('doctor_id', $user->id)
                ->whereDate('consultation_date', $date);
            $this->applyBranchScope($query, $branchId);
            $consultationData[] = $query->count();
        }

        return [
            'labels' => $labels,
            'consultations' => $consultationData,
        ];
    }

    public function getQueueStatus(?int $branchId): array
    {
        if (!$branchId) {
            return [];
        }

        $types = ['OPD', 'Lab', 'Pharmacy', 'Radiology'];
        $status = [];

        foreach ($types as $type) {
            $key = strtolower($type);
            $status["{$key}_waiting"] = Queue::where('queue_type', $type)
                ->where('branch_id', $branchId)
                ->where('status', 'waiting')
                ->count();
            $status["{$key}_serving"] = Queue::where('queue_type', $type)
                ->where('branch_id', $branchId)
                ->where('status', 'serving')
                ->count();
        }

        return $status;
    }

    public function resolveDashboardRole(User $user): string
    {
        if ($user->hasRole('super_admin')) {
            return 'Admin';
        }

        if ($user->hasRole('patient')) {
            return 'Patient';
        }

        if ($user->hasRole('admin') || $user->can('manage_roles') || $user->can('manage_system_settings')) {
            return 'Admin';
        }

        if ($user->hasRole('accountant') || ($user->can('view_financial_dashboard') && $user->can('view_revenue_analytics'))) {
            return 'Accountant';
        }

        if ($user->can('create_consultations') && $user->can('create_prescriptions')) {
            return 'Doctor';
        }

        if ($user->can('record_vitals') && $user->can('create_visits')) {
            return 'Nurse';
        }

        if ($user->can('dispense_drugs') && $user->can('view_prescriptions')) {
            return 'Pharmacist';
        }

        if ($user->can('process_lab_requests') || $user->can('enter_lab_results')) {
            return 'Lab Technician';
        }

        if ($user->can('process_radiology_requests') || $user->can('upload_radiology_results')) {
            return 'Radiologist';
        }

        return 'Admin';
    }
}
