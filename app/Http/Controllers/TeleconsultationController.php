<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\WorkflowNavigation;
use App\Models\Teleconsultation;
use App\Models\TeleconsultationChat;
use App\Models\TeleconsultationFile;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\Branch;
use App\Models\Consultation;
use App\Models\Visit;
use App\Services\JitsiService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeleconsultationController extends Controller
{
    use WorkflowNavigation;
    protected $jitsiService;

    public function __construct(JitsiService $jitsiService)
    {
        $this->jitsiService = $jitsiService;
        $this->middleware('permission:teleconsultation.view')->only(['index', 'show', 'statistics']);
        $this->middleware('permission:teleconsultation.create')->only(['store']);
        $this->middleware('permission:teleconsultation.edit')->only(['update']);
        $this->middleware('permission:teleconsultation.delete')->only(['destroy']);
        $this->middleware('permission:teleconsultation.start')->only(['start']);
        $this->middleware('permission:teleconsultation.end')->only(['end']);
        $this->middleware('permission:teleconsultation.cancel')->only(['cancel']);
        $this->middleware('permission:teleconsultation.consent.give')->only(['giveConsent']);
    }

    /**
     * Display a listing of teleconsultations.
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = $request->get('branch_id');
        if (!$branchId) {
            // Get user's first branch if no branch_id provided
            $userBranch = Auth::user()->branches()->first();
            $branchId = $userBranch ? $userBranch->id : 1;
        }
        
        $query = Teleconsultation::with(['patient', 'doctor', 'branch', 'appointment'])
            ->where('branch_id', $branchId);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by doctor
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // Filter by patient
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_at', '<=', $request->date_to);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 20);
        $teleconsultations = $query->orderBy('scheduled_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $teleconsultations->items(),
                'pagination' => [
                    'current_page' => $teleconsultations->currentPage(),
                    'per_page' => $teleconsultations->perPage(),
                    'total' => $teleconsultations->total(),
                    'last_page' => $teleconsultations->lastPage(),
                    'has_more_pages' => $teleconsultations->hasMorePages(),
                ],
            ],
            'message' => 'Teleconsultations retrieved successfully'
        ]);
    }

    /**
     * Store a newly created teleconsultation.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'nullable|exists:appointments,id',
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'scheduled_at' => 'required|date|after:now',
            'consultation_type' => 'required|in:video,audio,chat',
            'consultation_notes' => 'nullable|string',
            'patient_preferences' => 'nullable|array',
            'video_enabled' => 'boolean',
            'audio_enabled' => 'boolean',
            'recording_enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $teleconsultation = Teleconsultation::create([
            'appointment_id' => $request->appointment_id,
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'branch_id' => Auth::user()->branch_id,
            'scheduled_at' => $request->scheduled_at,
            'consultation_type' => $request->consultation_type,
            'consultation_notes' => $request->consultation_notes,
            'patient_preferences' => $request->patient_preferences,
            'video_enabled' => $request->video_enabled ?? true,
            'audio_enabled' => $request->audio_enabled ?? true,
            'recording_enabled' => $request->recording_enabled ?? false,
            'created_by' => Auth::id(),
        ]);

        // Generate Jitsi meeting
        try {
            $meetingData = $this->jitsiService->createMeeting($teleconsultation);
            
            return response()->json([
                'success' => true,
                'message' => 'Teleconsultation created successfully',
                'data' => $teleconsultation->load(['patient', 'doctor', 'branch', 'appointment']),
                'meeting' => $meetingData['meeting']
            ], 201);
        } catch (\Exception $e) {
            // If Jitsi fails, create basic teleconsultation without meeting
            $meetingUrl = $this->generateMeetingUrl($teleconsultation);
            $meetingPassword = $this->generateMeetingPassword();

            $teleconsultation->update([
                'meeting_url' => $meetingUrl,
                'meeting_password' => $meetingPassword,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Teleconsultation created successfully',
            'data' => $teleconsultation->load(['patient', 'doctor', 'branch', 'appointment'])
        ], 201);
    }

    /**
     * Display the specified teleconsultation.
     */
    public function show(Teleconsultation $teleconsultation): JsonResponse
    {
        $teleconsultation->load(['patient', 'doctor', 'branch', 'appointment', 'chatMessages.sender', 'sharedFiles.uploader']);

        return response()->json([
            'success' => true,
            'data' => $teleconsultation
        ]);
    }

    /**
     * Update the specified teleconsultation.
     */
    public function update(Request $request, Teleconsultation $teleconsultation): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'scheduled_at' => 'sometimes|date|after:now',
            'consultation_type' => 'sometimes|in:video,audio,chat',
            'consultation_notes' => 'nullable|string',
            'patient_preferences' => 'nullable|array',
            'video_enabled' => 'boolean',
            'audio_enabled' => 'boolean',
            'recording_enabled' => 'boolean',
            'status' => 'sometimes|in:scheduled,waiting,in_progress,completed,cancelled,failed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $teleconsultation->update(array_merge(
            $request->only([
                'scheduled_at',
                'consultation_type',
                'consultation_notes',
                'patient_preferences',
                'video_enabled',
                'audio_enabled',
                'recording_enabled',
                'status'
            ]),
            ['updated_by' => Auth::id()]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Teleconsultation updated successfully',
            'data' => $teleconsultation->load(['patient', 'doctor', 'branch', 'appointment'])
        ]);
    }

    /**
     * Start a teleconsultation.
     */
    public function start(Teleconsultation $teleconsultation): JsonResponse
    {
        if (!$teleconsultation->canStart()) {
            return response()->json([
                'success' => false,
                'message' => 'Teleconsultation cannot be started at this time'
            ], 400);
        }

        $teleconsultation->start();

        return response()->json([
            'success' => true,
            'message' => 'Teleconsultation started successfully',
            'data' => $teleconsultation
        ]);
    }

    /**
     * End a teleconsultation.
     */
    public function end(Teleconsultation $teleconsultation): JsonResponse
    {
        if (!$teleconsultation->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Teleconsultation is not active'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // End the teleconsultation
            $teleconsultation->end();

            // Create or find visit for this teleconsultation
            $visit = $this->createOrFindVisitForTeleconsultation($teleconsultation);

            // Create consultation record from teleconsultation
            $consultation = $this->createConsultationFromTeleconsultation($teleconsultation, $visit);

            // Link consultation to teleconsultation
            $teleconsultation->update(['consultation_id' => $consultation->id]);

            // Initialize workflow for consultation if needed
            if (!$consultation->workflowInstance) {
                $this->initializeWorkflowForEntity($consultation, 'OPD Consultation');
            }

            // Complete consultation workflow step (since teleconsultation is complete)
            if ($consultation->workflowInstance) {
                $this->completeWorkflowStep($consultation, 'consultation', [
                    'teleconsultation_id' => $teleconsultation->id,
                    'consultation_type' => 'teleconsultation',
                    'duration_minutes' => $teleconsultation->duration_minutes,
                ]);
            }

            DB::commit();

            // Get workflow next step suggestion
            $response = [
                'success' => true,
                'message' => 'Teleconsultation ended successfully',
                'data' => $teleconsultation->load(['patient', 'doctor', 'branch', 'appointment', 'consultation']),
                'consultation_id' => $consultation->id,
            ];

            if ($consultation->workflowInstance) {
                $workflowResponse = $this->getNextStepResponse($consultation, 'Teleconsultation completed. Consultation created.');
                $response['workflow'] = $workflowResponse->getData(true)['workflow'] ?? null;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error ending teleconsultation: ' . $e->getMessage(), [
                'teleconsultation_id' => $teleconsultation->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to end teleconsultation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create or find visit for teleconsultation
     */
    private function createOrFindVisitForTeleconsultation(Teleconsultation $teleconsultation): Visit
    {
        // Check if appointment has a visit
        if ($teleconsultation->appointment && $teleconsultation->appointment->visit_id) {
            return Visit::findOrFail($teleconsultation->appointment->visit_id);
        }

        // Check if there's an active visit for this patient today
        $todayVisit = Visit::where('patient_id', $teleconsultation->patient_id)
            ->where('branch_id', $teleconsultation->branch_id)
            ->whereDate('created_at', today())
            ->where('status', 'active')
            ->first();

        if ($todayVisit) {
            return $todayVisit;
        }

        // Create new visit for teleconsultation
        return Visit::create([
            'patient_id' => $teleconsultation->patient_id,
            'branch_id' => $teleconsultation->branch_id,
            'visit_type' => 'OPD',
            'status' => 'active',
            'check_in_time' => $teleconsultation->started_at ?? now(),
            'created_by' => $teleconsultation->created_by ?? Auth::id(),
        ]);
    }

    /**
     * Create consultation record from teleconsultation
     */
    private function createConsultationFromTeleconsultation(Teleconsultation $teleconsultation, Visit $visit): Consultation
    {
        // Check if consultation already exists
        if ($teleconsultation->consultation_id) {
            return Consultation::findOrFail($teleconsultation->consultation_id);
        }

        $consultation = Consultation::create([
            'visit_id' => $visit->id,
            'patient_id' => $teleconsultation->patient_id,
            'doctor_id' => $teleconsultation->doctor_id,
            'branch_id' => $teleconsultation->branch_id,
            'consultation_date' => $teleconsultation->started_at ? $teleconsultation->started_at->toDateString() : now()->toDateString(),
            'consultation_time' => $teleconsultation->started_at ? $teleconsultation->started_at->format('H:i') : now()->format('H:i'),
            'consultation_type' => 'teleconsultation',
            'chief_complaint' => $teleconsultation->consultation_notes ?? 'Teleconsultation',
            'consultation_status' => 'completed',
            'started_at' => $teleconsultation->started_at,
            'completed_at' => $teleconsultation->ended_at ?? now(),
            'notes' => $this->formatTeleconsultationNotes($teleconsultation),
            'created_by' => $teleconsultation->created_by ?? Auth::id(),
        ]);

        // Consultation can now be billed using PricingService via generateFromConsultation endpoint
        // The workflow will guide users to billing as the next step

        return $consultation;
    }

    /**
     * Format teleconsultation notes for consultation
     */
    private function formatTeleconsultationNotes(Teleconsultation $teleconsultation): string
    {
        $notes = [];
        
        if ($teleconsultation->consultation_notes) {
            $notes[] = "Chief Complaint: " . $teleconsultation->consultation_notes;
        }
        
        if ($teleconsultation->outcome) {
            $notes[] = "Outcome: " . $teleconsultation->outcome;
        }
        
        if ($teleconsultation->follow_up_notes) {
            $notes[] = "Follow-up Notes: " . $teleconsultation->follow_up_notes;
        }
        
        $notes[] = "Duration: " . ($teleconsultation->duration_minutes ?? 0) . " minutes";
        $notes[] = "Type: " . ucfirst($teleconsultation->consultation_type);
        
        if ($teleconsultation->connection_quality) {
            $notes[] = "Connection Quality: " . $teleconsultation->connection_quality;
        }

        return implode("\n\n", $notes);
    }

    /**
     * Cancel a teleconsultation.
     */
    public function cancel(Request $request, Teleconsultation $teleconsultation): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $teleconsultation->cancel($request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Teleconsultation cancelled successfully',
            'data' => $teleconsultation
        ]);
    }

    /**
     * Get patient consent for teleconsultation.
     */
    public function getConsent(Teleconsultation $teleconsultation): JsonResponse
    {
        return response()->json([
            'success' => true,
            'consent_given' => $teleconsultation->patient_consent_given,
            'consent_given_at' => $teleconsultation->consent_given_at,
        ]);
    }

    /**
     * Give patient consent for teleconsultation.
     */
    public function giveConsent(Teleconsultation $teleconsultation): JsonResponse
    {
        if ($teleconsultation->patient_consent_given) {
            return response()->json([
                'success' => false,
                'message' => 'Consent already given'
            ], 400);
        }

        $teleconsultation->update([
            'patient_consent_given' => true,
            'consent_given_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Consent given successfully',
            'data' => $teleconsultation
        ]);
    }

    /**
     * Get teleconsultation statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $query = Teleconsultation::where('branch_id', Auth::user()->branch_id);

        if ($request->has('date_from')) {
            $query->whereDate('scheduled_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('scheduled_at', '<=', $request->date_to);
        }

        $stats = [
            'total' => $query->count(),
            'scheduled' => $query->clone()->where('status', 'scheduled')->count(),
            'in_progress' => $query->clone()->where('status', 'in_progress')->count(),
            'completed' => $query->clone()->where('status', 'completed')->count(),
            'cancelled' => $query->clone()->where('status', 'cancelled')->count(),
            'by_type' => [
                'video' => $query->clone()->where('consultation_type', 'video')->count(),
                'audio' => $query->clone()->where('consultation_type', 'audio')->count(),
                'chat' => $query->clone()->where('consultation_type', 'chat')->count(),
            ],
            'average_duration' => $query->clone()->whereNotNull('duration_minutes')->avg('duration_minutes'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get Jitsi meeting configuration for frontend
     */
    public function getJitsiConfig(Teleconsultation $teleconsultation): JsonResponse
    {
        try {
            // Check if user has access to this teleconsultation
            $user = Auth::user();
            $isPatient = $user->hasRole('patient');
            $isDoctor = $user->hasRole('doctor');
            
            // Authorize access
            if ($isPatient && $teleconsultation->patient_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this teleconsultation'
                ], 403);
            }
            
            if ($isDoctor && $teleconsultation->doctor_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this teleconsultation'
                ], 403);
            }
            
            // Get dynamic configuration based on teleconsultation preferences
            $config = $this->jitsiService->getMeetingConfig($teleconsultation);
            
            // Generate appropriate JWT token based on user role
            $jwtToken = null;
            if ($isPatient) {
                $jwtToken = $this->jitsiService->generatePatientJWTToken($teleconsultation, $teleconsultation->meeting_id);
            } else {
                // For doctors and other staff, use the default JWT or doctor token
                $jwtToken = $this->jitsiService->generateJWTToken($teleconsultation, $teleconsultation->meeting_id);
            }
            
            // Build meeting URL with JWT if available
            $meetingUrl = $teleconsultation->meeting_url;
            if ($jwtToken) {
                $meetingUrl .= (strpos($meetingUrl, '?') !== false ? '&' : '?') . 'jwt=' . $jwtToken;
            }
            
            $config['meeting'] = [
                'room_name' => $teleconsultation->meeting_id,
                'meeting_url' => $meetingUrl,
                'meetingUrl' => $meetingUrl, // Alternative key for compatibility
                'meeting_password' => $teleconsultation->meeting_password,
                'jwt_token' => $jwtToken,
                'user_role' => $isPatient ? 'patient' : 'doctor',
                'user_name' => $isPatient ? 
                    $teleconsultation->patient->first_name . ' ' . $teleconsultation->patient->last_name :
                    'Dr. ' . $teleconsultation->doctor->first_name . ' ' . $teleconsultation->doctor->last_name,
            ];

            return response()->json([
                'success' => true,
                'data' => $config
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get Jitsi configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate patient JWT token for joining meeting
     */
    public function generatePatientToken(Teleconsultation $teleconsultation): JsonResponse
    {
        try {
            $jwtToken = $this->jitsiService->generatePatientJWTToken($teleconsultation, $teleconsultation->meeting_id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'jwt_token' => $jwtToken,
                    'meeting_url' => $teleconsultation->meeting_url,
                    'room_name' => $teleconsultation->meeting_id,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate patient token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate meeting URL.
     */
    private function generateMeetingUrl(Teleconsultation $teleconsultation): string
    {
        $baseUrl = config('app.url');
        return "{$baseUrl}/teleconsultation/{$teleconsultation->meeting_id}";
    }

    /**
     * Generate meeting password.
     */
    private function generateMeetingPassword(): string
    {
        return Str::random(8);
    }
}
