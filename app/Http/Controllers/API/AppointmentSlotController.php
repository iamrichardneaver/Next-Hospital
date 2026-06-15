<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AppointmentSlot;
use App\Models\DoctorSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AppointmentSlotController extends Controller
{
    /**
     * Display a listing of appointment slots.
     */
    public function index(Request $request)
    {
        $query = AppointmentSlot::with(['doctor', 'branch', 'creator'])
            ->orderBy('slot_date', 'desc')
            ->orderBy('start_time', 'asc');

        // Filter by doctor
        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->where('slot_date', $request->date);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('slot_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('slot_date', '<=', $request->date_to);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by appointment type
        if ($request->has('appointment_type')) {
            $query->where('appointment_type', $request->appointment_type);
        }

        // Filter by availability
        if ($request->boolean('available_only')) {
            $query->available();
        }

        // Filter by future slots only
        if ($request->boolean('future_only', true)) {
            $query->future();
        }

        $slots = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $slots,
            'message' => 'Appointment slots retrieved successfully'
        ]);
    }

    /**
     * Store a newly created appointment slot.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'doctor_id' => 'required|exists:users,id',
                'branch_id' => 'required|exists:branches,id',
                'slot_date' => 'required|date|after_or_equal:today',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'duration' => 'integer|min:15|max:120',
                'max_appointments' => 'integer|min:1|max:10',
                'status' => 'in:available,booked,blocked,maintenance',
                'fee' => 'nullable|numeric|min:0',
                'currency' => 'string|size:3',
                'appointment_type' => 'required|in:in-person,teleconsultation',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for conflicts
            $conflict = $this->checkSlotConflict(
                $request->doctor_id,
                $request->slot_date,
                $request->start_time,
                $request->end_time
            );

            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Slot conflict detected',
                    'conflict' => $conflict
                ], 409);
            }

            $slot = AppointmentSlot::create([
                'doctor_id' => $request->doctor_id,
                'branch_id' => $request->branch_id,
                'slot_date' => $request->slot_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'duration' => $request->duration ?? 30,
                'max_appointments' => $request->max_appointments ?? 1,
                'status' => $request->status ?? 'available',
                'fee' => $request->fee,
                'currency' => $request->currency ?? 'GHS',
                'appointment_type' => $request->appointment_type,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $slot->load(['doctor', 'branch']),
                'message' => 'Appointment slot created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating appointment slot: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating appointment slot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified appointment slot.
     */
    public function show($id)
    {
        $slot = AppointmentSlot::with(['doctor', 'branch', 'creator', 'updater'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $slot,
            'message' => 'Appointment slot retrieved successfully'
        ]);
    }

    /**
     * Update the specified appointment slot.
     */
    public function update(Request $request, $id)
    {
        try {
            $slot = AppointmentSlot::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'start_time' => 'sometimes|date_format:H:i',
                'end_time' => 'sometimes|date_format:H:i|after:start_time',
                'duration' => 'sometimes|integer|min:15|max:120',
                'max_appointments' => 'sometimes|integer|min:1|max:10',
                'status' => 'sometimes|in:available,booked,blocked,maintenance',
                'fee' => 'nullable|numeric|min:0',
                'currency' => 'sometimes|string|size:3',
                'appointment_type' => 'sometimes|in:in-person,teleconsultation',
                'notes' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Use only validated data for security (prevent mass assignment vulnerabilities)
            $slot->update(array_merge($validator->validated(), [
                'updated_by' => auth()->id(),
            ]));

            return response()->json([
                'success' => true,
                'data' => $slot->load(['doctor', 'branch']),
                'message' => 'Appointment slot updated successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment slot not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating appointment slot: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'slot_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating appointment slot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified appointment slot.
     */
    public function destroy($id)
    {
        try {
            $slot = AppointmentSlot::findOrFail($id);
            $slot->delete();

            return response()->json([
                'success' => true,
                'message' => 'Appointment slot deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment slot not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting appointment slot: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'slot_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting appointment slot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available slots for booking.
     */
    public function getAvailableSlots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date|after_or_equal:today',
            'appointment_type' => 'in:in-person,teleconsultation',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = AppointmentSlot::with(['doctor', 'branch'])
            ->where('doctor_id', $request->doctor_id)
            ->where('branch_id', $request->branch_id)
            ->where('slot_date', $request->date)
            ->available()
            ->orderBy('start_time');

        if ($request->has('appointment_type')) {
            $query->where('appointment_type', $request->appointment_type);
        }

        $slots = $query->get();

        return response()->json([
            'success' => true,
            'data' => $slots,
            'message' => 'Available slots retrieved successfully'
        ]);
    }

    /**
     * Block a slot (make it unavailable).
     */
    public function blockSlot(Request $request, $id)
    {
        $slot = AppointmentSlot::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $slot->update([
            'status' => 'blocked',
            'notes' => $request->reason,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $slot->load(['doctor', 'branch']),
            'message' => 'Slot blocked successfully'
        ]);
    }

    /**
     * Unblock a slot (make it available).
     */
    public function unblockSlot($id)
    {
        $slot = AppointmentSlot::findOrFail($id);

        $slot->update([
            'status' => 'available',
            'notes' => null,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $slot->load(['doctor', 'branch']),
            'message' => 'Slot unblocked successfully'
        ]);
    }

    /**
     * Bulk create slots for a date range.
     */
    public function bulkCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'appointment_type' => 'required|in:in-person,teleconsultation',
            'slot_duration' => 'integer|min:15|max:120',
            'max_appointments' => 'integer|min:1|max:10',
            'fee' => 'nullable|numeric|min:0',
            'currency' => 'string|size:3',
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
        $createdSlots = [];

        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dayOfWeek = strtolower($currentDate->format('l'));
            
            // Get doctor schedule for this day
            $schedule = DoctorSchedule::where('doctor_id', $request->doctor_id)
                ->where('branch_id', $request->branch_id)
                ->where('day_of_week', $dayOfWeek)
                ->effective()
                ->available()
                ->first();

            if ($schedule) {
                $slots = $schedule->getAvailableSlots($currentDate);
                
                foreach ($slots as $slotData) {
                    $slot = AppointmentSlot::create([
                        'doctor_id' => $request->doctor_id,
                        'branch_id' => $request->branch_id,
                        'slot_date' => $currentDate->format('Y-m-d'),
                        'start_time' => $slotData['start_time'],
                        'end_time' => $slotData['end_time'],
                        'duration' => $request->slot_duration ?? $slotData['duration'],
                        'max_appointments' => $request->max_appointments ?? $slotData['max_appointments'],
                        'appointment_type' => $request->appointment_type,
                        'fee' => $request->fee,
                        'currency' => $request->currency ?? 'GHS',
                        'status' => 'available',
                        'created_by' => auth()->id(),
                    ]);

                    $createdSlots[] = $slot;
                }
            }

            $currentDate->addDay();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'created_slots' => $createdSlots,
                'total_created' => count($createdSlots),
                'date_range' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ],
            ],
            'message' => 'Slots created successfully'
        ]);
    }

    /**
     * Get slot statistics.
     */
    public function getStatistics(Request $request)
    {
        $query = AppointmentSlot::query();

        // Filter by doctor
        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by date range
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $query->dateRange($dateFrom, $dateTo);

        $stats = [
            'total_slots' => $query->count(),
            'available_slots' => $query->clone()->where('status', 'available')->count(),
            'booked_slots' => $query->clone()->where('status', 'booked')->count(),
            'blocked_slots' => $query->clone()->where('status', 'blocked')->count(),
            'maintenance_slots' => $query->clone()->where('status', 'maintenance')->count(),
            'in_person_slots' => $query->clone()->where('appointment_type', 'in-person')->count(),
            'teleconsultation_slots' => $query->clone()->where('appointment_type', 'teleconsultation')->count(),
            'total_appointments' => $query->clone()->sum('booked_appointments'),
            'utilization_rate' => 0,
        ];

        if ($stats['total_slots'] > 0) {
            $stats['utilization_rate'] = round(($stats['total_appointments'] / ($stats['total_slots'] * $query->clone()->avg('max_appointments'))) * 100, 2);
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Slot statistics retrieved successfully'
        ]);
    }

    /**
     * Check for slot conflicts.
     */
    private function checkSlotConflict($doctorId, $date, $startTime, $endTime)
    {
        $conflict = AppointmentSlot::where('doctor_id', $doctorId)
            ->where('slot_date', $date)
            ->where(function($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function($subQ) use ($startTime, $endTime) {
                      $subQ->where('start_time', '<=', $startTime)
                           ->where('end_time', '>=', $endTime);
                  });
            })
            ->first();

        if ($conflict) {
            return [
                'conflicting_slot' => $conflict,
                'conflict_time' => $conflict->start_time . ' - ' . $conflict->end_time
            ];
        }

        return null;
    }
}
