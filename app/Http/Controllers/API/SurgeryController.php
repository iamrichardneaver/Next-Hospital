<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SurgerySchedule;
use App\Models\SurgeryTeam;
use App\Models\SurgeryProcedure;
use App\Models\Theatre;
use App\Models\SurgeryEquipment;
use App\Models\Patient;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SurgeryController extends Controller
{
    /**
     * Display a listing of surgery schedules.
     */
    public function index(Request $request)
    {
        $query = SurgerySchedule::with(['patient', 'surgeon', 'theatre', 'team', 'procedure'])
            ->orderBy('surgery_date', 'desc')
            ->orderBy('surgery_time', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by surgeon
        if ($request->has('surgeon_id')) {
            $query->where('surgeon_id', $request->surgeon_id);
        }

        // Filter by theatre
        if ($request->has('theatre_id')) {
            $query->where('theatre_id', $request->theatre_id);
        }

        // Filter by procedure type
        if ($request->has('procedure_type')) {
            $query->whereHas('procedure', function($q) use ($request) {
                $q->where('procedure_type', $request->procedure_type);
            });
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('surgery_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('surgery_date', '<=', $request->date_to);
        }

        // Search by patient name or surgery number
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('surgery_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($patientQuery) use ($search) {
                      $patientQuery->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%")
                                  ->orWhere('patient_number', 'like', "%{$search}%");
                  });
            });
        }

        $surgeries = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $surgeries,
            'message' => 'Surgery schedules retrieved successfully'
        ]);
    }

    /**
     * Store a newly created surgery schedule.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'surgeon_id' => 'required|exists:users,id',
            'theatre_id' => 'required|exists:theatres,id',
            'procedure_id' => 'required|exists:surgery_procedures,id',
            'surgery_date' => 'required|date|after_or_equal:today',
            'surgery_time' => 'required|date_format:H:i',
            'estimated_duration' => 'required|integer|min:15|max:1440',
            'priority' => 'required|in:elective,urgent,emergency',
            'surgery_type' => 'required|in:major,minor,diagnostic,therapeutic',
            'anesthesia_type' => 'required|in:general,regional,local,conscious_sedation',
            'pre_op_instructions' => 'nullable|string',
            'post_op_instructions' => 'nullable|string',
            'special_requirements' => 'nullable|string',
            'team_members' => 'required|array|min:1',
            'team_members.*.user_id' => 'required|exists:users,id',
            'team_members.*.role' => 'required|in:surgeon,assistant_surgeon,anesthesiologist,nurse,technician',
            'equipment_required' => 'nullable|array',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for theatre availability
        $conflict = $this->checkTheatreAvailability($request->theatre_id, $request->surgery_date, $request->surgery_time, $request->estimated_duration);
        
        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'Theatre time conflict detected',
                'conflict' => $conflict
            ], 409);
        }

        DB::beginTransaction();

        try {
            // Create surgery schedule
            $surgery = SurgerySchedule::create([
                'patient_id' => $request->patient_id,
                'surgeon_id' => $request->surgeon_id,
                'theatre_id' => $request->theatre_id,
                'procedure_id' => $request->procedure_id,
                'surgery_date' => $request->surgery_date,
                'surgery_time' => $request->surgery_time,
                'estimated_duration' => $request->estimated_duration,
                'priority' => $request->priority,
                'surgery_type' => $request->surgery_type,
                'anesthesia_type' => $request->anesthesia_type,
                'pre_op_instructions' => $request->pre_op_instructions,
                'post_op_instructions' => $request->post_op_instructions,
                'special_requirements' => $request->special_requirements,
                'equipment_required' => $request->equipment_required,
                'status' => 'scheduled',
                'notes' => $request->notes,
                'created_by' => auth()->id()
            ]);

            // Create surgery team
            foreach ($request->team_members as $member) {
                SurgeryTeam::create([
                    'surgery_id' => $surgery->id,
                    'user_id' => $member['user_id'],
                    'role' => $member['role'],
                    'assigned_at' => now(),
                    'assigned_by' => auth()->id()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $surgery->load(['patient', 'surgeon', 'theatre', 'team.user', 'procedure']),
                'message' => 'Surgery scheduled successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error scheduling surgery: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified surgery schedule.
     */
    public function show($id)
    {
        $surgery = SurgerySchedule::with([
            'patient', 
            'surgeon', 
            'theatre', 
            'team.user', 
            'procedure',
            'preOpChecklist',
            'postOpNotes'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $surgery,
            'message' => 'Surgery schedule retrieved successfully'
        ]);
    }

    /**
     * Update the specified surgery schedule.
     */
    public function update(Request $request, $id)
    {
        $surgery = SurgerySchedule::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'surgery_date' => 'sometimes|date|after_or_equal:today',
            'surgery_time' => 'sometimes|date_format:H:i',
            'estimated_duration' => 'sometimes|integer|min:15|max:1440',
            'priority' => 'sometimes|in:elective,urgent,emergency',
            'status' => 'sometimes|in:scheduled,confirmed,in_progress,completed,cancelled,postponed',
            'pre_op_instructions' => 'sometimes|string',
            'post_op_instructions' => 'sometimes|string',
            'special_requirements' => 'sometimes|string',
            'notes' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for theatre availability if time is being changed
        if ($request->has('surgery_date') || $request->has('surgery_time')) {
            $conflict = $this->checkTheatreAvailability(
                $surgery->theatre_id,
                $request->surgery_date ?? $surgery->surgery_date,
                $request->surgery_time ?? $surgery->surgery_time,
                $request->estimated_duration ?? $surgery->estimated_duration,
                $id
            );
            
            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Theatre time conflict detected',
                    'conflict' => $conflict
                ], 409);
            }
        }

        $surgery->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $surgery->load(['patient', 'surgeon', 'theatre', 'team.user', 'procedure']),
            'message' => 'Surgery schedule updated successfully'
        ]);
    }

    /**
     * Start surgery.
     */
    public function startSurgery(Request $request, $id)
    {
        $surgery = SurgerySchedule::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'actual_start_time' => 'required|date_format:Y-m-d H:i:s',
            'anesthesia_start_time' => 'nullable|date_format:Y-m-d H:i:s',
            'incision_time' => 'nullable|date_format:Y-m-d H:i:s',
            'pre_op_checklist' => 'required|array',
            'pre_op_checklist.*.item' => 'required|string',
            'pre_op_checklist.*.checked' => 'required|boolean',
            'pre_op_checklist.*.notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $surgery->update([
            'status' => 'in_progress',
            'actual_start_time' => $request->actual_start_time,
            'anesthesia_start_time' => $request->anesthesia_start_time,
            'incision_time' => $request->incision_time,
            'pre_op_checklist' => $request->pre_op_checklist,
            'started_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'data' => $surgery,
            'message' => 'Surgery started successfully'
        ]);
    }

    /**
     * Complete surgery.
     */
    public function completeSurgery(Request $request, $id)
    {
        $surgery = SurgerySchedule::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'actual_end_time' => 'required|date_format:Y-m-d H:i:s',
            'closure_time' => 'nullable|date_format:Y-m-d H:i:s',
            'anesthesia_end_time' => 'nullable|date_format:Y-m-d H:i:s',
            'post_op_notes' => 'required|string',
            'complications' => 'nullable|string',
            'blood_loss' => 'nullable|string',
            'vital_signs' => 'nullable|array',
            'recovery_room_time' => 'nullable|date_format:Y-m-d H:i:s',
            'discharge_instructions' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $surgery->update([
            'status' => 'completed',
            'actual_end_time' => $request->actual_end_time,
            'closure_time' => $request->closure_time,
            'anesthesia_end_time' => $request->anesthesia_end_time,
            'post_op_notes' => $request->post_op_notes,
            'complications' => $request->complications,
            'blood_loss' => $request->blood_loss,
            'vital_signs' => $request->vital_signs,
            'recovery_room_time' => $request->recovery_room_time,
            'discharge_instructions' => $request->discharge_instructions,
            'completed_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'data' => $surgery,
            'message' => 'Surgery completed successfully'
        ]);
    }

    /**
     * Get theatre availability.
     */
    public function getTheatreAvailability(Request $request, $theatreId)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'required|integer|min:15|max:1440'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $date = $request->date;
        $duration = $request->duration;

        // Get theatre working hours
        $theatre = Theatre::findOrFail($theatreId);
        $workingHours = $this->getTheatreWorkingHours($theatre);

        // Get existing surgeries for the day
        $existingSurgeries = SurgerySchedule::where('theatre_id', $theatreId)
            ->whereDate('surgery_date', $date)
            ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
            ->get();

        // Calculate available time slots
        $availableSlots = $this->calculateTheatreSlots($workingHours, $existingSurgeries, $duration);

        return response()->json([
            'success' => true,
            'data' => [
                'theatre_id' => $theatreId,
                'date' => $date,
                'working_hours' => $workingHours,
                'available_slots' => $availableSlots
            ],
            'message' => 'Theatre availability retrieved successfully'
        ]);
    }

    /**
     * Get surgery statistics.
     */
    public function getStatistics(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(30);
        $dateTo = $request->date_to ?? now();

        $stats = [
            'total_surgeries' => SurgerySchedule::whereBetween('surgery_date', [$dateFrom, $dateTo])->count(),
            'scheduled_surgeries' => SurgerySchedule::where('status', 'scheduled')->count(),
            'completed_surgeries' => SurgerySchedule::where('status', 'completed')->whereBetween('surgery_date', [$dateFrom, $dateTo])->count(),
            'cancelled_surgeries' => SurgerySchedule::where('status', 'cancelled')->whereBetween('surgery_date', [$dateFrom, $dateTo])->count(),
            'emergency_surgeries' => SurgerySchedule::where('priority', 'emergency')->whereBetween('surgery_date', [$dateFrom, $dateTo])->count(),
            'urgent_surgeries' => SurgerySchedule::where('priority', 'urgent')->whereBetween('surgery_date', [$dateFrom, $dateTo])->count(),
            'elective_surgeries' => SurgerySchedule::where('priority', 'elective')->whereBetween('surgery_date', [$dateFrom, $dateTo])->count(),
            'surgery_types' => $this->getSurgeryTypeStats($dateFrom, $dateTo),
            'anesthesia_types' => $this->getAnesthesiaTypeStats($dateFrom, $dateTo),
            'average_duration' => $this->getAverageSurgeryDuration($dateFrom, $dateTo),
            'top_procedures' => $this->getTopProcedures($dateFrom, $dateTo),
            'theatre_utilization' => $this->getTheatreUtilization($dateFrom, $dateTo)
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Surgery statistics retrieved successfully'
        ]);
    }

    /**
     * Check theatre availability.
     */
    private function checkTheatreAvailability($theatreId, $date, $time, $duration, $excludeId = null)
    {
        $startTime = Carbon::parse($date . ' ' . $time);
        $endTime = $startTime->copy()->addMinutes($duration);

        $query = SurgerySchedule::where('theatre_id', $theatreId)
            ->whereDate('surgery_date', $date)
            ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
            ->where(function($q) use ($startTime, $endTime) {
                $q->whereBetween('surgery_time', [$startTime->format('H:i'), $endTime->format('H:i')])
                  ->orWhere(function($subQ) use ($startTime, $endTime) {
                      $subQ->whereRaw('ADDTIME(surgery_time, INTERVAL estimated_duration MINUTE) > ?', [$startTime->format('H:i')])
                           ->where('surgery_time', '<', $endTime->format('H:i'));
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $conflict = $query->first();

        if ($conflict) {
            return [
                'conflicting_surgery' => $conflict,
                'conflict_time' => $conflict->surgery_time,
                'conflict_duration' => $conflict->estimated_duration
            ];
        }

        return null;
    }

    /**
     * Get theatre working hours.
     */
    private function getTheatreWorkingHours($theatre)
    {
        // This would typically come from a theatre_schedules table
        // For now, return default working hours
        return [
            'start_time' => '08:00',
            'end_time' => '18:00',
            'break_start' => '12:00',
            'break_end' => '13:00'
        ];
    }

    /**
     * Calculate available theatre slots.
     */
    private function calculateTheatreSlots($workingHours, $existingSurgeries, $duration)
    {
        $slots = [];
        $startTime = Carbon::parse($workingHours['start_time']);
        $endTime = Carbon::parse($workingHours['end_time']);
        $breakStart = Carbon::parse($workingHours['break_start']);
        $breakEnd = Carbon::parse($workingHours['break_end']);

        $currentTime = $startTime->copy();
        $slotDuration = 30; // 30-minute slots

        while ($currentTime->lt($endTime)) {
            // Skip break time
            if ($currentTime->between($breakStart, $breakEnd)) {
                $currentTime = $breakEnd->copy();
                continue;
            }

            $slotEnd = $currentTime->copy()->addMinutes($slotDuration);
            
            // Check if slot is available for the required duration
            $isAvailable = true;
            foreach ($existingSurgeries as $surgery) {
                $surgeryStart = Carbon::parse($surgery->surgery_time);
                $surgeryEnd = $surgeryStart->copy()->addMinutes($surgery->estimated_duration);
                
                if ($currentTime->between($surgeryStart, $surgeryEnd) || $slotEnd->between($surgeryStart, $surgeryEnd)) {
                    $isAvailable = false;
                    break;
                }
            }

            if ($isAvailable) {
                $slots[] = [
                    'time' => $currentTime->format('H:i'),
                    'duration' => $slotDuration,
                    'available' => true
                ];
            }

            $currentTime->addMinutes($slotDuration);
        }

        return $slots;
    }

    /**
     * Get surgery type statistics.
     */
    private function getSurgeryTypeStats($dateFrom, $dateTo)
    {
        return SurgerySchedule::whereBetween('surgery_date', [$dateFrom, $dateTo])
            ->selectRaw('surgery_type, COUNT(*) as count')
            ->groupBy('surgery_type')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Get anesthesia type statistics.
     */
    private function getAnesthesiaTypeStats($dateFrom, $dateTo)
    {
        return SurgerySchedule::whereBetween('surgery_date', [$dateFrom, $dateTo])
            ->selectRaw('anesthesia_type, COUNT(*) as count')
            ->groupBy('anesthesia_type')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Get average surgery duration.
     */
    private function getAverageSurgeryDuration($dateFrom, $dateTo)
    {
        $surgeries = SurgerySchedule::whereBetween('surgery_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->whereNotNull('actual_start_time')
            ->whereNotNull('actual_end_time')
            ->get();

        if ($surgeries->isEmpty()) {
            return 0;
        }

        $totalMinutes = $surgeries->sum(function($surgery) {
            return Carbon::parse($surgery->actual_start_time)->diffInMinutes(Carbon::parse($surgery->actual_end_time));
        });

        return round($totalMinutes / $surgeries->count(), 2);
    }

    /**
     * Get top procedures.
     */
    private function getTopProcedures($dateFrom, $dateTo)
    {
        return SurgerySchedule::whereBetween('surgery_date', [$dateFrom, $dateTo])
            ->join('surgery_procedures', 'surgery_schedules.procedure_id', '=', 'surgery_procedures.id')
            ->selectRaw('surgery_procedures.name, surgery_procedures.procedure_type, COUNT(*) as count')
            ->groupBy('surgery_procedures.id', 'surgery_procedures.name', 'surgery_procedures.procedure_type')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get theatre utilization.
     */
    private function getTheatreUtilization($dateFrom, $dateTo)
    {
        return SurgerySchedule::whereBetween('surgery_date', [$dateFrom, $dateTo])
            ->join('theatres', 'surgery_schedules.theatre_id', '=', 'theatres.id')
            ->selectRaw('theatres.name, COUNT(*) as surgery_count, SUM(estimated_duration) as total_minutes')
            ->groupBy('theatres.id', 'theatres.name')
            ->orderBy('surgery_count', 'desc')
            ->get();
    }

    /**
     * Remove the specified surgery schedule.
     */
    public function destroy($id)
    {
        try {
            $surgery = SurgerySchedule::findOrFail($id);

            // Check if surgery can be deleted
            if (in_array($surgery->status, ['in_progress', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete surgery that is in progress or completed'
                ], 422);
            }

            $surgery->delete();

            return response()->json([
                'success' => true,
                'message' => 'Surgery schedule deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Surgery schedule not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting surgery schedule: ' . $e->getMessage()
            ], 500);
        }
    }
}
