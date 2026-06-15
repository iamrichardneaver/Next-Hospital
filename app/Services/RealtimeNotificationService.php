<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Models\Queue;
use App\Models\LabRequest;
use App\Models\Prescription;
use App\Models\Visit;
use App\Services\PharmacyInventoryAlertService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RealtimeNotificationService
{
    /**
     * Get real-time notifications for a user
     */
    public function getNotificationsForUser(User $user): array
    {
        $notifications = [];
        $userRole = $user->roles->first()->name ?? 'User';
        $branchId = $user->staffProfile?->branch_id;

        // Role-specific notifications
        switch (strtolower($userRole)) {
            case 'doctor':
                $notifications = array_merge($notifications, $this->getDoctorNotifications($user, $branchId));
                break;
            case 'nurse':
                $notifications = array_merge($notifications, $this->getNurseNotifications($user, $branchId));
                break;
            case 'pharmacist':
                $notifications = array_merge($notifications, $this->getPharmacistNotifications($user, $branchId));
                break;
            case 'lab technician':
            case 'lab_technician':
                $notifications = array_merge($notifications, $this->getLabTechnicianNotifications($user, $branchId));
                break;
            case 'accountant':
                $notifications = array_merge($notifications, $this->getAccountantNotifications($user, $branchId));
                break;
            default:
                $notifications = array_merge($notifications, $this->getAdminNotifications($user, $branchId));
        }

        // System-wide notifications
        $notifications = array_merge($notifications, $this->getSystemNotifications($user, $branchId));

        return array_slice($notifications, 0, 10); // Limit to 10 notifications
    }

    /**
     * Get doctor-specific notifications
     */
    protected function getDoctorNotifications(User $user, $branchId): array
    {
        $notifications = [];

        // Pending consultations
        $pendingConsultations = DB::table('consultations')
            ->where('doctor_id', $user->id)
            ->where('consultation_status', 'pending')
            ->count();

        if ($pendingConsultations > 0) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'bi-clipboard2-pulse',
                'title' => 'Pending Consultations',
                'message' => "You have {$pendingConsultations} pending consultations",
                'timestamp' => now(),
                'priority' => 'high'
            ];
        }

        // Overdue appointments
        $overdueAppointments = DB::table('appointments')
            ->where('doctor_id', $user->id)
            ->where('appointment_date', '<', now())
            ->where('status', 'scheduled')
            ->count();

        if ($overdueAppointments > 0) {
            $notifications[] = [
                'type' => 'danger',
                'icon' => 'bi-clock-history',
                'title' => 'Overdue Appointments',
                'message' => "You have {$overdueAppointments} overdue appointments",
                'timestamp' => now(),
                'priority' => 'high'
            ];
        }

        return $notifications;
    }

    /**
     * Get nurse-specific notifications
     */
    protected function getNurseNotifications(User $user, $branchId): array
    {
        $notifications = [];

        // Pending vitals
        $pendingVitals = DB::table('visits')
            ->where('assigned_nurse_id', $user->id)
            ->where('status', 'active')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('vitals')
                    ->whereColumn('vitals.visit_id', 'visits.id');
            })
            ->count();

        if ($pendingVitals > 0) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'bi-heart-pulse',
                'title' => 'Pending Vitals',
                'message' => "You have {$pendingVitals} patients waiting for vitals",
                'timestamp' => now(),
                'priority' => 'high'
            ];
        }

        return $notifications;
    }

    /**
     * Get pharmacist-specific notifications
     */
    protected function getPharmacistNotifications(User $user, $branchId): array
    {
        $notifications = [];

        // Pending prescriptions
        $pendingPrescriptions = Prescription::where('status', 'pending')->count();

        if ($pendingPrescriptions > 0) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'bi-capsule',
                'title' => 'Pending Prescriptions',
                'message' => "You have {$pendingPrescriptions} prescriptions to dispense",
                'timestamp' => now(),
                'priority' => 'medium'
            ];
        }

        $alertService = app(PharmacyInventoryAlertService::class);
        $counts = $alertService->getAlertCounts($branchId);

        if ($counts['low_stock'] > 0) {
            $notifications[] = [
                'type' => 'danger',
                'icon' => 'bi-exclamation-triangle',
                'title' => 'Low Stock Alert',
                'message' => "{$counts['low_stock']} drug stock items are running low",
                'timestamp' => now(),
                'priority' => 'high'
            ];
        }

        if ($counts['expiring_soon'] > 0) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'bi-clock-history',
                'title' => 'Expiring Soon',
                'message' => "{$counts['expiring_soon']} drug stock items expire within {$alertService->expiryWarningDays()} days",
                'timestamp' => now(),
                'priority' => 'high'
            ];
        }

        if ($counts['expired'] > 0) {
            $notifications[] = [
                'type' => 'danger',
                'icon' => 'bi-calendar-x',
                'title' => 'Expired Stock',
                'message' => "{$counts['expired']} drug stock items have expired",
                'timestamp' => now(),
                'priority' => 'high'
            ];
        }

        return $notifications;
    }

    /**
     * Get lab technician-specific notifications
     */
    protected function getLabTechnicianNotifications(User $user, $branchId): array
    {
        $notifications = [];

        // Pending lab tests
        $pendingTests = LabRequest::where('status', 'pending')->count();

        if ($pendingTests > 0) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'bi-flask',
                'title' => 'Pending Lab Tests',
                'message' => "You have {$pendingTests} lab tests pending",
                'timestamp' => now(),
                'priority' => 'high'
            ];
        }

        // Overdue lab tests
        $overdueTests = LabRequest::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(24))
            ->count();

        if ($overdueTests > 0) {
            $notifications[] = [
                'type' => 'danger',
                'icon' => 'bi-clock-history',
                'title' => 'Overdue Lab Tests',
                'message' => "{$overdueTests} lab tests are overdue",
                'timestamp' => now(),
                'priority' => 'high'
            ];
        }

        return $notifications;
    }

    /**
     * Get accountant-specific notifications
     */
    protected function getAccountantNotifications(User $user, $branchId): array
    {
        $notifications = [];

        // Pending invoices
        $pendingInvoices = DB::table('invoices')
            ->where('status', 'pending')
            ->count();

        if ($pendingInvoices > 0) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'bi-receipt',
                'title' => 'Pending Invoices',
                'message' => "You have {$pendingInvoices} pending invoices",
                'timestamp' => now(),
                'priority' => 'medium'
            ];
        }

        return $notifications;
    }

    /**
     * Get admin-specific notifications
     */
    protected function getAdminNotifications(User $user, $branchId): array
    {
        $notifications = [];

        // System alerts
        $notifications[] = [
            'type' => 'info',
            'icon' => 'bi-info-circle',
            'title' => 'System Status',
            'message' => 'All systems operational',
            'timestamp' => now(),
            'priority' => 'low'
        ];

        return $notifications;
    }

    /**
     * Get system-wide notifications
     */
    protected function getSystemNotifications(User $user, $branchId): array
    {
        $notifications = [];

        // Queue status notifications
        if ($branchId) {
            $queueStatus = $this->getQueueStatusNotifications($branchId);
            $notifications = array_merge($notifications, $queueStatus);
        }

        return $notifications;
    }

    /**
     * Get queue status notifications
     */
    protected function getQueueStatusNotifications($branchId): array
    {
        $notifications = [];

        // Long waiting times
        $longWaitQueues = Queue::where('branch_id', $branchId)
            ->where('status', 'waiting')
            ->where('queued_at', '<', now()->subMinutes(30))
            ->count();

        if ($longWaitQueues > 0) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'bi-clock-history',
                'title' => 'Long Wait Times',
                'message' => "{$longWaitQueues} patients have been waiting over 30 minutes",
                'timestamp' => now(),
                'priority' => 'medium'
            ];
        }

        return $notifications;
    }

    /**
     * Get notification count for user
     */
    public function getNotificationCount(User $user): int
    {
        $notifications = $this->getNotificationsForUser($user);
        return count($notifications);
    }

    /**
     * Get high priority notification count
     */
    public function getHighPriorityNotificationCount(User $user): int
    {
        $notifications = $this->getNotificationsForUser($user);
        return collect($notifications)->where('priority', 'high')->count();
    }
}
