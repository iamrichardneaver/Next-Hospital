<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DoctorScheduleController extends Controller
{
    /**
     * Display a listing of doctor schedules.
     */
    public function index(Request $request)
    {
        $query = DoctorSchedule::with(['doctor', 'branch', 'creator'])
            ->orderBy('doctor_id')
            ->orderBy('day_of_week');

        // If user is a doctor, show only their schedules (self-service)
        if (auth()->user()->hasRole('doctor')) {
            $query->where('doctor_id', auth()->id());
        } else {
            // Filter by doctor (for admins)
            if ($request->has('doctor_id')) {
                $query->where('doctor_id', $request->doctor_id);
            }
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by day of week
        if ($request->has('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        // Filter by availability
        if ($request->has('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }

        // Filter by effective schedules only
        if ($request->boolean('effective_only', true)) {
            $query->effective();
        }

        $schedules = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $schedules,
            'message' => 'Doctor schedules retrieved successfully'
        ]);
    }

    /**
     * Store a newly created doctor schedule.
     */
    public function store(Request $request)
    {
        try {
            // If user is a doctor, they can only create schedules for themselves
            $doctorId = auth()->user()->hasRole('doctor') ? auth()->id() : $request->doctor_id;
            
            $validator = Validator::make(array_merge($request->all(), ['doctor_id' => $doctorId]), [
                'doctor_id' => 'required|exists:users,id',
                'branch_id' => 'required|exists:branches,id',
                'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'break_start_time' => 'nullable|date_format:H:i',
                'break_end_time' => 'nullable|date_format:H:i|after:break_start_time',
                'slot_duration' => 'integer|min:15|max:120',
                'max_appointments_per_slot' => 'integer|min:1|max:10',
                'is_available' => 'boolean',
                'notes' => 'nullable|string',
                'effective_from' => 'nullable|date',
                'effective_until' => 'nullable|date|after:effective_from',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for conflicts
            $conflict = $this->checkScheduleConflict(
                $doctorId,
                $request->branch_id,
                $request->day_of_week,
                $request->effective_from
            );

            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Schedule conflict detected',
                    'conflict' => $conflict
                ], 409);
            }

        $schedule = DoctorSchedule::create([
            'doctor_id' => $doctorId,
            'branch_id' => $request->branch_id,
                'day_of_week' => $request->day_of_week,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'break_start_time' => $request->break_start_time,
                'break_end_time' => $request->break_end_time,
                'slot_duration' => $request->slot_duration ?? 30,
                'max_appointments_per_slot' => $request->max_appointments_per_slot ?? 1,
                'is_available' => $request->boolean('is_available', true),
                'notes' => $request->notes,
                'effective_from' => $request->effective_from,
                'effective_until' => $request->effective_until,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $schedule->load(['doctor', 'branch']),
                'message' => 'Doctor schedule created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating doctor schedule: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating doctor schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified doctor schedule.
     */
    public function show($id)
    {
        $schedule = DoctorSchedule::with(['doctor', 'branch', 'creator', 'updater'])->findOrFail($id);
        
        // If user is a doctor, ensure they can only view their own schedules
        if (auth()->user()->hasRole('doctor') && $schedule->doctor_id != auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You can only view your own schedules'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $schedule,
            'message' => 'Doctor schedule retrieved successfully'
        ]);
    }

    /**
     * Update the specified doctor schedule.
     */
    public function update(Request $request, $id)
    {
        try {
            $schedule = DoctorSchedule::findOrFail($id);
            
            // If user is a doctor, ensure they can only update their own schedules
            if (auth()->user()->hasRole('doctor') && $schedule->doctor_id != auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You can only update your own schedules'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'start_time' => 'sometimes|date_format:H:i',
                'end_time' => 'sometimes|date_format:H:i|after:start_time',
                'break_start_time' => 'nullable|date_format:H:i',
                'break_end_time' => 'nullable|date_format:H:i|after:break_start_time',
                'slot_duration' => 'sometimes|integer|min:15|max:120',
                'max_appointments_per_slot' => 'sometimes|integer|min:1|max:10',
                'is_available' => 'sometimes|boolean',
                'notes' => 'sometimes|string',
                'effective_from' => 'nullable|date',
                'effective_until' => 'nullable|date|after:effective_from',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // FIX: Handle checkbox - if unchecked, it won't be in request, so explicitly set to false
            // If checked, it will be in request as "1" or true
            $validated = $validator->validated();
            if ($request->has('is_available')) {
                $validated['is_available'] = $request->boolean('is_available');
            } else {
                // Checkbox not present means it's unchecked
                $validated['is_available'] = false;
            }

            // Use only validated data for security (prevent mass assignment vulnerabilities)
            $schedule->update(array_merge($validated, [
                'updated_by' => auth()->id(),
            ]));

            return response()->json([
                'success' => true,
                'data' => $schedule->load(['doctor', 'branch']),
                'message' => 'Doctor schedule updated successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor schedule not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating doctor schedule: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'schedule_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating doctor schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified doctor schedule.
     */
    public function destroy($id)
    {
        try {
            $schedule = DoctorSchedule::findOrFail($id);
            
            // If user is a doctor, ensure they can only delete their own schedules
            if (auth()->user()->hasRole('doctor') && $schedule->doctor_id != auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You can only delete your own schedules'
                ], 403);
            }
            
            $schedule->delete();

            return response()->json([
                'success' => true,
                'message' => 'Doctor schedule deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor schedule not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting doctor schedule: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'schedule_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting doctor schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available time slots for a doctor on a specific date.
     */
    public function getAvailableSlots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $date = Carbon::parse($request->date);
        $dayOfWeek = strtolower($date->format('l'));

        $schedule = DoctorSchedule::where('doctor_id', $request->doctor_id)
            ->where('branch_id', $request->branch_id)
            ->where('day_of_week', $dayOfWeek)
            ->effective()
            ->available()
            ->first();

        if (!$schedule) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No schedule found for the specified doctor and date'
            ]);
        }

        $availableSlots = $schedule->getAvailableSlots($date);

        return response()->json([
            'success' => true,
            'data' => [
                'doctor' => $schedule->doctor,
                'branch' => $schedule->branch,
                'date' => $date->format('Y-m-d'),
                'day_of_week' => $dayOfWeek,
                'schedule' => $schedule,
                'available_slots' => $availableSlots,
            ],
            'message' => 'Available slots retrieved successfully'
        ]);
    }

    /**
     * Generate appointment slots for a date range.
     */
    public function generateSlots(Request $request)
    {
        // If user is a doctor, they can only generate slots for themselves
        $doctorId = auth()->user()->hasRole('doctor') ? auth()->id() : $request->doctor_id;
        
        $validator = Validator::make(array_merge($request->all(), ['doctor_id' => $doctorId]), [
            'doctor_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'appointment_type' => 'required|in:in-person,teleconsultation',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $branchId = $request->branch_id;
        $appointmentType = $request->appointment_type;

        $generatedSlots = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dayOfWeek = strtolower($currentDate->format('l'));
            
            $schedule = DoctorSchedule::where('doctor_id', $doctorId)
                ->where('branch_id', $branchId)
                ->where('day_of_week', $dayOfWeek)
                ->effective()
                ->available()
                ->first();

            if ($schedule) {
                $slots = $schedule->getAvailableSlots($currentDate);
                
                foreach ($slots as $slot) {
                    $appointmentSlot = \App\Models\AppointmentSlot::create([
                        'doctor_id' => $doctorId,
                        'branch_id' => $branchId,
                        'slot_date' => $currentDate->format('Y-m-d'),
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                        'duration' => $slot['duration'],
                        'max_appointments' => $slot['max_appointments'],
                        'appointment_type' => $appointmentType,
                        'status' => 'available',
                        'created_by' => auth()->id(),
                    ]);

                    $generatedSlots[] = $appointmentSlot;
                }
            }

            $currentDate->addDay();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'generated_slots' => $generatedSlots,
                'total_generated' => count($generatedSlots),
                'date_range' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ],
            ],
            'message' => 'Appointment slots generated successfully'
        ]);
    }

    /**
     * Get doctor schedules for a specific week.
     */
    public function getWeeklySchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'week_start' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $weekStart = Carbon::parse($request->week_start)->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $schedules = DoctorSchedule::where('doctor_id', $request->doctor_id)
            ->where('branch_id', $request->branch_id)
            ->effective()
            ->get()
            ->groupBy('day_of_week');

        $weeklySchedule = [];
        $currentDate = $weekStart->copy();

        while ($currentDate->lte($weekEnd)) {
            $dayOfWeek = strtolower($currentDate->format('l'));
            $daySchedules = $schedules->get($dayOfWeek, collect());

            $weeklySchedule[] = [
                'date' => $currentDate->format('Y-m-d'),
                'day_of_week' => $dayOfWeek,
                'schedules' => $daySchedules,
                'is_available' => $daySchedules->isNotEmpty() && $daySchedules->where('is_available', true)->isNotEmpty(),
            ];

            $currentDate->addDay();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'doctor_id' => $request->doctor_id,
                'branch_id' => $request->branch_id,
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'weekly_schedule' => $weeklySchedule,
            ],
            'message' => 'Weekly schedule retrieved successfully'
        ]);
    }

    /**
     * Check for schedule conflicts.
     */
    private function checkScheduleConflict($doctorId, $branchId, $dayOfWeek, $effectiveFrom)
    {
        $query = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('branch_id', $branchId)
            ->where('day_of_week', $dayOfWeek);

        if ($effectiveFrom) {
            $query->where(function($q) use ($effectiveFrom) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', $effectiveFrom);
            });
        }

        return $query->first();
    }
}
