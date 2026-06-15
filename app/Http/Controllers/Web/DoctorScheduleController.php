<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DoctorScheduleController extends Controller
{
    /**
     * Display a listing of doctor schedules.
     * For doctors: shows only their own schedules
     * For admins: shows all schedules with filters
     */
    public function index(Request $request)
    {
        $query = DoctorSchedule::with(['doctor', 'branch', 'creator'])
            ->orderBy('doctor_id')
            ->orderBy('day_of_week');

        // If user is a doctor, show only their schedules
        if (auth()->user()->hasRole('doctor')) {
            $query->where('doctor_id', auth()->id());
        } else {
            // Filter by doctor (for admins)
            if ($request->has('doctor_id') && $request->doctor_id) {
                $query->where('doctor_id', $request->doctor_id);
            }
        }

        // Filter by branch
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by day of week
        if ($request->has('day_of_week') && $request->day_of_week) {
            $query->where('day_of_week', $request->day_of_week);
        }

        // Filter by availability
        if ($request->has('is_available') && $request->is_available !== '') {
            $query->where('is_available', $request->boolean('is_available'));
        }

        // Filter by effective schedules only
        if ($request->boolean('effective_only', true)) {
            $query->effective();
        }

        $schedules = $query->paginate(20);
        
        // Get doctors and branches for filters (only for admins)
        $doctors = auth()->user()->hasRole('doctor') 
            ? collect([auth()->user()]) 
            : User::role('doctor')->where('is_active', true)->get();
        $branches = Branch::all();

        return view('doctor-schedules.index', compact('schedules', 'doctors', 'branches'));
    }

    /**
     * Show the form for creating a new doctor schedule.
     */
    public function create()
    {
        // If user is a doctor, they can only create schedules for themselves
        $doctors = auth()->user()->hasRole('doctor') 
            ? collect([auth()->user()]) 
            : User::role('doctor')->where('is_active', true)->get();
        $branches = Branch::all();
        
        return view('doctor-schedules.create', compact('doctors', 'branches'));
    }

    /**
     * Store a newly created doctor schedule.
     */
    public function store(Request $request)
    {
        try {
            // SECURITY: If user is a doctor, force doctor_id to be their own ID
            // This prevents doctors from creating schedules for other doctors
            if (auth()->user()->hasRole('doctor')) {
                $request->merge(['doctor_id' => auth()->id()]);
            }

            $validated = $request->validate([
                'doctor_id' => 'required|exists:users,id',
                'branch_id' => 'required|exists:branches,id',
                'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'break_start_time' => 'nullable|date_format:H:i',
                'break_end_time' => 'nullable|date_format:H:i|after:break_start_time',
                'slot_duration' => 'required|integer|min:15|max:120',
                'max_appointments_per_slot' => 'required|integer|min:1|max:10',
                'is_available' => 'boolean',
                'notes' => 'nullable|string',
                'effective_from' => 'nullable|date',
                'effective_until' => 'nullable|date|after:effective_from',
            ]);

            // Double-check: If user is a doctor, ensure they can only create schedules for themselves
            // This is a redundant check for extra security
            if (auth()->user()->hasRole('doctor') && $validated['doctor_id'] != auth()->id()) {
                return back()->with('error', 'You can only create schedules for yourself.')->withInput();
            }

            // Check for conflicts
            $conflict = $this->checkScheduleConflict(
                $validated['doctor_id'],
                $validated['branch_id'],
                $validated['day_of_week'],
                $validated['effective_from'] ?? null
            );

            if ($conflict) {
                return back()
                    ->with('error', 'A schedule already exists for this doctor, branch, and day. Please edit the existing schedule instead.')
                    ->withInput();
            }

            $schedule = DoctorSchedule::create([
                'doctor_id' => $validated['doctor_id'],
                'branch_id' => $validated['branch_id'],
                'day_of_week' => $validated['day_of_week'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'break_start_time' => $validated['break_start_time'] ?? null,
                'break_end_time' => $validated['break_end_time'] ?? null,
                'slot_duration' => $validated['slot_duration'],
                'max_appointments_per_slot' => $validated['max_appointments_per_slot'],
                'is_available' => $request->boolean('is_available', true),
                'notes' => $validated['notes'] ?? null,
                'effective_from' => $validated['effective_from'] ?? null,
                'effective_until' => $validated['effective_until'] ?? null,
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('doctor-schedules.index')
                ->with('success', 'Doctor schedule created successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating doctor schedule: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to create doctor schedule. Please try again.');
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
            abort(403, 'You can only view your own schedules.');
        }

        return view('doctor-schedules.show', compact('schedule'));
    }

    /**
     * Show the form for editing the specified doctor schedule.
     */
    public function edit($id)
    {
        $schedule = DoctorSchedule::findOrFail($id);

        // If user is a doctor, ensure they can only edit their own schedules
        if (auth()->user()->hasRole('doctor') && $schedule->doctor_id != auth()->id()) {
            abort(403, 'You can only edit your own schedules.');
        }

        $doctors = auth()->user()->hasRole('doctor') 
            ? collect([auth()->user()]) 
            : User::role('doctor')->where('is_active', true)->get();
        $branches = Branch::all();
        
        return view('doctor-schedules.edit', compact('schedule', 'doctors', 'branches'));
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
                abort(403, 'You can only update your own schedules.');
            }

            $validated = $request->validate([
                'start_time' => 'sometimes|required|date_format:H:i',
                'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
                'break_start_time' => 'nullable|date_format:H:i',
                'break_end_time' => 'nullable|date_format:H:i|after:break_start_time',
                'slot_duration' => 'sometimes|required|integer|min:15|max:120',
                'max_appointments_per_slot' => 'sometimes|required|integer|min:1|max:10',
                'is_available' => 'sometimes|boolean',
                'notes' => 'nullable|string',
                'effective_from' => 'nullable|date',
                'effective_until' => 'nullable|date|after:effective_from',
            ]);

            // SECURITY: Doctors cannot change doctor_id, branch_id, or day_of_week via update
            // These fields are intentionally not in validation for update, and are disabled in the form
            // Only admins can change these by deleting and recreating schedules

            // FIX: Handle checkbox - if unchecked, it won't be in request, so explicitly set to false
            // If checked, it will be in request as "1" or true
            if ($request->has('is_available')) {
                $validated['is_available'] = $request->boolean('is_available');
            } else {
                // Checkbox not present means it's unchecked
                $validated['is_available'] = false;
            }

            $schedule->update(array_merge($validated, [
                'updated_by' => auth()->id(),
            ]));

            return redirect()->route('doctor-schedules.index')
                ->with('success', 'Doctor schedule updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating doctor schedule: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'schedule_id' => $id,
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update doctor schedule. Please try again.');
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
                abort(403, 'You can only delete your own schedules.');
            }

            $schedule->delete();

            return redirect()->route('doctor-schedules.index')
                ->with('success', 'Doctor schedule deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting doctor schedule: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'schedule_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete doctor schedule. Please try again.');
        }
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
