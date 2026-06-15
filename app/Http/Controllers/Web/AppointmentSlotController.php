<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppointmentSlot;
use App\Models\DoctorSchedule;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentSlotController extends Controller
{
    /**
     * Display a listing of appointment slots.
     */
    public function index(Request $request)
    {
        $query = AppointmentSlot::with(['doctor', 'branch'])
            ->orderBy('slot_date', 'desc')
            ->orderBy('start_time', 'asc');

        // Filter by doctor
        if ($request->has('doctor_id') && $request->doctor_id) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // Filter by branch
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->where('slot_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->where('slot_date', '<=', $request->date_to);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by appointment type
        if ($request->has('appointment_type') && $request->appointment_type) {
            $query->where('appointment_type', $request->appointment_type);
        }

        // Check if "Show All" is requested
        $showAll = $request->boolean('show_all', false);
        
        if ($showAll) {
            $allSlots = $query->get();
            $total = $allSlots->count();
            // Convert collection to LengthAwarePaginator for consistent view usage
            $slots = new \Illuminate\Pagination\LengthAwarePaginator(
                $allSlots,
                $total > 0 ? $total : 1,
                $total > 0 ? $total : 1,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
        $slots = $query->paginate(20);
        }
        
        // Get doctors and branches for filters
        $doctors = User::role('doctor')->where('is_active', true)->get();
        $branches = Branch::all();

        return view('appointments.slots.index', compact('slots', 'doctors', 'branches', 'showAll'));
    }

    /**
     * Show the form for creating new appointment slots.
     */
    public function create()
    {
        $doctors = User::role('doctor')->where('is_active', true)->get();
        $branches = Branch::all();
        
        return view('appointments.slots.create', compact('doctors', 'branches'));
    }

    /**
     * Store newly created appointment slots.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'doctor_id' => 'required|exists:users,id',
                'branch_id' => 'required|exists:branches,id',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
                'appointment_type' => 'required|in:in-person,teleconsultation,both',
                'slot_duration' => 'required|integer|min:15|max:120',
                'max_appointments' => 'required|integer|min:1|max:10',
                'fee_in_person' => 'nullable|numeric|min:0',
                'fee_teleconsultation' => 'nullable|numeric|min:0',
            ]);

            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $createdSlots = [];
            $daysWithoutSchedule = [];
            $daysWithSchedule = [];

            // Check if doctor has any schedules for this branch
            $hasAnySchedule = DoctorSchedule::where('doctor_id', $request->doctor_id)
                ->where('branch_id', $request->branch_id)
                ->where('is_available', true)
                ->effective()
                ->exists();

            if (!$hasAnySchedule) {
                // No schedules exist - create slots using default working hours (8 AM - 6 PM)
                $defaultStartTime = '08:00';
                $defaultEndTime = '18:00';
                $breakStartTime = '12:00';
                $breakEndTime = '14:00';
                
                $currentDate = $startDate->copy();
                while ($currentDate->lte($endDate)) {
                    // Skip weekends by default (can be configured later)
                    $dayOfWeek = strtolower($currentDate->format('l'));
                    $isWeekend = in_array($dayOfWeek, ['saturday', 'sunday']);
                    
                    if (!$isWeekend) {
                        // Generate slots for working days (Monday-Friday)
                        $slots = $this->generateDefaultSlots(
                            $defaultStartTime,
                            $defaultEndTime,
                            $breakStartTime,
                            $breakEndTime,
                            $request->slot_duration
                        );
                        
                        $appointmentTypes = $request->appointment_type === 'both' 
                            ? ['in-person', 'teleconsultation'] 
                            : [$request->appointment_type];

                        foreach ($slots as $slotData) {
                            foreach ($appointmentTypes as $type) {
                                // Check if slot already exists to prevent duplicates
                                $existingSlot = AppointmentSlot::where('doctor_id', $request->doctor_id)
                                    ->where('branch_id', $request->branch_id)
                                    ->where('slot_date', $currentDate->format('Y-m-d'))
                                    ->where('start_time', $slotData['start_time'])
                                    ->where('appointment_type', $type)
                                    ->first();
                                
                                if (!$existingSlot) {
                                    $fee = $type === 'teleconsultation'
                                        ? ($request->fee_teleconsultation ?? $request->fee_in_person)
                                        : ($request->fee_in_person ?? $request->fee_teleconsultation);
                                    $slot = AppointmentSlot::create([
                                        'doctor_id' => $request->doctor_id,
                                        'branch_id' => $request->branch_id,
                                        'slot_date' => $currentDate->format('Y-m-d'),
                                        'start_time' => $slotData['start_time'],
                                        'end_time' => $slotData['end_time'],
                                        'duration' => $request->slot_duration,
                                        'max_appointments' => $request->max_appointments,
                                        'appointment_type' => $type,
                                        'fee' => $fee !== null && $fee !== '' ? (float) $fee : null,
                                        'currency' => 'GHS',
                                        'status' => 'available',
                                        'notes' => $request->notes ?? 'Auto-generated slot (no schedule configured)',
                                        'created_by' => auth()->id(),
                                    ]);

                                    $createdSlots[] = $slot;
                                }
                            }
                        }
                    }
                    
                    $currentDate->addDay();
                }
                
                $message = 'Created ' . count($createdSlots) . ' appointment slot(s) successfully!';
                if (count($createdSlots) > 0) {
                    $message .= ' Note: Doctor schedule not configured. Used default working hours (8 AM - 6 PM, Monday-Friday).';
                }
                
                return redirect()->route('appointments.slots.index')
                    ->with('success', $message)
                    ->with('warning', count($createdSlots) == 0 ? 'No slots created. Please ensure the date range includes working days (Monday-Friday) or configure doctor schedules.' : null);
            }

            // Doctor has schedules - use schedule-based slot generation
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $dayOfWeek = strtolower($currentDate->format('l'));
                
                // Get doctor schedule for this day
                $schedule = DoctorSchedule::where('doctor_id', $request->doctor_id)
                    ->where('branch_id', $request->branch_id)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_available', true)
                    ->effective()
                    ->first();

                if ($schedule) {
                    $daysWithSchedule[] = $currentDate->format('Y-m-d');
                    $slots = $schedule->getAvailableSlots($currentDate);
                    
                    $appointmentTypes = $request->appointment_type === 'both' 
                        ? ['in-person', 'teleconsultation'] 
                        : [$request->appointment_type];

                    foreach ($slots as $slotData) {
                        foreach ($appointmentTypes as $type) {
                            // Check if slot already exists to prevent duplicates
                            $existingSlot = AppointmentSlot::where('doctor_id', $request->doctor_id)
                                ->where('branch_id', $request->branch_id)
                                ->where('slot_date', $currentDate->format('Y-m-d'))
                                ->where('start_time', $slotData['start_time'])
                                ->where('appointment_type', $type)
                                ->first();
                            
                            if (!$existingSlot) {
                                $fee = $type === 'teleconsultation'
                                    ? ($request->fee_teleconsultation ?? $request->fee_in_person)
                                    : ($request->fee_in_person ?? $request->fee_teleconsultation);
                                $slot = AppointmentSlot::create([
                                    'doctor_id' => $request->doctor_id,
                                    'branch_id' => $request->branch_id,
                                    'slot_date' => $currentDate->format('Y-m-d'),
                                    'start_time' => $slotData['start_time'],
                                    'end_time' => $slotData['end_time'],
                                    'duration' => $request->slot_duration ?? $slotData['duration'],
                                    'max_appointments' => $request->max_appointments ?? $slotData['max_appointments'],
                                    'appointment_type' => $type,
                                    'fee' => $fee !== null && $fee !== '' ? (float) $fee : null,
                                    'currency' => 'GHS',
                                    'status' => 'available',
                                    'notes' => $request->notes,
                                    'created_by' => auth()->id(),
                                ]);

                                $createdSlots[] = $slot;
                            }
                        }
                    }
                } else {
                    $daysWithoutSchedule[] = $currentDate->format('Y-m-d');
                }

                $currentDate->addDay();
            }

            // Build response message
            $message = 'Created ' . count($createdSlots) . ' appointment slot(s) successfully!';
            
            if (count($daysWithoutSchedule) > 0 && count($createdSlots) > 0) {
                $message .= ' Note: ' . count($daysWithoutSchedule) . ' day(s) had no schedule configured and were skipped.';
            } elseif (count($createdSlots) == 0) {
                return redirect()->route('appointments.slots.index')
                    ->with('error', 'No appointment slots were created. The selected doctor does not have schedules configured for any days in the selected date range. Please configure doctor schedules first or select a different date range.');
            }

            return redirect()->route('appointments.slots.index')
                ->with('success', $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating appointment slots: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to create appointment slots: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate default time slots when no doctor schedule exists.
     */
    private function generateDefaultSlots(string $startTime, string $endTime, string $breakStart, string $breakEnd, int $duration): array
    {
        $slots = [];
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $breakStartTime = Carbon::parse($breakStart);
        $breakEndTime = Carbon::parse($breakEnd);
        
        $currentTime = $start->copy();
        
        while ($currentTime->lt($end)) {
            $slotEnd = $currentTime->copy()->addMinutes($duration);
            
            // Skip if slot would go beyond end time
            if ($slotEnd->gt($end)) {
                break;
            }
            
            // Skip if slot is during break time
            if ($currentTime->between($breakStartTime, $breakEndTime) || 
                $slotEnd->between($breakStartTime, $breakEndTime) ||
                ($currentTime->lt($breakStartTime) && $slotEnd->gt($breakEndTime))) {
                $currentTime->addMinutes($duration);
                continue;
            }
            
            $slots[] = [
                'start_time' => $currentTime->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
                'duration' => $duration,
            ];
            
            $currentTime->addMinutes($duration);
        }
        
        return $slots;
    }

    /**
     * Show the form for editing an appointment slot.
     */
    public function edit($id)
    {
        $slot = AppointmentSlot::findOrFail($id);
        $doctors = User::role('doctor')->where('is_active', true)->get();
        $branches = Branch::all();
        
        return view('appointments.slots.edit', compact('slot', 'doctors', 'branches'));
    }

    /**
     * Update the specified appointment slot.
     */
    public function update(Request $request, $id)
    {
        try {
            $slot = AppointmentSlot::findOrFail($id);

            $request->validate([
                'slot_date' => 'required|date',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'duration' => 'required|integer|min:15|max:120',
                'max_appointments' => 'required|integer|min:1|max:10',
                'status' => 'required|in:available,booked,blocked,maintenance',
                'fee' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
            ]);

            $slot->update([
                'slot_date' => $request->slot_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'duration' => (int) $request->duration,
                'max_appointments' => (int) $request->max_appointments,
                'status' => $request->status,
                'fee' => $request->filled('fee') ? (float) $request->fee : null,
                'notes' => $request->notes,
                'updated_by' => auth()->id(),
            ]);

            return redirect()->route('appointments.slots.index')
                ->with('success', 'Appointment slot updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating appointment slot: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'slot_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update appointment slot. Please try again.');
        }
    }

    /**
     * Remove the specified appointment slot.
     */
    public function destroy($id)
    {
        // Check permission
        if (!auth()->user()->can('delete_appointments')) {
            abort(403, 'Unauthorized action. You do not have permission to delete appointment slots.');
        }

        try {
            $slot = AppointmentSlot::findOrFail($id);
            
            if ($slot->booked_appointments > 0) {
                return redirect()->route('appointments.slots.index')
                    ->with('error', 'Cannot delete slot with existing bookings!');
            }

            $slot->delete();

            Log::info('Deleted appointment slot', [
                'user_id' => auth()->id(),
                'slot_id' => $id,
            ]);

            return redirect()->route('appointments.slots.index')
                ->with('success', 'Appointment slot deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting appointment slot: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'slot_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete appointment slot. Please try again.');
        }
    }

    /**
     * Bulk delete slots
     */
    public function bulkDelete(Request $request)
    {
        // Check permission
        if (!$request->user()->can('delete_appointments')) {
            abort(403, 'Unauthorized action. You do not have permission to delete appointment slots.');
        }

        try {
            // Check if delete all matching records is requested
            $deleteAll = $request->has('delete_all') && $request->delete_all == '1';
            
            if ($deleteAll) {
                // Build the same query as index method to get all matching slots
                $query = AppointmentSlot::query();

                // Apply same filters as index method
                if ($request->has('doctor_id') && $request->doctor_id) {
                    $query->where('doctor_id', $request->doctor_id);
                }

                if ($request->has('branch_id') && $request->branch_id) {
                    $query->where('branch_id', $request->branch_id);
                }

                if ($request->has('date_from') && $request->date_from) {
                    $query->where('slot_date', '>=', $request->date_from);
                }

                if ($request->has('date_to') && $request->date_to) {
                    $query->where('slot_date', '<=', $request->date_to);
                }

                if ($request->has('status') && $request->status) {
                    $query->where('status', $request->status);
                }

                if ($request->has('appointment_type') && $request->appointment_type) {
                    $query->where('appointment_type', $request->appointment_type);
                }

                // Only get slots without bookings
                $slots = $query->where('booked_appointments', 0)->get();
                
                $deleted = 0;
                $skipped = 0;
                $skippedIds = [];

                foreach ($slots as $slot) {
                    // Double-check before deletion (defensive programming)
                    if ($slot->booked_appointments == 0) {
                        $slot->delete();
                        $deleted++;
                    } else {
                        // This shouldn't happen since we filtered, but log if it does
                        $skipped++;
                        $skippedIds[] = $slot->id;
                        Log::warning('Slot with bookings found during delete all operation', [
                            'slot_id' => $slot->id,
                            'booked_appointments' => $slot->booked_appointments,
                        ]);
                    }
                }

                $message = "Successfully deleted {$deleted} slot(s) matching your filters.";
                if ($skipped > 0) {
                    $message .= " {$skipped} slot(s) were skipped because they have existing bookings.";
                } else if ($deleted == 0) {
                    $message = "No slots found matching your filters that can be deleted (all have existing bookings).";
                }

                Log::info('Bulk deleted all matching appointment slots', [
                    'user_id' => auth()->id(),
                    'deleted_count' => $deleted,
                    'skipped_count' => $skipped,
                    'filters' => $request->except(['_token', 'delete_all']),
                    'skipped_ids' => $skippedIds,
                ]);
            } else {
                // Delete specific slot IDs
        $request->validate([
                    'slot_ids' => 'required|array|min:1',
            'slot_ids.*' => 'exists:appointment_slots,id',
        ]);

        $deleted = 0;
        $skipped = 0;
                $skippedIds = [];

        foreach ($request->slot_ids as $slotId) {
            $slot = AppointmentSlot::find($slotId);
            if ($slot && $slot->booked_appointments == 0) {
                $slot->delete();
                $deleted++;
            } else {
                $skipped++;
                        if ($slot) {
                            $skippedIds[] = $slot->id;
                        }
            }
        }

                $message = "Successfully deleted {$deleted} slot(s).";
        if ($skipped > 0) {
                    $message .= " {$skipped} slot(s) were skipped because they have existing bookings.";
                }

                Log::info('Bulk deleted appointment slots', [
                    'user_id' => auth()->id(),
                    'deleted_count' => $deleted,
                    'skipped_count' => $skipped,
                    'deleted_ids' => $request->slot_ids,
                    'skipped_ids' => $skippedIds,
                ]);
            }

            // Preserve filters in redirect
            $redirectParams = $request->except(['_token', 'slot_ids', 'delete_all']);
            if (!empty($redirectParams)) {
                return redirect()->route('appointments.slots.index', $redirectParams)->with('success', $message);
        }

        return redirect()->route('appointments.slots.index')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error bulk deleting appointment slots: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('appointments.slots.index')
                ->with('error', 'Failed to delete appointment slots. Please try again.');
        }
    }

    /**
     * Block a slot
     */
    public function block(Request $request, $id)
    {
        $slot = AppointmentSlot::findOrFail($id);
        
        $slot->update([
            'status' => 'blocked',
            'notes' => $request->reason ?? 'Blocked by administrator',
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Slot blocked successfully!');
    }

    /**
     * Unblock a slot
     */
    public function unblock($id)
    {
        $slot = AppointmentSlot::findOrFail($id);
        
        $slot->update([
            'status' => 'available',
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Slot unblocked successfully!');
    }
}

