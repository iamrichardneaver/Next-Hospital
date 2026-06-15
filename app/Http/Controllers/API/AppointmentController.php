<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\Branch;
use App\Models\AppointmentSlot;
use App\Models\AppointmentFee;
use App\Models\DoctorSchedule;
use App\Models\Teleconsultation;
use App\Services\JitsiService;
use App\Services\ModulePricingService;
use App\Services\AppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    protected $jitsiService;
    protected AppNotificationService $appNotificationService;

    public function __construct(JitsiService $jitsiService, AppNotificationService $appNotificationService)
    {
        $this->jitsiService = $jitsiService;
        $this->appNotificationService = $appNotificationService;
    }
    /**
     * Display a listing of appointments.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Appointment::with(['patient', 'doctor', 'branch', 'slot'])
            ->orderBy('id', 'desc');

        // Role-based data filtering
        if ($user->hasRole('patient')) {
            // Patients can only see their own appointments
            $patient = Patient::where('user_id', $user->id)->first();
            $query->where('patient_id', $patient ? $patient->id : 0);
        } elseif ($user->hasRole('doctor')) {
            // Doctors can see their own appointments and patients from their branch
            $query->where(function ($q) use ($user) {
                $q->where('doctor_id', $user->id)
                    ->orWhereHas('patient', function ($patientQuery) use ($user) {
                        if ($user->staffProfile && $user->staffProfile->branch_id) {
                            $patientQuery->where('branch_id', $user->staffProfile->branch_id);
                        }
                    });
            });
        } elseif ($user->hasRole(['nurse', 'pharmacist', 'receptionist', 'lab_technician'])) {
            // Other medical staff can see appointments from their branch
            if ($user->staffProfile && $user->staffProfile->branch_id) {
                $query->where('branch_id', $user->staffProfile->branch_id);
            }
        }
        // Super admin and other roles can see all appointments

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by doctor
        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // Filter by patient
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // Filter by branch (only for non-patient roles)
        if (!$user->hasRole('patient') && $request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by appointment type
        if ($request->has('appointment_type')) {
            $query->where('appointment_type', $request->appointment_type);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('appointment_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('appointment_date', '<=', $request->date_to);
        }

        // Search by patient name
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('patient', function ($patientQuery) use ($search) {
                $patientQuery->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('patient_number', 'like', "%{$search}%");
            });
        }

        $appointments = $query->paginate(20);

        // Transform the data to match frontend expectations
        $transformedAppointments = $appointments->getCollection()->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'patient_name' => $appointment->patient ? $appointment->patient->full_name : 'Unknown Patient',
                'doctor_id' => $appointment->doctor_id,
                'doctor_name' => $appointment->doctor ? $appointment->doctor->name : 'Unknown Doctor',
                'department' => $appointment->doctor ? $appointment->doctor->department : 'General',
                'date' => $appointment->appointment_date,
                'time' => $appointment->appointment_time,
                'reason' => $appointment->reason,
                'status' => $appointment->status,
                'appointment_type' => $appointment->appointment_type,
                'notes' => $appointment->notes,
                'created_at' => $appointment->created_at,
                'updated_at' => $appointment->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedAppointments,
            'meta' => [
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage(),
                'per_page' => $appointments->perPage(),
                'total' => $appointments->total(),
            ],
            'message' => 'Appointments retrieved successfully'
        ]);
    }

    /**
     * Store a newly created appointment.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // SECURITY: If user is a patient, automatically set patient_id from their patient record
        // This prevents patients from creating appointments for other patients
        if ($user && $user->hasRole('patient')) {
            $userPatient = $user->patient ?? Patient::where('user_id', $user->id)->first();
            
            if (!$userPatient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient record not found. Please complete your profile registration first.'
                ], 404);
            }
            
            // Automatically set patient_id to the logged-in patient's ID
            // This ensures patients can only create appointments for themselves
            $request->merge(['patient_id' => $userPatient->id]);
        }
        
        // SECURITY: If user is a doctor, force doctor_id to be their own ID
        if ($user && $user->hasRole('doctor')) {
            $request->merge(['doctor_id' => $user->id]);
        }
        
        // Validation rules - patient_id is required for non-patient users (staff/admin)
        // For patient users, it's automatically set above
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'appointment_type' => 'required|in:in-person,teleconsultation',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'slot_id' => 'nullable|exists:appointment_slots,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Additional security check: If user is a patient, ensure they can't override the patient_id
        // (This is a double-check in case someone tries to send patient_id in the request)
        if ($user && $user->hasRole('patient')) {
            $userPatient = $user->patient ?? Patient::where('user_id', $user->id)->first();
            if ($userPatient && $userPatient->id != $request->patient_id) {
                Log::warning('Patient attempted to create appointment for different patient', [
                    'user_id' => $user->id,
                    'requested_patient_id' => $request->patient_id,
                    'user_patient_id' => $userPatient->id
                ]);
                
                // Force the correct patient_id
                $request->merge(['patient_id' => $userPatient->id]);
            }
        }

        // If slot_id is provided, validate and use the slot
        if ($request->has('slot_id')) {
            $slot = AppointmentSlot::findOrFail($request->slot_id);

            // Validate slot is available and matches criteria
            if (!$slot->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected time slot is not available'
                ], 409);
            }

            // Format slot_date to Y-m-d string for comparison
            $slotDateFormatted = $slot->slot_date instanceof \Carbon\Carbon 
                ? $slot->slot_date->format('Y-m-d') 
                : Carbon::parse($slot->slot_date)->format('Y-m-d');
            
            // Format request appointment_date to Y-m-d for consistent comparison
            $requestDateFormatted = Carbon::parse($request->appointment_date)->format('Y-m-d');

            if (
                $slot->doctor_id != $request->doctor_id ||
                $slot->branch_id != $request->branch_id ||
                $slotDateFormatted != $requestDateFormatted ||
                $slot->appointment_type != $request->appointment_type
            ) {
                Log::warning('Slot validation failed', [
                    'slot_id' => $slot->id,
                    'slot_doctor_id' => $slot->doctor_id,
                    'request_doctor_id' => $request->doctor_id,
                    'slot_branch_id' => $slot->branch_id,
                    'request_branch_id' => $request->branch_id,
                    'slot_date' => $slotDateFormatted,
                    'request_date' => $requestDateFormatted,
                    'slot_appointment_type' => $slot->appointment_type,
                    'request_appointment_type' => $request->appointment_type,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Selected slot does not match appointment criteria',
                    'debug' => [
                        'slot_doctor_id' => $slot->doctor_id,
                        'request_doctor_id' => $request->doctor_id,
                        'slot_branch_id' => $slot->branch_id,
                        'request_branch_id' => $request->branch_id,
                        'slot_date' => $slotDateFormatted,
                        'request_date' => $requestDateFormatted,
                        'slot_appointment_type' => $slot->appointment_type,
                        'request_appointment_type' => $request->appointment_type,
                    ]
                ], 409);
            }

            // Book the slot
            if (!$slot->bookAppointment()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to book the selected time slot'
                ], 409);
            }
        } else {
            // Check for conflicts using the old method as fallback
            $conflict = $this->checkAppointmentConflict($request->doctor_id, $request->appointment_date, $request->appointment_time, 30);

            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment time conflict detected',
                    'conflict' => $conflict
                ], 409);
            }
        }

        $appointment = Appointment::create([
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'branch_id' => $request->branch_id,
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time,
            'appointment_type' => $request->appointment_type,
            'reason' => $request->reason,
            'status' => 'scheduled',
            'billing_status' => 'pending', // Set to pending so user can pay
            'notes' => $request->notes,
            'is_teleconsultation' => ($request->appointment_type === 'teleconsultation'),
            'slot_id' => $request->slot_id ?? null,
            'created_by' => auth()->id()
        ]);

        // If this is a teleconsultation appointment, create teleconsultation and Jitsi meeting
        if ($request->appointment_type === 'teleconsultation') {
            try {
                $this->createTeleconsultationForAppointment($appointment, $request->all());
            } catch (\Exception $e) {
                Log::error('Failed to create teleconsultation for API appointment', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if ($appointment->status === 'scheduled') {
            try {
                $this->appNotificationService->notifyAppointmentScheduled(
                    $appointment->load(['patient', 'doctor']),
                    auth()->id()
                );
            } catch (\Exception $e) {
                Log::error('Failed to send appointment notification: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'data' => $appointment->load(['patient', 'doctor', 'branch', 'slot', 'teleconsultation']),
            'message' => 'Appointment created successfully'
        ], 201);
    }

    /**
     * Display the specified appointment.
     */
    public function show($id)
    {
        $appointment = Appointment::with(['patient', 'doctor', 'branch', 'creator', 'slot'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $appointment,
            'message' => 'Appointment retrieved successfully'
        ]);
    }

    /**
     * Update the specified appointment.
     */
    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'appointment_date' => 'sometimes|date|after_or_equal:today',
            'appointment_time' => 'sometimes|date_format:H:i',
            'appointment_type' => 'sometimes|in:in-person,teleconsultation',
            'reason' => 'sometimes|string',
            'status' => 'sometimes|in:scheduled,completed,cancelled,no-show',
            'notes' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for conflicts if time is being changed (using default 30-minute duration)
        if ($request->has('appointment_date') || $request->has('appointment_time')) {
            $conflict = $this->checkAppointmentConflict(
                $appointment->doctor_id,
                $request->appointment_date ?? $appointment->appointment_date,
                $request->appointment_time ?? $appointment->appointment_time,
                30, // Default duration
                $id
            );

            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment time conflict detected',
                    'conflict' => $conflict
                ], 409);
            }
        }

        $appointment->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $appointment->load(['patient', 'doctor', 'branch']),
            'message' => 'Appointment updated successfully'
        ]);
    }

    /**
     * Cancel appointment.
     */
    public function cancel(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string',
            'cancelled_by' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $appointment->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason,
            'cancelled_by' => $request->cancelled_by,
            'cancelled_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => $appointment->load(['patient', 'doctor', 'branch']),
            'message' => 'Appointment cancelled successfully'
        ]);
    }

    /**
     * Mark appointment as completed.
     */
    public function complete(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'completion_notes' => 'nullable|string',
            'completed_by' => 'required|exists:users,id'
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
            $appointment->update([
                'status' => 'completed',
                'completion_notes' => $request->completion_notes,
                'completed_by' => $request->completed_by,
                'completed_at' => now()
            ]);

            // Auto-create visit if appointment is in-person
            // Check if visit already exists for this appointment by checking patient and date
            $existingVisit = \App\Models\Visit::where('patient_id', $appointment->patient_id)
                ->where('branch_id', $appointment->branch_id)
                ->whereDate('check_in_time', $appointment->appointment_date)
                ->where('status', 'active')
                ->where('visit_type', 'OPD')
                ->first();

            if ($appointment->appointment_type === 'in-person' && !$existingVisit) {
                $visit = \App\Models\Visit::create([
                    'patient_id' => $appointment->patient_id,
                    'branch_id' => $appointment->branch_id,
                    'visit_type' => 'OPD',
                    'chief_complaint' => $appointment->reason ?? 'Appointment consultation',
                    'priority' => 'routine',
                    'assigned_doctor_id' => $appointment->doctor_id,
                    'check_in_time' => Carbon::parse($appointment->appointment_date)->setTimeFromTimeString($appointment->appointment_time),
                    'status' => 'active',
                    'created_by' => $request->completed_by
                ]);

                // Note: visit_id field doesn't exist in appointments table yet
                // Visit is linked via patient_id, branch_id, and date matching
                // Future enhancement: Add visit_id column to appointments table

                // Add to OPD queue
                $lastPosition = \App\Models\Queue::where('queue_type', 'OPD')
                    ->where('branch_id', $appointment->branch_id)
                    ->where('status', '!=', 'cancelled')
                    ->max('position') ?? 0;

                \App\Models\Queue::create([
                    'visit_id' => $visit->id,
                    'patient_id' => $visit->patient_id,
                    'branch_id' => $visit->branch_id,
                    'queue_type' => 'OPD',
                    'position' => $lastPosition + 1,
                    'status' => 'waiting',
                    'queued_at' => now(),
                    'priority' => 'routine'
                ]);

                // Create draft consultation for assigned doctor
                if ($visit->assigned_doctor_id) {
                    $consultationService = app(\App\Services\ConsultationService::class);
                    $consultationService->createDraftConsultationForVisit($visit);
                }

                // Initialize workflow for visit
                try {
                    $workflowService = app(\App\Services\WorkflowService::class);
                    $workflowService->initializeWorkflow('OPD Visit', 'App\\Models\\Visit', $visit->id, $request->completed_by);
                } catch (\Exception $e) {
                    Log::warning('Failed to initialize workflow for appointment visit', [
                        'visit_id' => $visit->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            $responseData = [
                'success' => true,
                'data' => $appointment->load(['patient', 'doctor', 'branch']),
                'message' => 'Appointment completed successfully'
            ];

            // Include visit data if one was created
            if (isset($visit)) {
                $responseData['visit'] = $visit->load(['patient', 'branch', 'queues']);
                $responseData['message'] .= ' and visit created';
            } elseif ($existingVisit) {
                $responseData['visit'] = $existingVisit->load(['patient', 'branch', 'queues']);
                $responseData['message'] .= ' (visit already exists)';
            }

            return response()->json($responseData);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error completing appointment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error completing appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's appointments.
     */
    public function today(Request $request)
    {
        $query = Appointment::with(['patient', 'doctor', 'branch', 'slot'])
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_time', 'asc');

        $user = auth()->user();
        if ($user->hasRole('patient')) {
            $patient = Patient::where('user_id', $user->id)->first();
            $query->where('patient_id', $patient ? $patient->id : 0);
        } elseif ($user->hasRole('doctor')) {
            $query->where('doctor_id', $user->id);
        } elseif ($user && $user->staffProfile && $user->staffProfile->branch_id) {
            $query->where('branch_id', $user->staffProfile->branch_id);
        }

        // Filter by doctor
        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $appointments = $query->get();

        return response()->json([
            'success' => true,
            'data' => $appointments,
            'message' => 'Today\'s appointments retrieved successfully'
        ]);
    }

    /**
     * Get upcoming appointments.
     */
    public function upcoming(Request $request)
    {
        $query = Appointment::with(['patient', 'doctor', 'branch', 'slot'])
            ->whereDate('appointment_date', '>=', today())
            ->whereIn('status', ['scheduled'])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc');

        $user = auth()->user();
        if ($user->hasRole('patient')) {
            $patient = Patient::where('user_id', $user->id)->first();
            $query->where('patient_id', $patient ? $patient->id : 0);
        } elseif ($user->hasRole('doctor')) {
            $query->where('doctor_id', $user->id);
        } elseif ($user && $user->staffProfile && $user->staffProfile->branch_id) {
            $query->where('branch_id', $user->staffProfile->branch_id);
        }

        // Filter by doctor
        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // Filter by days ahead
        $daysAhead = $request->get('days_ahead', 7);
        $query->whereDate('appointment_date', '<=', now()->addDays($daysAhead));

        $appointments = $query->get();

        return response()->json([
            'success' => true,
            'data' => $appointments,
            'message' => 'Upcoming appointments retrieved successfully'
        ]);
    }

    /**
     * Get appointment statistics.
     */
    public function getStatistics(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(30);
        $dateTo = $request->date_to ?? now();

        $stats = [
            'total_appointments' => Appointment::whereBetween('appointment_date', [$dateFrom, $dateTo])->count(),
            'scheduled_appointments' => Appointment::where('status', 'scheduled')->count(),
            'completed_appointments' => Appointment::where('status', 'completed')->whereBetween('appointment_date', [$dateFrom, $dateTo])->count(),
            'cancelled_appointments' => Appointment::where('status', 'cancelled')->whereBetween('appointment_date', [$dateFrom, $dateTo])->count(),
            'no_show_appointments' => Appointment::where('status', 'no-show')->whereBetween('appointment_date', [$dateFrom, $dateTo])->count(),
            'in_person_appointments' => Appointment::where('appointment_type', 'in-person')->whereBetween('appointment_date', [$dateFrom, $dateTo])->count(),
            'teleconsultation_appointments' => Appointment::where('appointment_type', 'teleconsultation')->whereBetween('appointment_date', [$dateFrom, $dateTo])->count(),
            'appointments_today' => Appointment::whereDate('appointment_date', today())->count(),
            'appointments_this_week' => Appointment::whereBetween('appointment_date', [now()->startOfWeek(), now()->endOfWeek()])->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Appointment statistics retrieved successfully'
        ]);
    }

    /**
     * Update appointment status.
     */
    public function updateStatus(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:scheduled,completed,cancelled,no-show'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $appointment->update([
            'status' => $request->status,
            'updated_by' => auth()->id()
        ]);

        if ($request->status === 'scheduled') {
            try {
                $this->appNotificationService->notifyAppointmentScheduled(
                    $appointment->fresh(['patient', 'doctor']),
                    auth()->id()
                );
            } catch (\Exception $e) {
                Log::error('Failed to send appointment status notification: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'data' => $appointment->load(['patient', 'doctor', 'branch']),
            'message' => 'Appointment status updated successfully'
        ]);
    }

    /**
     * Get doctor availability.
     */
    public function getDoctorAvailability(Request $request, $doctorId)
    {
        $date = $request->get('date', today());

        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', ['scheduled'])
            ->orderBy('appointment_time')
            ->get();

        $availableSlots = [];
        $startTime = Carbon::parse($date . ' 08:00');
        $endTime = Carbon::parse($date . ' 17:00');
        $slotDuration = 30; // minutes

        while ($startTime->lt($endTime)) {
            $slotEnd = $startTime->copy()->addMinutes($slotDuration);

            $conflict = $appointments->where('appointment_time', $startTime->format('H:i'))->first();

            if (!$conflict) {
                $availableSlots[] = [
                    'time' => $startTime->format('H:i'),
                    'available' => true
                ];
            } else {
                $availableSlots[] = [
                    'time' => $startTime->format('H:i'),
                    'available' => false,
                    'reason' => 'Booked'
                ];
            }

            $startTime->addMinutes($slotDuration);
        }

        return response()->json([
            'success' => true,
            'data' => $availableSlots,
            'message' => 'Doctor availability retrieved successfully'
        ]);
    }

    /**
     * Remove the specified appointment.
     */
    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Appointment deleted successfully'
        ]);
    }

    /**
     * Get user's appointments (for patient dashboard).
     */
    public function getUserAppointments(Request $request)
    {
        $user = auth()->user();

        \Log::info('getUserAppointments called', [
            'user_id' => $user->id,
            'user_roles' => $user->roles->pluck('name')->toArray()
        ]);

        if (!$user->hasRole('patient')) {
            \Log::warning('User does not have patient role', ['user_id' => $user->id]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Find the patient record for this user
        $patient = Patient::where('user_id', $user->id)->first();

        \Log::info('Patient lookup result', [
            'user_id' => $user->id,
            'patient_id' => $patient ? $patient->id : null,
            'patient_number' => $patient ? $patient->patient_number : null
        ]);

        if (!$patient) {
            \Log::warning('No patient record found for user', ['user_id' => $user->id]);
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No patient record found'
            ]);
        }

        $appointments = Appointment::with(['doctor', 'branch'])
            ->where('patient_id', $patient->id)
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get();

        \Log::info('Appointments found', [
            'patient_id' => $patient->id,
            'appointments_count' => $appointments->count(),
            'appointments' => $appointments->toArray()
        ]);

        return response()->json([
            'success' => true,
            'data' => $appointments,
            'message' => 'User appointments retrieved successfully'
        ]);
    }

    /**
     * Get available time slots for a doctor on a specific date.
     */
    public function getAvailableTimeSlots(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date|after_or_equal:today',
            'appointment_type' => 'nullable|in:in-person,teleconsultation',
        ]);

        $doctorId = $request->doctor_id;
        $branchId = $request->branch_id;
        $date = $request->date;
        $appointmentType = $request->appointment_type ?? 'in-person';

        // Get available appointment slots for the requested branch/date
        $slots = AppointmentSlot::where('doctor_id', $doctorId)
            ->where('branch_id', $branchId)
            ->where('slot_date', $date)
            ->where('appointment_type', $appointmentType)
            ->available()
            ->orderBy('start_time')
            ->get();

        // Fallback: if no slots for this branch (e.g. mobile uses branch_id=1 but slots exist for another branch),
        // return slots from any branch so the book-appointment screen can show times; booking will use the slot's branch_id.
        if ($slots->isEmpty()) {
            $slots = AppointmentSlot::where('doctor_id', $doctorId)
                ->where('slot_date', $date)
                ->where('appointment_type', $appointmentType)
                ->available()
                ->orderBy('start_time')
                ->get();
            if ($slots->isNotEmpty()) {
                Log::info('Available time slots: no slots for branch_id ' . $branchId . ' on ' . $date . ', returned slots from other branches for doctor_id ' . $doctorId);
            }
        }

        $modulePricing = app(ModulePricingService::class);
        $patientId = auth()->user()?->patient?->id;

        $availableSlots = $slots->map(function ($slot) use ($appointmentType, $modulePricing, $patientId) {
            $nativeFee = (float) ($slot->fee ?? 0);
            $costData = $modulePricing->buildAppointmentCostData(
                (int) $slot->branch_id,
                $appointmentType,
                $nativeFee,
                $patientId ? (int) $patientId : null,
                $slot->currency ?? 'GHS',
                'slot'
            );

            return [
                'id' => $slot->id,
                'time' => $slot->start_time->format('H:i'),
                'end_time' => $slot->end_time->format('H:i'),
                'duration' => (int) $slot->duration,
                'available' => $slot->isAvailable(),
                'max_appointments' => (int) $slot->max_appointments,
                'booked_appointments' => (int) $slot->booked_appointments,
                'remaining_capacity' => $slot->getRemainingCapacity(),
                'fee' => $nativeFee,
                'module_fee' => $costData['module_fee'],
                'total_fee' => $costData['total_cost'],
                'currency' => $slot->currency ?? 'GHS',
                'charge_lines' => $costData['charge_lines'],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $availableSlots,
            'message' => 'Available time slots retrieved successfully'
        ]);
    }

    /**
     * Get available dates for a doctor in a date range.
     */
    public function getAvailableDates(Request $request)
    {
        // Handle both mobile app format (month/year) and web format (start_date/end_date)
        if ($request->has('month') && $request->has('year')) {
            // Mobile app format - validate month/year parameters (branch_id optional)
            $request->validate([
                'doctor_id' => 'required|exists:users,id',
                'branch_id' => 'nullable|exists:branches,id',
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2024|max:2030',
                'appointment_type' => 'nullable|in:in-person,teleconsultation',
            ]);

            $doctorId = $request->doctor_id;
            $month = $request->month;
            $year = $request->year;
            $appointmentType = $request->appointment_type ?? 'in-person';
            $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;

            if (!$branchId) {
                $defaultBranch = \App\Models\Branch::first();
                $branchId = $defaultBranch ? $defaultBranch->id : null;
            }

            // Calculate start and end dates for the month
            $startDate = Carbon::create($year, $month, 1)->format('Y-m-d');
            $endDate = Carbon::create($year, $month)->endOfMonth()->format('Y-m-d');

        } else {
            // Web format - validate start_date/end_date parameters
            $request->validate([
                'doctor_id' => 'required|exists:users,id',
                'branch_id' => 'required|exists:branches,id',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
                'appointment_type' => 'in:in-person,teleconsultation'
            ]);

            $doctorId = $request->doctor_id;
            $branchId = $request->branch_id;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $appointmentType = $request->appointment_type ?? 'in-person';
        }

        // Handle case where no branch is available
        if (!$branchId) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No branch configured for appointments'
            ]);
        }

        // Get available appointment slots in the date range (for the requested branch)
        $slots = AppointmentSlot::where('doctor_id', $doctorId)
            ->where('branch_id', $branchId)
            ->where('slot_date', '>=', $startDate)
            ->where('slot_date', '<=', $endDate)
            ->where('appointment_type', $appointmentType)
            ->available()
            ->orderBy('slot_date')
            ->get();

        // Fallback: if no slots for the requested branch (e.g. mobile sends branch_id=1 but admin created slots for branch 2),
        // return slots from any branch for this doctor so the book-appointment screen still shows dates.
        if ($slots->isEmpty() && $branchId && ($request->has('month') || $request->has('start_date'))) {
            $slots = AppointmentSlot::where('doctor_id', $doctorId)
                ->where('slot_date', '>=', $startDate)
                ->where('slot_date', '<=', $endDate)
                ->where('appointment_type', $appointmentType)
                ->available()
                ->orderBy('slot_date')
                ->get();
            if ($slots->isNotEmpty()) {
                Log::info('Available dates: no slots for branch_id ' . $branchId . ', returned slots from other branches for doctor_id ' . $doctorId);
            }
        }

        // Optional debug log when still no slots found (helps diagnose "No available dates" in mobile)
        if ($slots->isEmpty() && $request->has('month') && $request->has('year')) {
            Log::debug('Available dates returned empty for mobile', [
                'doctor_id' => $doctorId,
                'branch_id' => $branchId,
                'month' => $request->month,
                'year' => $request->year,
                'appointment_type' => $appointmentType ?? 'in-person',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
        }

        // Group slots by date and count available slots per date
        $availableDates = $slots->groupBy('slot_date')->map(function ($dateSlots) {
            $totalSlots = $dateSlots->sum('max_appointments');
            $bookedSlots = $dateSlots->sum('booked_appointments');
            $availableSlots = $totalSlots - $bookedSlots;

            return [
                'date' => $dateSlots->first()->slot_date,
                'available_slots' => max(0, $availableSlots),
                'total_slots' => $totalSlots,
                'booked_slots' => $bookedSlots,
                'has_availability' => $availableSlots > 0,
            ];
        })->values();

        // For mobile app format, return just the dates as Y-m-d strings (consistent format for mobile parsing)
        if ($request->has('month') && $request->has('year')) {
            $today = now()->toDateString();
            $dateStrings = $availableDates
                ->map(function ($dateInfo) {
                    $d = $dateInfo['date'];
                    return $d instanceof \Carbon\Carbon ? $d->format('Y-m-d') : Carbon::parse($d)->format('Y-m-d');
                })
                ->filter(function ($dateStr) use ($today) {
                    return $dateStr >= $today; // Exclude past dates
                })
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => array_values($dateStrings),
                'message' => 'Available dates retrieved successfully'
            ]);
        }

        // For web format, return full date information
        return response()->json([
            'success' => true,
            'data' => $availableDates,
            'message' => 'Available dates retrieved successfully'
        ]);
    }

    /**
     * Get appointment cost estimate.
     */
    public function getCostEstimate(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'appointment_type' => 'required|in:in-person,teleconsultation',
            'slot_id' => 'nullable|exists:appointment_slots,id',
            'context' => 'nullable|array'
        ]);

        $doctorId = $request->doctor_id;
        $branchId = $request->branch_id;
        $appointmentType = $request->appointment_type;
        $slotId = $request->slot_id;
        $context = $request->get('context', []);

        $modulePricing = app(ModulePricingService::class);
        $patientId = auth()->user()?->patient?->id;

        if ($slotId) {
            $slot = AppointmentSlot::findOrFail($slotId);
            $fee = (float) ($slot->fee ?? 0);
            $costData = $modulePricing->buildAppointmentCostData(
                $branchId,
                $appointmentType,
                $fee,
                $patientId ? (int) $patientId : null,
                $slot->currency ?? 'GHS',
                'slot'
            );
            $costData['available'] = true;

            return response()->json([
                'success' => true,
                'data' => $costData,
                'message' => 'Cost estimate retrieved successfully',
            ]);
        }

        // Otherwise, get fee from appointment fee structure
        $fee = AppointmentFee::where('branch_id', $branchId)
            ->where('appointment_type', $appointmentType)
            ->where(function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId)
                    ->orWhereNull('doctor_id');
            })
            ->effective()
            ->active()
            ->orderBy('doctor_id', 'desc') // Doctor-specific fees first
            ->first();

        if (!$fee) {
            return response()->json([
                'success' => true,
                'data' => array_merge([
                    'available' => false,
                    'message' => 'No fee structure found for the specified criteria. Fees will be confirmed at booking.',
                    'charge_lines' => [],
                    'total_cost' => 0,
                    'base_fee' => 0,
                    'module_fee' => 0,
                    'appointment_type' => $appointmentType,
                    'currency' => 'GHS',
                    'source' => 'unavailable',
                ], [
                    'breakdown' => [
                        'consultation_fee' => 0.0,
                        'module_fee' => 0.0,
                        'platform_fee' => 0.0,
                        'tax' => 0.0,
                    ],
                ]),
                'message' => 'Cost estimate unavailable; fees confirmed at booking',
            ]);
        }

        $breakdown = $fee->applyDiscounts($context);
        $nativeFee = (float) ($breakdown['total'] ?? $breakdown['base_fee'] ?? 0);
        $costData = $modulePricing->buildAppointmentCostData(
            $branchId,
            $appointmentType,
            $nativeFee,
            $patientId ? (int) $patientId : null,
            $fee->currency ?? 'GHS',
            'fee_structure'
        );
        $costData['available'] = true;

        return response()->json([
            'success' => true,
            'data' => $costData,
            'message' => 'Cost estimate retrieved successfully',
        ]);
    }

    /**
     * Reschedule appointment.
     */
    public function reschedule(Request $request, $id)
    {
        $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|string|regex:/^\d{2}:\d{2}(:\d{2})?$/', // H:i or H:i:s to match mobile time slots (e.g. 09:00)
        ]);

        $appointment = Appointment::findOrFail($id);
        $user = auth()->user();

        // Normalize time to H:i:s for DB consistency (mobile may send H:i from time slots e.g. "09:00")
        $appointmentTime = strlen($request->appointment_time) === 5
            ? $request->appointment_time . ':00'
            : $request->appointment_time;

        // Check if user can reschedule this appointment
        if ($user->hasRole('patient')) {
            $userPatient = $user->patient ?? Patient::where('user_id', $user->id)->first();
            if (!$userPatient || $appointment->patient_id != $userPatient->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }
        }

        // Check for conflicts
        $conflict = Appointment::where('doctor_id', $appointment->doctor_id)
            ->whereDate('appointment_date', $request->appointment_date)
            ->where('appointment_time', $appointmentTime)
            ->where('id', '!=', $id)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->first();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'Time slot is not available'
            ], 400);
        }

        $appointment->update([
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $appointmentTime,
            'status' => 'scheduled'
        ]);

        return response()->json([
            'success' => true,
            'data' => $appointment->load(['doctor', 'patient', 'branch']),
            'message' => 'Appointment rescheduled successfully'
        ]);
    }

    /**
     * Join virtual appointment.
     */
    public function joinVirtualAppointment($id)
    {
        $appointment = Appointment::with(['doctor', 'patient', 'teleconsultation'])->findOrFail($id);
        $user = auth()->user();

        // Check if user can join this appointment
        if ($user->hasRole('patient')) {
            $userPatient = $user->patient ?? Patient::where('user_id', $user->id)->first();
            if (!$userPatient || $appointment->patient_id != $userPatient->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }
        }

        // Check for both 'virtual' and 'teleconsultation' appointment types
        if (!in_array($appointment->appointment_type, ['virtual', 'teleconsultation'])) {
            return response()->json([
                'success' => false,
                'message' => 'This is not a virtual appointment'
            ], 400);
        }

        if (!in_array($appointment->status, ['scheduled', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment is not available for joining'
            ], 400);
        }

        // Check if teleconsultation exists, if not create one
        if (!$appointment->teleconsultation) {
            try {
                // Create teleconsultation for this appointment
                $appointmentDateTime = Carbon::parse($appointment->appointment_date)->setTimeFromTimeString($appointment->appointment_time);

                $teleconsultation = Teleconsultation::create([
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient_id,
                    'doctor_id' => $appointment->doctor_id,
                    'branch_id' => $appointment->branch_id,
                    'scheduled_at' => $appointmentDateTime,
                    'consultation_type' => 'video',
                    'consultation_notes' => $appointment->reason,
                    'video_enabled' => true,
                    'audio_enabled' => true,
                    'recording_enabled' => false,
                    'created_by' => $user->id,
                ]);

                // Generate Jitsi meeting
                $jitsiService = new \App\Services\JitsiService();
                $meetingData = $jitsiService->createMeeting($teleconsultation);

                // Update appointment with teleconsultation details
                $appointment->update([
                    'teleconsultation_id' => $teleconsultation->id,
                    'meeting_url' => $teleconsultation->meeting_url,
                    'meeting_password' => $teleconsultation->meeting_password,
                ]);

                $appointment->load('teleconsultation');

                Log::info('Teleconsultation created during join for appointment', [
                    'appointment_id' => $appointment->id,
                    'teleconsultation_id' => $teleconsultation->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create teleconsultation during join', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create meeting: ' . $e->getMessage()
                ], 500);
            }
        }

        // Get teleconsultation
        $teleconsultation = $appointment->teleconsultation;

        // Generate JWT token based on user role
        $jitsiService = new \App\Services\JitsiService();
        $isPatient = $user->hasRole('patient');

        if ($isPatient) {
            $jwtToken = $jitsiService->generatePatientJWTToken($teleconsultation, $teleconsultation->meeting_id);
        } else {
            $jwtToken = $jitsiService->generateJWTToken($teleconsultation, $teleconsultation->meeting_id);
        }

        // Build meeting URL with JWT token
        $meetingUrl = $teleconsultation->meeting_url;
        if (empty($meetingUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'Telehealth meeting URL is not configured. Contact the hospital to enable video consultations.',
            ], 503);
        }
        if ($jwtToken) {
            $meetingUrl .= (strpos($meetingUrl, '?') !== false ? '&' : '?') . 'jwt=' . $jwtToken;
        }

        // Return meeting details
        $meetingDetails = [
            'appointment_id' => $appointment->id,
            'teleconsultation_id' => $teleconsultation->id,
            'meeting_url' => $meetingUrl,
            'meetingUrl' => $meetingUrl, // Alternative key for compatibility
            'meeting_id' => $teleconsultation->meeting_id,
            'meeting_password' => $teleconsultation->meeting_password,
            'jwt_token' => $jwtToken,
            'room_name' => $teleconsultation->meeting_id,
            'doctor_name' => $appointment->doctor->first_name . ' ' . $appointment->doctor->last_name,
            'patient_name' => $appointment->patient->first_name . ' ' . $appointment->patient->last_name,
            'user_role' => $isPatient ? 'patient' : 'doctor',
            'scheduled_at' => $teleconsultation->scheduled_at,
        ];

        return response()->json([
            'success' => true,
            'data' => $meetingDetails,
            'message' => 'Virtual appointment details retrieved successfully'
        ]);
    }

    /**
     * Check if appointment can be cancelled.
     */
    public function canCancelAppointment($id)
    {
        $appointment = Appointment::findOrFail($id);
        $user = auth()->user();

        // Check if user can cancel this appointment
        if ($user->hasRole('patient')) {
            $userPatient = $user->patient ?? Patient::where('user_id', $user->id)->first();
            if (!$userPatient || $appointment->patient_id != $userPatient->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }
        }

        $appointmentDate = Carbon::parse($appointment->appointment_date . ' ' . $appointment->appointment_time);
        $hoursUntilAppointment = now()->diffInHours($appointmentDate, false);

        $canCancel = $hoursUntilAppointment >= 24; // Can cancel if 24+ hours in advance

        return response()->json([
            'success' => true,
            'data' => [
                'can_cancel' => $canCancel,
                'hours_until_appointment' => $hoursUntilAppointment,
                'cancellation_policy' => 'Appointments can be cancelled up to 24 hours in advance'
            ],
            'message' => 'Cancellation status retrieved successfully'
        ]);
    }

    /**
     * Get appointment reminders.
     */
    public function getReminders(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasRole('patient')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $upcomingAppointments = Appointment::with(['doctor'])
            ->where('patient_id', $user->id)
            ->whereDate('appointment_date', '>=', today())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        $reminders = $upcomingAppointments->map(function ($appointment) {
            $appointmentDateTime = Carbon::parse($appointment->appointment_date . ' ' . $appointment->appointment_time);
            $hoursUntil = now()->diffInHours($appointmentDateTime, false);

            return [
                'appointment_id' => $appointment->id,
                'doctor_name' => $appointment->doctor->first_name . ' ' . $appointment->doctor->last_name,
                'appointment_date' => $appointment->appointment_date,
                'appointment_time' => $appointment->appointment_time,
                'hours_until' => $hoursUntil,
                'reminder_sent' => false // This would be tracked in a separate table
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $reminders,
            'message' => 'Appointment reminders retrieved successfully'
        ]);
    }

    /**
     * Send appointment reminder.
     */
    public function sendReminder($id)
    {
        $appointment = Appointment::with(['doctor', 'patient'])->findOrFail($id);
        $user = auth()->user();

        // Check if user can send reminder for this appointment
        if ($user->hasRole('patient')) {
            $userPatient = $user->patient ?? Patient::where('user_id', $user->id)->first();
            if (!$userPatient || $appointment->patient_id != $userPatient->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }
        }

        // Here you would integrate with your notification system
        // For now, we'll just return success

        return response()->json([
            'success' => true,
            'data' => [
                'appointment_id' => $appointment->id,
                'reminder_sent' => true,
                'sent_at' => now()->toISOString()
            ],
            'message' => 'Appointment reminder sent successfully'
        ]);
    }

    /**
     * Check for appointment conflicts.
     */
    private function checkAppointmentConflict($doctorId, $date, $time, $duration, $excludeId = null)
    {
        $startTime = Carbon::parse($date . ' ' . $time);
        $endTime = $startTime->copy()->addMinutes($duration);

        $query = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', ['scheduled'])
            ->whereBetween('appointment_time', [$startTime->format('H:i'), $endTime->format('H:i')]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $conflict = $query->first();

        if ($conflict) {
            return [
                'conflicting_appointment' => $conflict,
                'conflict_time' => $conflict->appointment_time
            ];
        }

        return null;
    }

    /**
     * Create teleconsultation and Jitsi meeting for appointment
     */
    private function createTeleconsultationForAppointment(Appointment $appointment, array $requestData)
    {
        try {
            // Create teleconsultation record
            $appointmentDateTime = Carbon::parse($appointment->appointment_date)->setTimeFromTimeString($appointment->appointment_time);

            $teleconsultation = Teleconsultation::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'branch_id' => $appointment->branch_id,
                'scheduled_at' => $appointmentDateTime,
                'consultation_type' => 'video', // Default to video for appointments
                'consultation_notes' => $appointment->reason,
                'video_enabled' => true,
                'audio_enabled' => true,
                'recording_enabled' => false,
                'created_by' => auth()->id(),
            ]);

            // Generate Jitsi meeting
            $meetingData = $this->jitsiService->createMeeting($teleconsultation);

            // Update appointment with teleconsultation details
            $appointment->update([
                'teleconsultation_id' => $teleconsultation->id,
                'meeting_url' => $teleconsultation->meeting_url,
                'meeting_password' => $teleconsultation->meeting_password,
            ]);

            Log::info('Teleconsultation created for API appointment', [
                'appointment_id' => $appointment->id,
                'teleconsultation_id' => $teleconsultation->id,
                'meeting_url' => $teleconsultation->meeting_url
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create teleconsultation for API appointment', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);

            // If Jitsi fails, create basic teleconsultation without meeting
            $meetingUrl = $this->generateBasicMeetingUrl($appointment);
            $meetingPassword = $this->generateMeetingPassword();

            $appointmentDateTime = Carbon::parse($appointment->appointment_date)->setTimeFromTimeString($appointment->appointment_time);

            $teleconsultation = Teleconsultation::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'branch_id' => $appointment->branch_id,
                'scheduled_at' => $appointmentDateTime,
                'consultation_type' => 'video',
                'consultation_notes' => $appointment->reason,
                'video_enabled' => true,
                'audio_enabled' => true,
                'recording_enabled' => false,
                'meeting_url' => $meetingUrl,
                'meeting_password' => $meetingPassword,
                'created_by' => auth()->id(),
            ]);

            $appointment->update([
                'teleconsultation_id' => $teleconsultation->id,
                'meeting_url' => $meetingUrl,
                'meeting_password' => $meetingPassword,
            ]);
        }
    }

    /**
     * Generate basic meeting URL as fallback
     */
    private function generateBasicMeetingUrl(Appointment $appointment)
    {
        $roomName = 'APPT-' . $appointment->id . '-' . time();
        return 'https://meet.jit.si/' . $roomName;
    }

    /**
     * Generate meeting password
     */
    private function generateMeetingPassword()
    {
        return strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
    }

    /**
     * Generate unique payment reference for appointment
     */
    private function generateAppointmentPaymentReference(Appointment $appointment): string
    {
        $prefix = 'APT';
        $appointmentId = str_pad($appointment->id, 6, '0', STR_PAD_LEFT);
        $timestamp = time();
        $random = strtoupper(substr(uniqid(), -6));
        
        // Format: APT-000001-1234567890-ABCDEF
        $reference = "{$prefix}-{$appointmentId}-{$timestamp}-{$random}";
        
        // Ensure uniqueness - check if reference exists
        $exists = \App\Models\Payment::where('reference_number', $reference)->exists();
        
        if ($exists) {
            // If exists, regenerate with additional random component
            $reference = "{$prefix}-{$appointmentId}-{$timestamp}-" . strtoupper(substr(uniqid(true), -8));
        }
        
        return $reference;
    }

    /**
     * Initialize Paystack payment for appointment
     */
    public function initializeAppointmentPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'appointment_id' => 'required|exists:appointments,id',
                'email' => 'nullable|email',
                'reference' => 'nullable|string|unique:payments,reference_number',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $appointment = Appointment::with(['patient.user', 'doctor', 'slot'])->findOrFail($request->appointment_id);

            // SECURITY: If user is a patient, validate that appointment belongs to authenticated user's patient record
            $user = $request->user();
            if ($user && $user->hasRole('patient')) {
                $userPatient = $user->patient ?? Patient::where('user_id', $user->id)->first();
                if ($userPatient && $appointment->patient_id != $userPatient->id) {
                    Log::warning('Appointment ownership mismatch in payment initialization', [
                        'user_id' => $user->id,
                        'appointment_id' => $appointment->id,
                        'appointment_patient_id' => $appointment->patient_id,
                        'user_patient_id' => $userPatient->id
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to pay for this appointment. It may belong to another account.'
                    ], 403);
                }
            }

            // Get email from request, patient record, or user account (in order of preference)
            $email = $request->email 
                ?? $appointment->patient->email 
                ?? ($appointment->patient->user ? $appointment->patient->user->email : null)
                ?? null;
            
            if (!$email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email is required for payment initialization. Please provide an email parameter or ensure the patient has an email in their profile.'
                ], 422);
            }

            // Generate unique payment reference if not provided
            $reference = $request->reference ?? $this->generateAppointmentPaymentReference($appointment);

            // Get appointment fee: first from fee structure, then fall back to slot fee (same as cost estimate / time slots)
            $feeQuery = AppointmentFee::where('branch_id', $appointment->branch_id)
                ->where('appointment_type', $appointment->appointment_type)
                ->where(function ($q) use ($appointment) {
                    $q->where('doctor_id', $appointment->doctor_id)
                        ->orWhereNull('doctor_id');
                })
                ->effective()
                ->active()
                ->orderBy('doctor_id', 'desc');

            $fee = $feeQuery->first();
            $amount = null;
            $currency = 'GHS';

            if ($fee) {
                $breakdown = $fee->applyDiscounts([]);
                $amount = $breakdown['total'];
                $currency = $fee->currency ?? 'GHS';
            }

            // Fallback: when appointment was booked with a slot, use slot fee so payment init matches cost estimate / time slots
            if (($amount === null || $amount <= 0) && $appointment->slot_id) {
                $slot = $appointment->slot ?? AppointmentSlot::find($appointment->slot_id);
                if ($slot && $slot->fee !== null && (float) $slot->fee > 0) {
                    $amount = (float) $slot->fee;
                    $currency = $slot->currency ?? 'GHS';
                }
            }

            if ($amount === null || $amount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No fee structure found for this appointment'
                ], 404);
            }

            $costData = app(ModulePricingService::class)->buildAppointmentCostData(
                (int) $appointment->branch_id,
                $appointment->appointment_type,
                (float) $amount,
                (int) $appointment->patient_id,
                $currency,
                $appointment->slot_id ? 'slot' : 'fee_structure'
            );
            $amount = $costData['total_cost'];

            // Prepare metadata
            $metadata = [
                'payment_type' => 'appointment',
                'appointment_id' => $appointment->id, // Primary key for appointment identification
                'reference_id' => $appointment->id, // Alias for backward compatibility
                'appointment_type' => $appointment->appointment_type,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'branch_id' => $appointment->branch_id,
                'patient_name' => $appointment->patient->full_name ?? 'Unknown',
                'doctor_name' => $appointment->doctor->name ?? 'Unknown',
            ];

            // Get dynamic callback URL
            $callbackUrl = \App\Models\PaymentSetting::getPaystackCallbackUrl();

            // Initialize payment with Paystack
            $paystackService = new \App\Services\PaystackService();
            $result = $paystackService->initializeTransaction(
                $email,
                $amount,
                $reference,
                $metadata,
                $callbackUrl
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment initialization failed'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'callback_url' => $callbackUrl,
                'amount' => $amount,
                'charge_lines' => $costData['charge_lines'],
                'reference' => $reference,
                'payment_policy' => [
                    'context' => 'OPD',
                    'requires_full_payment' => true,
                    'allows_partial_payment' => false,
                    'allows_payment_after_service' => false,
                    'message' => 'OPD appointments require full payment via Paystack before service.',
                ],
                'message' => 'Payment initialized successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Appointment Paystack initialization error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Payment initialization failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process Paystack payment for appointment
     */
    public function processAppointmentPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'appointment_id' => 'required|exists:appointments,id',
                'reference' => 'required|string',
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $appointment = Appointment::findOrFail($request->appointment_id);

            // SECURITY: If user is a patient, validate that appointment belongs to authenticated user's patient record
            $user = $request->user();
            if ($user && $user->hasRole('patient')) {
                $userPatient = $user->patient ?? Patient::where('user_id', $user->id)->first();
                if ($userPatient && $appointment->patient_id != $userPatient->id) {
                    Log::warning('Appointment ownership mismatch in payment processing', [
                        'user_id' => $user->id,
                        'appointment_id' => $appointment->id,
                        'appointment_patient_id' => $appointment->patient_id,
                        'user_patient_id' => $userPatient->id
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to pay for this appointment. It may belong to another account.'
                    ], 403);
                }
            }

            // Verify payment with Paystack
            $paystackService = new \App\Services\PaystackService();
            $verification = $paystackService->verifyTransaction($request->reference);

            if (!$verification['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed'
                ], 400);
            }

            $transactionData = $verification['data'];

            // Check if payment was successful
            if ($transactionData['status'] !== 'success') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment was not successful'
                ], 400);
            }

            DB::beginTransaction();

            // Load appointment with relationships (include slot for fee fallback)
            $appointment = Appointment::with(['patient', 'doctor', 'branch', 'slot'])->findOrFail($appointment->id);

            // Get appointment fee amount (same logic as initialize: fee structure first, then slot fallback)
            $feeQuery = AppointmentFee::where('branch_id', $appointment->branch_id)
                ->where('appointment_type', $appointment->appointment_type)
                ->where(function ($q) use ($appointment) {
                    $q->where('doctor_id', $appointment->doctor_id)
                        ->orWhereNull('doctor_id');
                })
                ->effective()
                ->active()
                ->orderBy('doctor_id', 'desc');

            $fee = $feeQuery->first();
            $amount = null;

            if ($fee) {
                $breakdown = $fee->applyDiscounts([]);
                $amount = $breakdown['total'];
            }

            if (($amount === null || $amount <= 0) && $appointment->slot_id) {
                $slot = $appointment->slot ?? AppointmentSlot::find($appointment->slot_id);
                if ($slot && $slot->fee !== null && (float) $slot->fee > 0) {
                    $amount = (float) $slot->fee;
                }
            }

            if ($amount === null || $amount <= 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No fee structure found for this appointment'
                ], 404);
            }

            $costData = app(ModulePricingService::class)->buildAppointmentCostData(
                (int) $appointment->branch_id,
                $appointment->appointment_type,
                (float) $amount,
                (int) $appointment->patient_id,
                'GHS',
                $appointment->slot_id ? 'slot' : 'fee_structure'
            );
            $amount = $costData['total_cost'];

            if (!$paystackService->amountMatchesExpected((float) $amount, $transactionData)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Paystack amount does not match appointment fee. Full payment required.',
                ], 400);
            }

            $amountPaid = round((float) ($transactionData['amount'] ?? 0) / 100, 2);

            // Create invoice for appointment
            $invoiceService = app(\App\Services\InvoiceService::class);
            $invoiceItems = [];
            foreach ($costData['charge_lines'] as $line) {
                $invoiceItems[] = [
                    'type' => 'service',
                    'description' => $line['description'],
                    'quantity' => 1,
                    'unit_price' => $line['amount'],
                    'total' => $line['amount'],
                    'service_type' => 'appointment',
                    'charge_component' => $line['charge_component'] ?? null,
                    'service_code' => 'APT-' . $appointment->id,
                ];
            }
            if (empty($invoiceItems)) {
                $invoiceItems[] = [
                    'type' => 'service',
                    'description' => 'Appointment Fee - ' . ($appointment->appointment_type === 'teleconsultation' ? 'Teleconsultation' : 'In-Person Consultation'),
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'total' => $amount,
                    'service_type' => 'appointment',
                    'service_code' => 'APT-' . $appointment->id,
                ];
            }

            $invoice = $invoiceService->createInvoice(
                $appointment->patient_id,
                $appointment->branch_id,
                $invoiceItems,
                [
                    'invoice_date' => now()->toDateString(),
                    'due_date' => now()->toDateString(), // Immediate payment
                    'status' => 'paid', // Mark as paid since payment is confirmed
                    'notes' => 'Appointment payment via Paystack - Reference: ' . $request->reference,
                    'created_by' => auth()->id()
                ]
            );

            // Record payment using PaymentService
            $paymentService = app(\App\Services\PaymentService::class);
            $paymentResult = $paymentService->recordPayment(
                $invoice->id,
                $amountPaid,
                \App\Enums\PaymentMethod::Paystack->value,
                [
                    'reference_number' => $request->reference,
                    'transaction_id' => $transactionData['id'] ?? null,
                    'notes' => 'Appointment payment - ' . ($appointment->appointment_type === 'teleconsultation' ? 'Teleconsultation' : 'In-Person') . ' - Appointment ID: ' . $appointment->id,
                    'processed_by' => auth()->id(),
                    'source_platform' => 'mobile',
                    'device_info' => $request->header('User-Agent'),
                    'ip_address' => $request->ip(),
                    'metadata' => [
                        'appointment_id' => $appointment->id,
                        'payment_type' => 'appointment',
                        'appointment_type' => $appointment->appointment_type,
                        'paystack_transaction' => $transactionData,
                    ]
                ]
            );

            if (!$paymentResult['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to record payment: ' . $paymentResult['message']
                ], 500);
            }

            // Update appointment billing status to 'paid'
            $appointment->update([
                'billing_status' => 'paid',
                'updated_by' => auth()->id()
            ]);

            // Confirm appointment if it was pending
            if ($appointment->status === 'pending') {
                $appointment->update(['status' => 'scheduled']);
            }

            DB::commit();

            $payment = $paymentResult['payment'];

            if ($appointment->status === 'scheduled') {
                try {
                    $this->appNotificationService->notifyAppointmentScheduled(
                        $appointment->fresh(['patient', 'doctor']),
                        auth()->id()
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to send appointment confirmation notification: ' . $e->getMessage());
                }
            }

            // Mobile contract: AppointmentPaymentProcessResponse expects payment_id, status, appointment_id
            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'status' => $payment->status ?? 'completed',
                    'appointment_id' => $appointment->id,
                    'appointment' => $appointment->load(['patient', 'doctor', 'branch']),
                ],
                'message' => 'Payment processed successfully. Appointment confirmed.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Appointment Paystack payment error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
