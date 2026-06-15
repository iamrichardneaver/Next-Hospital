<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserNotificationPreference;
use App\Models\Queue;
use App\Models\Prescription;
use App\Models\LabRequest;
use App\Models\EmergencyAlert;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class NotificationPreferenceController extends Controller
{
    /**
     * Get user's notification preferences.
     */
    public function index(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $preferences = $user->getOrCreateNotificationPreference();
            
            return response()->json([
                'success' => true,
                'data' => $preferences->toFormArray()
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get notification preferences: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notification preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user's notification preferences.
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $validated = $request->validate([
                'audio_enabled' => 'sometimes|boolean',
                'audio_volume' => 'sometimes|integer|min:0|max:100',
                'notification_sound' => 'sometimes|string|in:standard,urgent,critical,custom',
                'notify_opd_queue' => 'sometimes|boolean',
                'notify_lab_queue' => 'sometimes|boolean',
                'notify_pharmacy_queue' => 'sometimes|boolean',
                'notify_emergency_queue' => 'sometimes|boolean',
                'notify_triage_queue' => 'sometimes|boolean',
                'notify_routine' => 'sometimes|boolean',
                'notify_urgent' => 'sometimes|boolean',
                'notify_critical' => 'sometimes|boolean',
                'notify_new_patient' => 'sometimes|boolean',
                'notify_patient_waiting' => 'sometimes|boolean',
                'notify_prescription_ready' => 'sometimes|boolean',
                'notify_lab_result_ready' => 'sometimes|boolean',
                'notify_consultation_required' => 'sometimes|boolean',
                'check_interval' => 'sometimes|integer|min:10|max:300',
                'desktop_notification' => 'sometimes|boolean',
                'do_not_disturb' => 'sometimes|boolean',
                'dnd_start' => 'nullable|date_format:H:i',
                'dnd_end' => 'nullable|date_format:H:i',
            ]);

            $preferences = $user->getOrCreateNotificationPreference();
            $preferences->update(UserNotificationPreference::normalizeUpdateData($validated));
            $preferences->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated successfully',
                'data' => $preferences->toFormArray()
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to update notification preferences: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check for new work items that require attention.
     * This is role-based and only returns work relevant to the user's role.
     */
    public function checkNewWork(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            // Get user's branch - try multiple sources with proper fallback
            $branchId = null;
            
            // Try to get branch from user's assigned branches
            $userBranch = $user->branches()->first();
            if ($userBranch) {
                $branchId = $userBranch->id;
            }
            
            // Fallback to staff profile branch_id if no branch relationship
            if (!$branchId && $user->staffProfile && $user->staffProfile->branch_id) {
                $branchId = $user->staffProfile->branch_id;
            }
            
            // Fallback to current_branch_id if set
            if (!$branchId && $user->current_branch_id) {
                $branchId = $user->current_branch_id;
            }
            
            // Final fallback: use default branch (ID 1)
            if (!$branchId) {
                $branchId = 1;
                \Log::warning('User accessing notification check without branch assignment', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'using_default_branch' => true
                ]);
            }
            
            $preferences = $user->getOrCreateNotificationPreference();

        // If notifications are disabled, return empty
        if (!$preferences->audio_enabled || ($preferences->do_not_disturb && $preferences->isInDoNotDisturbPeriod())) {
            return response()->json([
                'success' => true,
                'has_new_work' => false,
                'notifications' => []
            ]);
        }

        $notifications = [];
        $lastCheckTime = $request->input('last_check', now()->subMinutes(5)->toDateTimeString());

        // Check based on user role
        if ($user->hasRole('doctor')) {
            $notifications = array_merge($notifications, $this->checkDoctorWork($user, $branchId, $lastCheckTime, $preferences));
        }

        if ($user->hasRole('nurse')) {
            $notifications = array_merge($notifications, $this->checkNurseWork($user, $branchId, $lastCheckTime, $preferences));
        }

        if ($user->hasRole('lab_technician')) {
            $notifications = array_merge($notifications, $this->checkLabWork($user, $branchId, $lastCheckTime, $preferences));
        }

        if ($user->hasRole('pharmacist')) {
            $notifications = array_merge($notifications, $this->checkPharmacyWork($user, $branchId, $lastCheckTime, $preferences));
        }

        // Emergency notifications for relevant staff
        if ($user->hasRole(['doctor', 'nurse'])) {
            $notifications = array_merge($notifications, $this->checkEmergencyAlerts($user, $branchId, $lastCheckTime, $preferences));
        }

            return response()->json([
                'success' => true,
                'has_new_work' => count($notifications) > 0,
                'notifications' => $notifications,
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error checking for new work via API: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'has_new_work' => false,
                'notifications' => [],
                'message' => 'Error checking for new work',
                'timestamp' => now()->toDateTimeString()
            ], 500);
        }
    }

    /**
     * Check for doctor-specific work.
     */
    private function checkDoctorWork($user, $branchId, $lastCheckTime, $preferences)
    {
        $notifications = [];

        if ($preferences->notify_opd_queue) {
            // Check OPD queue
            $opdQueue = Queue::where('branch_id', $branchId)
                ->where('queue_type', 'OPD')
                ->where('status', 'waiting')
                ->where('queued_at', '>=', $lastCheckTime)
                ->with('patient')
                ->get();

            foreach ($opdQueue as $queue) {
                if ($preferences->shouldNotifyForPriority($queue->priority ?? 'routine')) {
                    $notifications[] = [
                        'type' => 'new_patient',
                        'queue_type' => 'OPD',
                        'priority' => $queue->priority ?? 'routine',
                        'sound' => $preferences->getSoundForPriority($queue->priority ?? 'routine'),
                        'message' => 'New patient in OPD queue',
                        'patient_name' => $queue->patient->first_name . ' ' . $queue->patient->last_name,
                        'queue_id' => $queue->id,
                        'patient_id' => $queue->patient_id
                    ];
                }
            }
        }

        return $notifications;
    }

    /**
     * Check for nurse-specific work.
     */
    private function checkNurseWork($user, $branchId, $lastCheckTime, $preferences)
    {
        $notifications = [];

        if ($preferences->notify_opd_queue) {
            // Check for patients needing vitals (in triage queue)
            $triageQueue = Queue::where('branch_id', $branchId)
                ->where('queue_type', 'Triage')
                ->where('status', 'waiting')
                ->where('queued_at', '>=', $lastCheckTime)
                ->with('patient')
                ->get();

            foreach ($triageQueue as $queue) {
                if ($preferences->shouldNotifyForPriority($queue->priority ?? 'routine')) {
                    $notifications[] = [
                        'type' => 'vitals_needed',
                        'queue_type' => 'Triage',
                        'priority' => $queue->priority ?? 'routine',
                        'sound' => $preferences->getSoundForPriority($queue->priority ?? 'routine'),
                        'message' => 'Patient needs vitals check',
                        'patient_name' => $queue->patient->first_name . ' ' . $queue->patient->last_name,
                        'queue_id' => $queue->id,
                        'patient_id' => $queue->patient_id
                    ];
                }
            }
        }

        return $notifications;
    }

    /**
     * Check for lab technician-specific work.
     */
    private function checkLabWork($user, $branchId, $lastCheckTime, $preferences)
    {
        $notifications = [];

        if ($preferences->notify_lab_queue) {
            // Check lab queue
            $labQueue = Queue::where('branch_id', $branchId)
                ->where('queue_type', 'Lab')
                ->where('status', 'waiting')
                ->where('queued_at', '>=', $lastCheckTime)
                ->with('patient')
                ->get();

            foreach ($labQueue as $queue) {
                if ($preferences->shouldNotifyForPriority($queue->priority ?? 'routine')) {
                    $notifications[] = [
                        'type' => 'new_lab_request',
                        'queue_type' => 'Lab',
                        'priority' => $queue->priority ?? 'routine',
                        'sound' => $preferences->getSoundForPriority($queue->priority ?? 'routine'),
                        'message' => 'New lab request',
                        'patient_name' => $queue->patient->first_name . ' ' . $queue->patient->last_name,
                        'queue_id' => $queue->id,
                        'patient_id' => $queue->patient_id
                    ];
                }
            }

            // Check for new lab requests (direct orders)
            $labRequests = LabRequest::where('branch_id', $branchId)
                ->where('status', 'pending')
                ->where('created_at', '>=', $lastCheckTime)
                ->with('patient')
                ->get();

            foreach ($labRequests as $request) {
                $notifications[] = [
                    'type' => 'new_lab_order',
                    'queue_type' => 'Lab',
                    'priority' => $request->priority ?? 'routine',
                    'sound' => $preferences->getSoundForPriority($request->priority ?? 'routine'),
                    'message' => 'New lab order received',
                    'patient_name' => $request->patient->first_name . ' ' . $request->patient->last_name,
                    'request_id' => $request->id,
                    'patient_id' => $request->patient_id
                ];
            }
        }

        return $notifications;
    }

    /**
     * Check for pharmacist-specific work.
     */
    private function checkPharmacyWork($user, $branchId, $lastCheckTime, $preferences)
    {
        $notifications = [];

        if ($preferences->notify_pharmacy_queue) {
            // Check pharmacy queue
            $pharmacyQueue = Queue::where('branch_id', $branchId)
                ->where('queue_type', 'Pharmacy')
                ->where('status', 'waiting')
                ->where('queued_at', '>=', $lastCheckTime)
                ->with('patient')
                ->get();

            foreach ($pharmacyQueue as $queue) {
                if ($preferences->shouldNotifyForPriority($queue->priority ?? 'routine')) {
                    $notifications[] = [
                        'type' => 'new_pharmacy_patient',
                        'queue_type' => 'Pharmacy',
                        'priority' => $queue->priority ?? 'routine',
                        'sound' => $preferences->getSoundForPriority($queue->priority ?? 'routine'),
                        'message' => 'New patient in pharmacy queue',
                        'patient_name' => $queue->patient->first_name . ' ' . $queue->patient->last_name,
                        'queue_id' => $queue->id,
                        'patient_id' => $queue->patient_id
                    ];
                }
            }

            // Check for new prescriptions to dispense
            if ($preferences->notify_prescription_ready) {
                $prescriptions = Prescription::where('branch_id', $branchId)
                    ->where('status', 'pending')
                    ->where('created_at', '>=', $lastCheckTime)
                    ->with('patient')
                    ->get();

                foreach ($prescriptions as $prescription) {
                    $notifications[] = [
                        'type' => 'new_prescription',
                        'queue_type' => 'Pharmacy',
                        'priority' => 'routine',
                        'sound' => 'standard',
                        'message' => 'New prescription to dispense',
                        'patient_name' => $prescription->patient->first_name . ' ' . $prescription->patient->last_name,
                        'prescription_id' => $prescription->id,
                        'patient_id' => $prescription->patient_id
                    ];
                }
            }
        }

        return $notifications;
    }

    /**
     * Check for emergency alerts.
     */
    private function checkEmergencyAlerts($user, $branchId, $lastCheckTime, $preferences)
    {
        $notifications = [];

        if ($preferences->notify_emergency_queue) {
            $alerts = EmergencyAlert::where('branch_id', $branchId)
                ->where('status', 'active')
                ->where('created_at', '>=', $lastCheckTime)
                ->with('patient')
                ->get();

            foreach ($alerts as $alert) {
                if ($preferences->shouldNotifyForPriority($alert->priority ?? 'critical')) {
                    $notifications[] = [
                        'type' => 'emergency_alert',
                        'queue_type' => 'Emergency',
                        'priority' => $alert->priority ?? 'critical',
                        'sound' => 'critical',
                        'message' => $alert->alert_type . ' - ' . $alert->description,
                        'patient_name' => $alert->patient->first_name . ' ' . $alert->patient->last_name,
                        'alert_id' => $alert->id,
                        'patient_id' => $alert->patient_id
                    ];
                }
            }
        }

        return $notifications;
    }
}
