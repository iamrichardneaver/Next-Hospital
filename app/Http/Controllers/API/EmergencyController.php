<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EmergencyVisit;
use App\Models\TriageAssessment;
use App\Models\EmergencyAlert;
use App\Models\CrashCart;
use App\Models\Patient;
use App\Models\User;
use App\Models\Branch;
use App\Models\Visit;
use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class EmergencyController extends Controller
{
    /**
     * Display a listing of emergency visits.
     */
    public function index(Request $request)
    {
        $query = EmergencyVisit::with(['patient', 'triageAssessment', 'assignedDoctor', 'branch'])
            ->orderBy('id', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by triage level
        if ($request->has('triage_level')) {
            $query->whereHas('triageAssessment', function($q) use ($request) {
                $q->where('triage_level', $request->triage_level);
            });
        }

        // Filter by assigned doctor
        if ($request->has('doctor_id')) {
            $query->where('assigned_doctor_id', $request->doctor_id);
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('arrival_time', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('arrival_time', '<=', $request->date_to);
        }

        // Search by patient name or visit number
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('visit_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($patientQuery) use ($search) {
                      $patientQuery->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%")
                                  ->orWhere('patient_number', 'like', "%{$search}%");
                  });
            });
        }

        $visits = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $visits,
            'message' => 'Emergency visits retrieved successfully'
        ]);
    }

    /**
     * Store a newly created emergency visit.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
            'arrival_time' => 'required|date',
            'chief_complaint' => 'required|string',
            'arrival_mode' => 'required|in:ambulance,walk-in,private_vehicle,police,other',
            'accompanied_by' => 'nullable|string',
            'referral_source' => 'nullable|string',
            'vital_signs' => 'required|array',
            'vital_signs.blood_pressure_systolic' => 'required|integer|min:50|max:300',
            'vital_signs.blood_pressure_diastolic' => 'required|integer|min:30|max:200',
            'vital_signs.pulse_rate' => 'required|integer|min:30|max:250',
            'vital_signs.respiratory_rate' => 'required|integer|min:5|max:60',
            'vital_signs.temperature' => 'required|numeric|min:30|max:45',
            'vital_signs.oxygen_saturation' => 'required|integer|min:50|max:100',
            'vital_signs.glasgow_coma_scale' => 'required|integer|min:3|max:15',
            'triage_level' => 'required|in:1,2,3,4,5',
            'triage_notes' => 'nullable|string',
            'assigned_doctor_id' => 'nullable|exists:users,id',
            'priority' => 'required|in:critical,urgent,stable,non_urgent',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create emergency visit
            $visit = EmergencyVisit::create([
                'patient_id' => $request->patient_id,
                'branch_id' => $request->branch_id,
                'arrival_time' => $request->arrival_time,
                'chief_complaint' => $request->chief_complaint,
                'arrival_mode' => $request->arrival_mode,
                'accompanied_by' => $request->accompanied_by,
                'referral_source' => $request->referral_source,
                'vital_signs' => $request->vital_signs,
                'triage_level' => $request->triage_level,
                'triage_notes' => $request->triage_notes,
                'assigned_doctor_id' => $request->assigned_doctor_id,
                'priority' => $request->priority,
                'status' => 'active',
                'notes' => $request->notes,
                'created_by' => auth()->id()
            ]);

            // Create triage assessment
            $triageAssessment = TriageAssessment::create([
                'emergency_visit_id' => $visit->id,
                'triage_level' => $request->triage_level,
                'vital_signs' => $request->vital_signs,
                'chief_complaint' => $request->chief_complaint,
                'assessment_notes' => $request->triage_notes,
                'assessed_by' => auth()->id(),
                'assessment_time' => now()
            ]);

            // Create emergency alert if critical
            if ($request->triage_level <= 2 || $request->priority === 'critical') {
                EmergencyAlert::create([
                    'emergency_visit_id' => $visit->id,
                    'alert_type' => 'critical_triage',
                    'message' => "Critical patient arrived: {$visit->patient->first_name} {$visit->patient->last_name}",
                    'priority' => 'high',
                    'status' => 'active',
                    'created_by' => auth()->id()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $visit->load(['patient', 'triageAssessment', 'assignedDoctor', 'branch']),
                'message' => 'Emergency visit created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating emergency visit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified emergency visit.
     */
    public function show($id)
    {
        $visit = EmergencyVisit::with([
            'patient', 
            'triageAssessment', 
            'assignedDoctor', 
            'branch',
            'alerts',
            'interventions'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $visit,
            'message' => 'Emergency visit retrieved successfully'
        ]);
    }

    /**
     * Update the specified emergency visit.
     */
    public function update(Request $request, $id)
    {
        $visit = EmergencyVisit::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:active,discharged,transferred,deceased',
            'assigned_doctor_id' => 'sometimes|exists:users,id',
            'priority' => 'sometimes|in:critical,urgent,stable,non_urgent',
            'notes' => 'sometimes|string',
            'discharge_time' => 'nullable|date',
            'discharge_diagnosis' => 'nullable|string',
            'discharge_instructions' => 'nullable|string',
            'transfer_destination' => 'nullable|string',
            'transfer_reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $visit->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $visit->load(['patient', 'triageAssessment', 'assignedDoctor', 'branch']),
            'message' => 'Emergency visit updated successfully'
        ]);
    }

    /**
     * Update triage assessment.
     */
    public function updateTriage(Request $request, $visitId)
    {
        $visit = EmergencyVisit::findOrFail($visitId);

        $validator = Validator::make($request->all(), [
            'triage_level' => 'required|in:1,2,3,4,5',
            'vital_signs' => 'required|array',
            'assessment_notes' => 'nullable|string',
            'reassessment_reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $triageAssessment = TriageAssessment::where('emergency_visit_id', $visitId)->first();
        
        if ($triageAssessment) {
            $triageAssessment->update([
                'triage_level' => $request->triage_level,
                'vital_signs' => $request->vital_signs,
                'assessment_notes' => $request->assessment_notes,
                'reassessment_reason' => $request->reassessment_reason,
                'reassessed_by' => auth()->id(),
                'reassessment_time' => now()
            ]);
        } else {
            TriageAssessment::create([
                'emergency_visit_id' => $visitId,
                'triage_level' => $request->triage_level,
                'vital_signs' => $request->vital_signs,
                'assessment_notes' => $request->assessment_notes,
                'assessed_by' => auth()->id(),
                'assessment_time' => now()
            ]);
        }

        // Update visit priority based on triage level
        $priority = $this->getPriorityFromTriageLevel($request->triage_level);
        $visit->update(['priority' => $priority]);

        return response()->json([
            'success' => true,
            'data' => $visit->load(['triageAssessment']),
            'message' => 'Triage assessment updated successfully'
        ]);
    }

    /**
     * Add emergency intervention.
     */
    public function addIntervention(Request $request, $visitId)
    {
        $visit = EmergencyVisit::findOrFail($visitId);

        $validator = Validator::make($request->all(), [
            'intervention_type' => 'required|in:medication,procedure,lab_order,imaging,consultation,transfer',
            'description' => 'required|string',
            'medication_id' => 'nullable|exists:drugs,id',
            'dosage' => 'nullable|string',
            'frequency' => 'nullable|string',
            'procedure_code' => 'nullable|string',
            'lab_tests' => 'nullable|array',
            'imaging_type' => 'nullable|string',
            'consultation_specialty' => 'nullable|string',
            'transfer_destination' => 'nullable|string',
            'priority' => 'required|in:immediate,urgent,routine',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $intervention = $visit->interventions()->create([
            'intervention_type' => $request->intervention_type,
            'description' => $request->description,
            'medication_id' => $request->medication_id,
            'dosage' => $request->dosage,
            'frequency' => $request->frequency,
            'procedure_code' => $request->procedure_code,
            'lab_tests' => $request->lab_tests,
            'imaging_type' => $request->imaging_type,
            'consultation_specialty' => $request->consultation_specialty,
            'transfer_destination' => $request->transfer_destination,
            'priority' => $request->priority,
            'status' => 'ordered',
            'notes' => $request->notes,
            'ordered_by' => auth()->id(),
            'ordered_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => $intervention,
            'message' => 'Emergency intervention added successfully'
        ], 201);
    }

    /**
     * Get active emergency alerts.
     */
    public function getActiveAlerts(Request $request)
    {
        $query = EmergencyAlert::with(['emergencyVisit.patient', 'createdBy'])
            ->where('status', 'active')
            ->orderBy('created_at', 'desc');

        // Filter by alert type
        if ($request->has('alert_type')) {
            $query->where('alert_type', $request->alert_type);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $alerts = $query->get();

        return response()->json([
            'success' => true,
            'data' => $alerts,
            'message' => 'Active emergency alerts retrieved successfully'
        ]);
    }

    /**
     * Acknowledge emergency alert.
     */
    public function acknowledgeAlert(Request $request, $alertId)
    {
        $alert = EmergencyAlert::findOrFail($alertId);

        $validator = Validator::make($request->all(), [
            'acknowledgment_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now(),
            'acknowledgment_notes' => $request->acknowledgment_notes
        ]);

        return response()->json([
            'success' => true,
            'data' => $alert,
            'message' => 'Emergency alert acknowledged successfully'
        ]);
    }

    /**
     * Get emergency statistics.
     */
    public function getStatistics(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(30);
        $dateTo = $request->date_to ?? now();

        $stats = [
            'total_visits' => EmergencyVisit::whereBetween('arrival_time', [$dateFrom, $dateTo])->count(),
            'active_visits' => EmergencyVisit::where('status', 'active')->count(),
            'discharged_visits' => EmergencyVisit::where('status', 'discharged')->whereBetween('arrival_time', [$dateFrom, $dateTo])->count(),
            'transferred_visits' => EmergencyVisit::where('status', 'transferred')->whereBetween('arrival_time', [$dateFrom, $dateTo])->count(),
            'critical_visits' => EmergencyVisit::where('priority', 'critical')->whereBetween('arrival_time', [$dateFrom, $dateTo])->count(),
            'urgent_visits' => EmergencyVisit::where('priority', 'urgent')->whereBetween('arrival_time', [$dateFrom, $dateTo])->count(),
            'triage_levels' => $this->getTriageLevelStats($dateFrom, $dateTo),
            'arrival_modes' => $this->getArrivalModeStats($dateFrom, $dateTo),
            'average_wait_time' => $this->getAverageWaitTime($dateFrom, $dateTo),
            'top_complaints' => $this->getTopComplaints($dateFrom, $dateTo),
            'active_alerts' => EmergencyAlert::where('status', 'active')->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Emergency statistics retrieved successfully'
        ]);
    }

    /**
     * Get triage level statistics.
     */
    private function getTriageLevelStats($dateFrom, $dateTo)
    {
        return EmergencyVisit::whereBetween('arrival_time', [$dateFrom, $dateTo])
            ->selectRaw('triage_level, COUNT(*) as count')
            ->groupBy('triage_level')
            ->orderBy('triage_level')
            ->get();
    }

    /**
     * Get arrival mode statistics.
     */
    private function getArrivalModeStats($dateFrom, $dateTo)
    {
        return EmergencyVisit::whereBetween('arrival_time', [$dateFrom, $dateTo])
            ->selectRaw('arrival_mode, COUNT(*) as count')
            ->groupBy('arrival_mode')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Get average wait time.
     */
    private function getAverageWaitTime($dateFrom, $dateTo)
    {
        $visits = EmergencyVisit::whereBetween('arrival_time', [$dateFrom, $dateTo])
            ->whereNotNull('discharge_time')
            ->get();

        if ($visits->isEmpty()) {
            return 0;
        }

        $totalMinutes = $visits->sum(function($visit) {
            return Carbon::parse($visit->arrival_time)->diffInMinutes(Carbon::parse($visit->discharge_time));
        });

        return round($totalMinutes / $visits->count(), 2);
    }

    /**
     * Get top chief complaints.
     */
    private function getTopComplaints($dateFrom, $dateTo)
    {
        return EmergencyVisit::whereBetween('arrival_time', [$dateFrom, $dateTo])
            ->selectRaw('chief_complaint, COUNT(*) as count')
            ->groupBy('chief_complaint')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get priority from triage level.
     */
    private function getPriorityFromTriageLevel($triageLevel)
    {
        $priorityMap = [
            1 => 'critical',
            2 => 'critical',
            3 => 'urgent',
            4 => 'stable',
            5 => 'non_urgent'
        ];

        return $priorityMap[$triageLevel] ?? 'stable';
    }

    /**
     * Get crash cart inventory.
     */
    public function getCrashCartInventory(Request $request)
    {
        $query = CrashCart::with(['items'])
            ->where('is_active', true);

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $crashCarts = $query->get();

        return response()->json([
            'success' => true,
            'data' => $crashCarts,
            'message' => 'Crash cart inventory retrieved successfully'
        ]);
    }

    /**
     * Update crash cart item.
     */
    public function updateCrashCartItem(Request $request, $cartId)
    {
        $validator = Validator::make($request->all(), [
            'item_name' => 'required|string',
            'quantity_used' => 'required|integer|min:0',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $crashCart = CrashCart::findOrFail($cartId);
        
        // Update item quantity
        $crashCart->update([
            'current_quantity' => $crashCart->current_quantity - $request->quantity_used,
            'last_used' => now(),
            'last_used_by' => auth()->id(),
            'usage_notes' => $request->notes
        ]);

        return response()->json([
            'success' => true,
            'data' => $crashCart,
            'message' => 'Crash cart item updated successfully'
        ]);
    }

    /**
     * Create emergency visit from visit system.
     */
    public function createFromVisit(Request $request, $visitId)
    {
        $visit = Visit::with(['patient', 'queues'])->findOrFail($visitId);

        if ($visit->status !== 'active' || $visit->visit_type !== 'Emergency') {
            return response()->json([
                'success' => false,
                'message' => 'Visit is not active or not an emergency visit'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'arrival_mode' => 'required|in:ambulance,walk-in,private_vehicle,police,other',
            'accompanied_by' => 'nullable|string|max:255',
            'referral_source' => 'nullable|string|max:255',
            'triage_level' => 'required|integer|min:1|max:5',
            'triage_notes' => 'nullable|string',
            'assigned_doctor_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create emergency visit
            $emergencyVisit = EmergencyVisit::create([
                'patient_id' => $visit->patient_id,
                'branch_id' => $visit->branch_id,
                'arrival_time' => $visit->check_in_time,
                'chief_complaint' => $visit->chief_complaint ?? 'Emergency visit',
                'arrival_mode' => $request->arrival_mode,
                'accompanied_by' => $request->accompanied_by,
                'referral_source' => $request->referral_source,
                'vital_signs' => $visit->vital_signs ?? [],
                'triage_level' => $request->triage_level,
                'triage_notes' => $request->triage_notes,
                'assigned_doctor_id' => $request->assigned_doctor_id,
                'priority' => $visit->priority,
                'status' => 'active',
                'notes' => $request->notes,
                'created_by' => auth()->id()
            ]);

            // Create triage assessment
            $triageAssessment = TriageAssessment::create([
                'emergency_visit_id' => $emergencyVisit->id,
                'triage_level' => $request->triage_level,
                'vital_signs' => $visit->vital_signs ?? [],
                'chief_complaint' => $visit->chief_complaint ?? 'Emergency visit',
                'assessment_notes' => $request->triage_notes,
                'assessed_by' => auth()->id(),
                'assessment_time' => now()
            ]);

            // Create emergency alert if critical
            if ($request->triage_level <= 2 || $visit->priority === 'critical') {
                EmergencyAlert::create([
                    'emergency_visit_id' => $emergencyVisit->id,
                    'alert_type' => 'critical_triage',
                    'message' => "Critical patient arrived: {$visit->patient->first_name} {$visit->patient->last_name}",
                    'priority' => 'high',
                    'status' => 'active',
                    'created_by' => auth()->id()
                ]);
            }

            // Update queue status to serving
            $visit->queues()->where('queue_type', 'Emergency')->update([
                'status' => 'serving',
                'serving_at' => now(),
                'served_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $emergencyVisit->load(['patient', 'triageAssessment', 'assignedDoctor', 'branch', 'alerts']),
                'message' => 'Emergency visit created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating emergency visit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete emergency visit and update queue.
     */
    public function completeEmergencyVisit(Request $request, $visitId)
    {
        $visit = Visit::with(['queues'])->findOrFail($visitId);

        if ($visit->status !== 'active' || $visit->visit_type !== 'Emergency') {
            return response()->json([
                'success' => false,
                'message' => 'Visit is not active or not an emergency visit'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'discharge_diagnosis' => 'nullable|string',
            'discharge_instructions' => 'nullable|string',
            'transfer_destination' => 'nullable|string',
            'transfer_reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Update emergency visit
            $emergencyVisit = EmergencyVisit::where('visit_number', $visit->visit_token)->first();
            if ($emergencyVisit) {
                $emergencyVisit->update([
                    'status' => 'discharged',
                    'discharge_time' => now(),
                    'discharge_diagnosis' => $request->discharge_diagnosis,
                    'discharge_instructions' => $request->discharge_instructions,
                    'transfer_destination' => $request->transfer_destination,
                    'transfer_reason' => $request->transfer_reason
                ]);
            }

            // Update queue status to completed
            $visit->queues()->where('queue_type', 'Emergency')->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            // Complete visit
            $visit->update([
                'status' => 'completed',
                'check_out_time' => now(),
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $visit,
                'message' => 'Emergency visit completed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error completing emergency visit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get emergency queue status.
     */
    public function getEmergencyQueueStatus(Request $request)
    {
        $branchId = $request->get('branch_id', 1);

        $queues = Queue::with(['patient', 'visit'])
                      ->where('queue_type', 'Emergency')
                      ->where('branch_id', $branchId)
                      ->whereIn('status', ['waiting', 'called', 'serving'])
                      ->orderBy('priority', 'asc') // Critical first
                      ->orderBy('position')
                      ->get();

        $stats = [
            'total_waiting' => $queues->where('status', 'waiting')->count(),
            'total_called' => $queues->where('status', 'called')->count(),
            'total_serving' => $queues->where('status', 'serving')->count(),
            'current_serving' => $queues->where('status', 'serving')->first(),
            'next_in_line' => $queues->where('status', 'waiting')->first(),
            'average_wait_time' => $this->calculateAverageWaitTime('Emergency', $branchId),
            'estimated_wait_time' => $this->calculateEstimatedWaitTime('Emergency', $branchId)
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'queues' => $queues,
                'stats' => $stats
            ],
            'message' => 'Emergency queue status retrieved successfully'
        ]);
    }

    /**
     * Call next patient in emergency queue.
     */
    public function callNextEmergencyPatient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'called_by' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Find next patient in emergency queue (prioritize by priority)
            $nextQueue = Queue::where('queue_type', 'Emergency')
                            ->where('branch_id', $request->branch_id)
                            ->where('status', 'waiting')
                            ->orderBy('priority', 'asc') // Critical first
                            ->orderBy('position')
                            ->first();

            if (!$nextQueue) {
                return response()->json([
                    'success' => false,
                    'message' => 'No patients waiting in emergency queue'
                ], 404);
            }

            // Update queue status
            $nextQueue->update([
                'status' => 'called',
                'called_at' => now(),
                'called_by' => $request->called_by
            ]);

            // Update all other queues to move them up
            Queue::where('queue_type', 'Emergency')
                ->where('branch_id', $request->branch_id)
                ->where('status', 'waiting')
                ->where('position', '>', $nextQueue->position)
                ->decrement('position');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $nextQueue->load(['patient', 'visit']),
                'message' => 'Emergency patient called successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error calling emergency patient: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate average wait time for emergency queue.
     */
    private function calculateAverageWaitTime(string $queueType, int $branchId): float
    {
        return Queue::where('queue_type', $queueType)
                   ->where('branch_id', $branchId)
                   ->whereNotNull('called_at')
                   ->whereNotNull('queued_at')
                   ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, queued_at, called_at)) as avg_wait')
                   ->value('avg_wait') ?? 0;
    }

    /**
     * Calculate estimated wait time for emergency queue.
     */
    private function calculateEstimatedWaitTime(string $queueType, int $branchId): int
    {
        $avgServiceTime = Queue::where('queue_type', $queueType)
                             ->where('branch_id', $branchId)
                             ->where('status', 'completed')
                             ->whereNotNull('serving_at')
                             ->whereNotNull('completed_at')
                             ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, serving_at, completed_at)) as avg_service')
                             ->value('avg_service') ?? 10; // Default 10 minutes for emergency

        $patientsAhead = Queue::where('queue_type', $queueType)
                             ->where('branch_id', $branchId)
                             ->where('status', 'waiting')
                             ->count();

        return $patientsAhead * $avgServiceTime;
    }
}
