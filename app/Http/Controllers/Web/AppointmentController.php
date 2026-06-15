<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\Branch;
use App\Models\Teleconsultation;
use App\Models\AppointmentFee;
use App\Enums\PaymentMethod;
use App\Services\JitsiService;
use App\Services\PaystackService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\DoctorReviewService;
use App\Support\PaymentMetadata;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    use ExportsListData, ResolvesUserBranch;

    protected $jitsiService;

    public function __construct(JitsiService $jitsiService, protected DoctorReviewService $doctorReviewService)
    {
        $this->jitsiService = $jitsiService;
    }
    /**
     * Display listing of appointments
     */
    public function index(Request $request)
    {
        $branchId = $this->resolveUserBranchId('view_appointments');
        $portalPatient = $this->portalPatient();
        
        // Fetch appointments with relationships (server-side)
        $query = Appointment::with(['patient', 'doctor', 'branch'])
            ->whereHas('patient');

        if ($portalPatient) {
            $query->where('patient_id', $portalPatient->id);
        } elseif ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // SECURITY: If user is a doctor, show only their appointments
        if (auth()->user()->hasRole('doctor')) {
            $query->where('doctor_id', auth()->id());
        }

        $appointments = $query->latest('id')->paginate(20);
        
        // Get statistics
        $statsQuery = Appointment::query();
        if ($portalPatient) {
            $statsQuery->where('patient_id', $portalPatient->id);
        } elseif ($branchId) {
            $statsQuery->where('branch_id', $branchId);
        }
        if (auth()->user()->hasRole('doctor')) {
            $statsQuery->where('doctor_id', auth()->id());
        }

        $statistics = [
            'total' => (clone $statsQuery)->count(),
            'today' => (clone $statsQuery)->whereDate('appointment_date', Carbon::today())->count(),
            'scheduled' => (clone $statsQuery)->where('status', 'scheduled')->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
        ];
        
        return view('appointments.index', compact('appointments', 'statistics'));
    }
    
    /**
     * Show the form for creating appointment
     */
    public function create()
    {
        $portalPatient = $this->portalPatient();

        if ($portalPatient) {
            $patients = collect([$portalPatient]);
        } else {
            $patients = Patient::latest()->get();
        }
        
        // SECURITY: If user is a doctor, only show themselves
        if (auth()->user()->hasRole('doctor')) {
            $doctors = collect([auth()->user()]);
        } else {
            $doctors = User::role('doctor')->get();
        }
        
        $branches = Branch::where('is_active', true)->get();
        
        return view('appointments.create', compact('patients', 'doctors', 'branches'));
    }
    
    /**
     * Store a newly created appointment
     */
    public function store(Request $request)
    {
        // SECURITY: If user is a doctor, force doctor_id to be their own ID
        if (auth()->user()->hasRole('doctor')) {
            $request->merge(['doctor_id' => auth()->id()]);
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'appointment_date' => 'required|date',
            'appointment_time' => 'required',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'appointment_type' => 'required|in:in-person,teleconsultation',
            'slot_id' => 'nullable|exists:appointment_slots,id',
        ]);

        // Double-check: If user is a doctor, ensure they can only create for themselves
        if (auth()->user()->hasRole('doctor') && $validated['doctor_id'] != auth()->id()) {
            return back()->with('error', 'You can only create appointments for yourself.')->withInput();
        }

        $portalPatient = $this->portalPatient();
        if ($portalPatient) {
            $validated['patient_id'] = $portalPatient->id;
            $validated['branch_id'] = $portalPatient->branch_id ?? $validated['branch_id'];
        }
        
        // If slot_id is provided, validate and use the slot (matches API behavior)
        if ($request->has('slot_id') && $request->slot_id) {
            $slot = \App\Models\AppointmentSlot::findOrFail($request->slot_id);
            
            // Validate slot is available and matches criteria
            if (!$slot->isAvailable()) {
                return back()->with('error', 'Selected time slot is not available.')->withInput();
            }
            
            // Format slot_date to Y-m-d string for comparison
            $slotDateFormatted = $slot->slot_date instanceof \Carbon\Carbon 
                ? $slot->slot_date->format('Y-m-d') 
                : \Carbon\Carbon::parse($slot->slot_date)->format('Y-m-d');
            
            // Format request appointment_date to Y-m-d for consistent comparison
            $requestDateFormatted = \Carbon\Carbon::parse($validated['appointment_date'])->format('Y-m-d');
            
            if (
                $slot->doctor_id != $validated['doctor_id'] ||
                $slot->branch_id != $validated['branch_id'] ||
                $slotDateFormatted != $requestDateFormatted ||
                $slot->appointment_type != $validated['appointment_type']
            ) {
                \Illuminate\Support\Facades\Log::warning('Slot validation failed (Web)', [
                    'slot_id' => $slot->id,
                    'slot_doctor_id' => $slot->doctor_id,
                    'request_doctor_id' => $validated['doctor_id'],
                    'slot_branch_id' => $slot->branch_id,
                    'request_branch_id' => $validated['branch_id'],
                    'slot_date' => $slotDateFormatted,
                    'request_date' => $requestDateFormatted,
                    'slot_appointment_type' => $slot->appointment_type,
                    'request_appointment_type' => $validated['appointment_type'],
                ]);

                return back()->with('error', 'Selected slot does not match appointment criteria.')->withInput();
            }
            
            // Book the slot
            if (!$slot->bookAppointment()) {
                return back()->with('error', 'Failed to book the selected time slot.')->withInput();
            }
        } else {
            // Check for conflicts using the old method as fallback (if no slot_id)
            $conflict = $this->checkAppointmentConflict(
                $validated['doctor_id'], 
                $validated['appointment_date'], 
                $validated['appointment_time'], 
                30
            );
            
            if ($conflict) {
                return back()->with('error', 'Appointment time conflict detected. Please choose a different time.')->withInput();
            }
        }
        
        // Generate appointment number
        // Appointment number will be generated automatically by HasIdPrefix trait
        $validated['status'] = 'scheduled';
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();
        
        // Set teleconsultation flag
        $validated['is_teleconsultation'] = ($validated['appointment_type'] === 'teleconsultation');
        
        try {
            $appointment = Appointment::create($validated);
            
            // If this is a teleconsultation appointment, create teleconsultation and Jitsi meeting
            if ($validated['appointment_type'] === 'teleconsultation') {
                $this->createTeleconsultationForAppointment($appointment, $validated);
            }
            
            // Redirect to show page where user can pay
            return redirect()->route('appointments.show', $appointment)
                ->with('success', 'Appointment scheduled successfully! Appointment #: ' . $appointment->appointment_number . '. Please complete payment to confirm your appointment.');
        } catch (\Exception $e) {
            Log::error('Failed to create appointment', [
                'error' => $e->getMessage(),
                'request_data' => $validated
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to schedule appointment. Please try again.');
        }
    }
    
    /**
     * Display the specified appointment
     */
    public function show(Appointment $appointment)
    {
        $this->assertPortalPatientOwns($appointment->patient_id);

        // SECURITY: If user is a doctor, ensure they can only view their own appointments
        if (auth()->user()->hasRole('doctor') && $appointment->doctor_id != auth()->id()) {
            abort(403, 'You can only view your own appointments.');
        }

        $appointment->load(['patient', 'doctor', 'branch', 'creator', 'teleconsultation', 'doctorReview']);
        
        return view('appointments.show', compact('appointment'));
    }

    public function storeReview(Request $request, Appointment $appointment)
    {
        $this->assertPortalPatientOwns($appointment->patient_id);

        if ($appointment->status !== 'completed') {
            return back()->with('error', 'You can only review completed appointments.');
        }

        if ($appointment->doctorReview) {
            return back()->with('error', 'You have already reviewed this appointment.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $patient = $this->portalPatient();
        if (!$patient || !$appointment->doctor) {
            abort(403);
        }

        $this->doctorReviewService->createReview(
            $appointment->doctor,
            $patient,
            (int) $validated['rating'],
            $validated['comment'] ?? null,
            $appointment->id
        );

        return back()->with('success', 'Thank you for your review.');
    }
    
    /**
     * Show the form for editing appointment
     */
    public function edit(Appointment $appointment)
    {
        $this->assertPortalPatientOwns($appointment->patient_id);

        // SECURITY: If user is a doctor, ensure they can only edit their own appointments
        if (auth()->user()->hasRole('doctor') && $appointment->doctor_id != auth()->id()) {
            abort(403, 'You can only edit your own appointments.');
        }

        $patients = Patient::latest()->get();
        
        // SECURITY: If user is a doctor, only show themselves
        if (auth()->user()->hasRole('doctor')) {
            $doctors = collect([auth()->user()]);
        } else {
            $doctors = User::role('doctor')->get();
        }
        
        $branches = Branch::where('is_active', true)->get();
        
        return view('appointments.edit', compact('appointment', 'patients', 'doctors', 'branches'));
    }
    
    /**
     * Update the specified appointment
     */
    public function update(Request $request, Appointment $appointment)
    {
        $this->assertPortalPatientOwns($appointment->patient_id);

        // SECURITY: If user is a doctor, ensure they can only update their own appointments
        if (auth()->user()->hasRole('doctor') && $appointment->doctor_id != auth()->id()) {
            abort(403, 'You can only update your own appointments.');
        }

        // SECURITY: Doctors cannot change doctor_id - preserve their ID
        if (auth()->user()->hasRole('doctor')) {
            $request->merge(['doctor_id' => auth()->id()]);
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'appointment_date' => 'required|date',
            'appointment_time' => 'required',
            'reason' => 'nullable|string',
            'appointment_type' => 'required|in:in-person,teleconsultation',
            'status' => 'required|in:scheduled,completed,cancelled,no-show',
        ]);
        
        $validated['updated_by'] = auth()->id();
        
        try {
            $appointment->update($validated);
            
            return redirect()->route('appointments.show', $appointment)
                ->with('success', 'Appointment updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update appointment. Please try again.');
        }
    }
    
    /**
     * Remove the specified appointment
     */
    public function destroy(Appointment $appointment)
    {
        $this->assertPortalPatientOwns($appointment->patient_id);

        // SECURITY: If user is a doctor, ensure they can only delete their own appointments
        if (auth()->user()->hasRole('doctor') && $appointment->doctor_id != auth()->id()) {
            abort(403, 'You can only delete your own appointments.');
        }

        try {
            $appointmentNumber = $appointment->appointment_number;
            $appointment->delete();
            
            return redirect()->route('appointments.index')
                ->with('success', 'Appointment ' . $appointmentNumber . ' deleted successfully!');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete appointment.');
        }
    }

    /**
     * Create teleconsultation and Jitsi meeting for appointment
     */
    private function createTeleconsultationForAppointment(Appointment $appointment, array $validated)
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

            Log::info('Teleconsultation created for appointment', [
                'appointment_id' => $appointment->id,
                'teleconsultation_id' => $teleconsultation->id,
                'meeting_url' => $teleconsultation->meeting_url
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create teleconsultation for appointment', [
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
     * Check for appointment conflicts (matches API behavior)
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
     * Calculate appointment fee for display
     */
    public function calculateFee(Request $request, Appointment $appointment)
    {
        // Get parameters from appointment if not provided in request
        $doctorId = $request->input('doctor_id') ?? $appointment->doctor_id;
        $branchId = $request->input('branch_id') ?? $appointment->branch_id;
        $appointmentType = $request->input('appointment_type') ?? $appointment->appointment_type;
        
        $validator = Validator::make([
            'doctor_id' => $doctorId,
            'branch_id' => $branchId,
            'appointment_type' => $appointmentType,
        ], [
            'doctor_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'appointment_type' => 'required|in:in-person,teleconsultation',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $feeQuery = AppointmentFee::where('branch_id', $branchId)
                ->where('appointment_type', $appointmentType)
                ->where(function ($q) use ($doctorId) {
                    $q->where('doctor_id', $doctorId)
                        ->orWhereNull('doctor_id');
                })
                ->effective()
                ->active()
                ->orderBy('doctor_id', 'desc');

            $fee = $feeQuery->first();

            if (!$fee) {
                return response()->json([
                    'success' => false,
                    'message' => 'No fee structure found for this appointment type',
                    'fee' => null,
                    'breakdown' => null
                ], 404);
            }

            $breakdown = $fee->applyDiscounts([]);

            return response()->json([
                'success' => true,
                'fee' => $fee,
                'breakdown' => $breakdown,
                'message' => 'Fee calculated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to calculate appointment fee', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate fee: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initialize Paystack payment for appointment (Web)
     */
    public function initializePayment(Request $request, Appointment $appointment)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'nullable|email',
            ]);

            if ($validator->fails()) {
                return back()->with('error', 'Invalid email address.')->withInput();
            }

            $appointment->load(['patient.user', 'doctor']);

            // Get email from request, patient record, or user account
            $email = $request->email 
                ?? $appointment->patient->email 
                ?? ($appointment->patient->user ? $appointment->patient->user->email : null)
                ?? null;
            
            if (!$email) {
                return back()->with('error', 'Email is required for payment. Please provide an email address.')->withInput();
            }

            // Generate unique payment reference
            $reference = $this->generateAppointmentPaymentReference($appointment);

            // Get appointment fee
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

            if (!$fee) {
                return back()->with('error', 'No fee structure found for this appointment.')->withInput();
            }

            $breakdown = $fee->applyDiscounts([]);
            $amount = $breakdown['total'];

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

            // Get dynamic callback URL and append appointment_id for web redirect
            $baseCallbackUrl = \App\Models\PaymentSetting::getPaystackCallbackUrl();
            $callbackUrl = $baseCallbackUrl . '?appointment_id=' . $appointment->id . '&reference=' . $reference;

            // Initialize payment with Paystack
            $paystackService = new PaystackService();
            $result = $paystackService->initializeTransaction(
                $email,
                $amount,
                $reference,
                $metadata,
                $callbackUrl
            );

            if (!$result['success']) {
                return back()->with('error', 'Payment initialization failed. Please try again.')->withInput();
            }

            // Redirect to Paystack payment page
            return redirect($result['data']['authorization_url']);

        } catch (\Exception $e) {
            Log::error('Appointment Paystack initialization error (Web): ' . $e->getMessage());

            return back()->with('error', 'Payment initialization failed: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Process Paystack payment callback for appointment (Web)
     */
    public function processPayment(Request $request)
    {
        try {
            // Get reference from query parameter (Paystack callback)
            $reference = $request->query('reference') ?? $request->input('reference');
            
            if (!$reference) {
                return redirect()->route('appointments.index')
                    ->with('error', 'Invalid payment reference.');
            }

            // Get appointment_id from request query parameters (passed by callback URL)
            $appointmentId = $request->query('appointment_id') ?? $request->input('appointment_id');
            
            if (!$appointmentId) {
                // Try to find from existing payment record (if payment was already recorded)
                $payment = \App\Models\Payment::where('reference_number', $reference)->first();
                
                // First check payment metadata (most reliable)
                if ($payment && $payment->metadata) {
                    $paymentMetadata = $payment->metadata;
                    $appointmentId = $paymentMetadata['appointment_id'] ?? $paymentMetadata['reference_id'] ?? null;
                }
                
                // If not found in metadata, try to extract from notes (fallback)
                if (!$appointmentId && $payment && $payment->notes) {
                    // Try to extract appointment ID from notes (format: "Appointment payment - ... - Appointment ID: X")
                    if (preg_match('/Appointment ID:\s*(\d+)/i', $payment->notes, $matches)) {
                        $appointmentId = $matches[1];
                    }
                }
            }
            
            // If still not found, try to get from Paystack transaction metadata (last resort)
            if (!$appointmentId) {
                $paystackService = new PaystackService();
                $verification = $paystackService->verifyTransaction($reference);
                if ($verification['success'] && isset($verification['data']['metadata'])) {
                    $paystackMetadata = $verification['data']['metadata'];
                    $appointmentId = $paystackMetadata['appointment_id'] ?? $paystackMetadata['reference_id'] ?? null;
                }
            }
            
            if (!$appointmentId) {
                return redirect()->route('appointments.index')
                    ->with('error', 'Appointment ID not found in payment reference. Please contact support.');
            }

            $appointment = Appointment::findOrFail($appointmentId);

            // Verify payment with Paystack (if not already verified above)
            if (!isset($verification)) {
                $paystackService = new PaystackService();
                $verification = $paystackService->verifyTransaction($reference);
            }

            if (!$verification['success']) {
                return redirect()->route('appointments.show', $appointment)
                    ->with('error', 'Payment verification failed. Please contact support.');
            }

            $transactionData = $verification['data'];

            // Check if payment was successful
            if ($transactionData['status'] !== 'success') {
                return redirect()->route('appointments.show', $appointment)
                    ->with('error', 'Payment was not successful. Please try again.');
            }

            DB::beginTransaction();

            // Load appointment with relationships
            $appointment = Appointment::with(['patient', 'doctor', 'branch'])->findOrFail($appointment->id);

            // Get appointment fee amount
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

            if (!$fee) {
                DB::rollBack();
                return redirect()->route('appointments.show', $appointment)
                    ->with('error', 'No fee structure found for this appointment.');
            }

            $breakdown = $fee->applyDiscounts([]);
            $amount = $breakdown['total'];

            // Convert amount from kobo back to cedis
            $amountPaid = ($transactionData['amount'] ?? 0) / 100;

            // Create invoice for appointment
            $invoiceService = app(InvoiceService::class);
            $invoiceItems = [
                [
                    'type' => 'service',
                    'description' => 'Appointment Fee - ' . ($appointment->appointment_type === 'teleconsultation' ? 'Teleconsultation' : 'In-Person Consultation'),
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'total' => $amount,
                    'service_type' => 'appointment',
                    'service_code' => 'APT-' . $appointment->id,
                ]
            ];

            $invoice = $invoiceService->createInvoice(
                $appointment->patient_id,
                $appointment->branch_id,
                $invoiceItems,
                [
                    'invoice_date' => now()->toDateString(),
                    'due_date' => now()->toDateString(),
                    'status' => 'paid',
                    'notes' => 'Appointment payment via Paystack - Reference: ' . $reference,
                    'created_by' => auth()->id()
                ]
            );

            // Record payment using PaymentService
            $paymentService = app(PaymentService::class);
            $paymentResult = $paymentService->recordPayment(
                $invoice->id,
                $amountPaid,
                \App\Enums\PaymentMethod::Paystack->value,
                [
                    'reference_number' => $reference,
                    'transaction_id' => $transactionData['id'] ?? null,
                    'notes' => 'Appointment payment - ' . ($appointment->appointment_type === 'teleconsultation' ? 'Teleconsultation' : 'In-Person') . ' - Appointment ID: ' . $appointment->id,
                    'processed_by' => auth()->id(),
                    'source_platform' => 'web',
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
                return redirect()->route('appointments.show', $appointment)
                    ->with('error', 'Failed to record payment: ' . $paymentResult['message']);
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

            return redirect()->route('appointments.show', $appointment)
                ->with('success', 'Payment processed successfully! Appointment confirmed.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Appointment Paystack payment error (Web): ' . $e->getMessage());

            return redirect()->route('appointments.index')
                ->with('error', 'Payment processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Record staff-assisted appointment payment (cash or offline MoMo).
     */
    public function recordStaffPayment(Request $request, Appointment $appointment)
    {
        if ($appointment->billing_status === 'paid') {
            return back()->with('error', 'Appointment is already paid.');
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,mobile_money_offline',
            'amount_tendered' => 'nullable|numeric|min:0',
            'momo_phone' => 'nullable|string',
            'momo_network' => 'nullable|in:MTN,Vodafone,AirtelTigo',
            'momo_reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $appointment = Appointment::with(['patient', 'doctor', 'branch'])->findOrFail($appointment->id);

            $feeQuery = AppointmentFee::where('branch_id', $appointment->branch_id)
                ->where('appointment_type', $appointment->appointment_type)
                ->where(function ($q) use ($appointment) {
                    $q->where('doctor_id', $appointment->doctor_id)->orWhereNull('doctor_id');
                })
                ->effective()
                ->active()
                ->orderBy('doctor_id', 'desc');

            $fee = $feeQuery->first();
            if (!$fee) {
                throw new \Exception('No fee structure found for this appointment.');
            }

            $amount = $fee->applyDiscounts([])['total'];

            $invoiceService = app(InvoiceService::class);
            $invoice = $invoiceService->createInvoice(
                $appointment->patient_id,
                $appointment->branch_id,
                [[
                    'type' => 'service',
                    'description' => 'Appointment Fee - ' . ucfirst($appointment->appointment_type),
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'total' => $amount,
                    'service_type' => 'appointment',
                    'service_code' => 'APT-' . $appointment->id,
                ]],
                [
                    'invoice_date' => now()->toDateString(),
                    'due_date' => now()->toDateString(),
                    'status' => 'pending',
                    'payment_method' => $validated['payment_method'],
                    'notes' => 'Staff-recorded appointment payment',
                    'created_by' => auth()->id(),
                ]
            );

            $paymentService = app(PaymentService::class);
            $paymentResult = $paymentService->recordPayment(
                $invoice->id,
                $amount,
                $validated['payment_method'],
                PaymentMetadata::fromRequest($request, [
                    'reference_number' => $this->generateAppointmentPaymentReference($appointment),
                    'notes' => $validated['notes'] ?? 'Staff appointment payment',
                    'processed_by' => auth()->id(),
                    'source_platform' => 'web',
                    'ip_address' => $request->ip(),
                    'metadata' => [
                        'appointment_id' => $appointment->id,
                        'payment_type' => 'appointment',
                    ],
                ])
            );

            if (!$paymentResult['success']) {
                throw new \Exception($paymentResult['message']);
            }

            $appointment->update([
                'billing_status' => 'paid',
                'updated_by' => auth()->id(),
            ]);

            if ($appointment->status === 'pending') {
                $appointment->update(['status' => 'scheduled']);
            }

            DB::commit();

            return redirect()->route('appointments.show', $appointment)
                ->with('success', 'Appointment payment recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Appointment staff payment error: ' . $e->getMessage());

            return back()->with('error', 'Payment failed: ' . $e->getMessage())->withInput();
        }
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
        
        // Ensure uniqueness
        $exists = \App\Models\Payment::where('reference_number', $reference)->exists();
        
        if ($exists) {
            $reference = "{$prefix}-{$appointmentId}-{$timestamp}-" . strtoupper(substr(uniqid(true), -8));
        }
        
        return $reference;
    }

    public function export(Request $request)
    {
        $this->resolveUserBranchId('view_appointments');

        $query = Appointment::with(['patient', 'doctor', 'branch'])
            ->whereHas('patient');

        if (auth()->user()->hasRole('doctor')) {
            $query->where('doctor_id', auth()->id());
        }

        $query->latest('id');

        return $this->exportFromQuery($request, $query, [
            'Patient' => fn ($a) => $a->patient?->full_name ?? '',
            'Patient Number' => fn ($a) => $a->patient?->patient_number ?? '',
            'Doctor' => fn ($a) => $this->formatExportUserName($a->doctor),
            'Branch' => fn ($a) => $a->branch?->name ?? '',
            'Date' => fn ($a) => $this->formatExportDate($a->appointment_date),
            'Time' => 'appointment_time',
            'Type' => 'appointment_type',
            'Status' => 'status',
        ], 'appointments', 'view_appointments');
    }
}
