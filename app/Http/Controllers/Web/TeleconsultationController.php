<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Http\Controllers\Concerns\WorkflowNavigation;
use App\Models\Teleconsultation;
use App\Models\Patient;
use App\Models\User;
use App\Models\Branch;
use App\Models\Consultation;
use App\Models\Visit;
use App\Services\JitsiService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TeleconsultationController extends Controller
{
    use ResolvesUserBranch, WorkflowNavigation;
    protected $jitsiService;

    public function __construct(JitsiService $jitsiService)
    {
        $this->jitsiService = $jitsiService;
    }

    /**
     * Display a listing of teleconsultations.
     */
    public function index(Request $request)
    {
        $portalPatient = $this->portalPatient();

        if ($portalPatient) {
            $branchId = $portalPatient->branch_id;
        } else {
            $branchId = $request->get('branch_id');
            if (!$branchId) {
                $userBranch = Auth::user()->branches()->first();
                $branchId = $userBranch ? $userBranch->id : 1;
            }
        }
        
        $query = Teleconsultation::with(['patient', 'doctor', 'branch', 'appointment'])
            ->where('branch_id', $branchId)
            ->whereHas('patient');

        if ($portalPatient) {
            $query->where('patient_id', $portalPatient->id);
        }

        // SECURITY: If user is a doctor, show only their teleconsultations
        if (Auth::user()->hasRole('doctor')) {
            $query->where('doctor_id', Auth::id());
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by doctor (only for non-doctors)
        if (!$request->user()->hasRole('doctor') && $request->filled('doctor_id')) {
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

        $teleconsultations = $query->orderBy('scheduled_at', 'desc')
            ->paginate($request->get('per_page', 15));

        $doctors = User::whereHas('roles', function ($q) {
            $q->where('name', 'doctor');
        })->get();

        $patients = Patient::orderBy('first_name')->get();

        return view('teleconsultations.index', compact('teleconsultations', 'doctors', 'patients'));
    }

    /**
     * Debug method to check teleconsultations data
     */
    public function debug(Request $request)
    {
        $branchId = $request->get('branch_id');
        if (!$branchId) {
            $userBranch = Auth::user()->branches()->first();
            $branchId = $userBranch ? $userBranch->id : 1;
        }
        
        $query = Teleconsultation::with(['patient', 'doctor', 'branch', 'appointment'])
            ->where('branch_id', $branchId)
            ->whereHas('patient'); // Only include teleconsultations with valid patients

        $teleconsultations = $query->orderBy('scheduled_at', 'desc')
            ->paginate($request->get('per_page', 15));

        $doctors = User::whereHas('roles', function ($q) {
            $q->where('name', 'doctor');
        })->get();

        $patients = Patient::orderBy('first_name')->get();

        return view('teleconsultations.debug', compact('teleconsultations', 'doctors', 'patients'));
    }

    /**
     * Test method to check teleconsultations data with simple view
     */
    public function test(Request $request)
    {
        $branchId = $request->get('branch_id');
        if (!$branchId) {
            $userBranch = Auth::user()->branches()->first();
            $branchId = $userBranch ? $userBranch->id : 1;
        }
        
        $query = Teleconsultation::with(['patient', 'doctor', 'branch', 'appointment'])
            ->where('branch_id', $branchId)
            ->whereHas('patient'); // Only include teleconsultations with valid patients

        $teleconsultations = $query->orderBy('scheduled_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return view('teleconsultations.test', compact('teleconsultations'));
    }

    /**
     * Show the form for creating a new teleconsultation.
     */
    public function create()
    {
        $patients = Patient::orderBy('first_name')->get();
        
        // SECURITY: If user is a doctor, only show themselves
        if (Auth::user()->hasRole('doctor')) {
            $doctors = collect([Auth::user()]);
        } else {
            $doctors = User::whereHas('roles', function ($q) {
                $q->where('name', 'doctor');
            })->get();
        }
        
        $branches = Branch::all();

        return view('teleconsultations.create', compact('patients', 'doctors', 'branches'));
    }

    /**
     * Store a newly created teleconsultation.
     */
    public function store(Request $request)
    {
        // SECURITY: If user is a doctor, force doctor_id to be their own ID
        if (Auth::user()->hasRole('doctor')) {
            $request->merge(['doctor_id' => Auth::id()]);
        }

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
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Double-check: If user is a doctor, ensure they can only create for themselves
        if (Auth::user()->hasRole('doctor') && $request->doctor_id != Auth::id()) {
            return back()->with('error', 'You can only create teleconsultations for yourself.')->withInput();
        }

        // Get user's branch
        $userBranch = Auth::user()->branches()->first();
        $branchId = $userBranch ? $userBranch->id : 1;

        $teleconsultation = Teleconsultation::create([
            'appointment_id' => $request->appointment_id,
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'branch_id' => $branchId,
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
            session()->flash('success', 'Teleconsultation created successfully with Jitsi Meet integration.');
        } catch (\Exception $e) {
            session()->flash('warning', 'Teleconsultation created but Jitsi Meet integration failed: ' . $e->getMessage());
        }

        return redirect()->route('teleconsultations.show', $teleconsultation);
    }

    /**
     * Display the specified teleconsultation.
     */
    public function show(Teleconsultation $teleconsultation)
    {
        $this->assertPortalPatientOwns($teleconsultation->patient_id);

        // SECURITY: If user is a doctor, ensure they can only view their own teleconsultations
        if (Auth::user()->hasRole('doctor') && $teleconsultation->doctor_id != Auth::id()) {
            abort(403, 'You can only view your own teleconsultations.');
        }

        $teleconsultation->load(['patient', 'doctor', 'branch', 'appointment', 'chatMessages.sender', 'sharedFiles.uploader']);
        
        // Get Jitsi configuration
        $jitsiConfig = $this->jitsiService->getClientConfig();
        $jitsiConfig['meeting'] = [
            'room_name' => $teleconsultation->meeting_id,
            'meeting_url' => $teleconsultation->meeting_url,
            'meeting_password' => $teleconsultation->meeting_password,
        ];

        return view('teleconsultations.show', compact('teleconsultation', 'jitsiConfig'));
    }

    /**
     * Show the form for editing the specified teleconsultation.
     */
    public function edit(Teleconsultation $teleconsultation)
    {
        // SECURITY: If user is a doctor, ensure they can only edit their own teleconsultations
        if (Auth::user()->hasRole('doctor') && $teleconsultation->doctor_id != Auth::id()) {
            abort(403, 'You can only edit your own teleconsultations.');
        }

        $patients = Patient::orderBy('first_name')->get();
        
        // SECURITY: If user is a doctor, only show themselves
        if (Auth::user()->hasRole('doctor')) {
            $doctors = collect([Auth::user()]);
        } else {
            $doctors = User::whereHas('roles', function ($q) {
                $q->where('name', 'doctor');
            })->get();
        }
        
        $branches = Branch::all();

        return view('teleconsultations.edit', compact('teleconsultation', 'patients', 'doctors', 'branches'));
    }

    /**
     * Update the specified teleconsultation.
     */
    public function update(Request $request, Teleconsultation $teleconsultation)
    {
        // SECURITY: If user is a doctor, ensure they can only update their own teleconsultations
        if (Auth::user()->hasRole('doctor') && $teleconsultation->doctor_id != Auth::id()) {
            abort(403, 'You can only update your own teleconsultations.');
        }

        $validator = Validator::make($request->all(), [
            'scheduled_at' => 'sometimes|date|after:now',
            'consultation_type' => 'sometimes|in:video,audio,chat',
            'consultation_notes' => 'nullable|string',
            'video_enabled' => 'boolean',
            'audio_enabled' => 'boolean',
            'recording_enabled' => 'boolean',
            'status' => 'sometimes|in:scheduled,waiting,in_progress,completed,cancelled,failed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // SECURITY: Doctors cannot change doctor_id - it's already set and disabled in form

        $teleconsultation->update(array_merge(
            $request->only([
                'scheduled_at',
                'consultation_type',
                'consultation_notes',
                'video_enabled',
                'audio_enabled',
                'recording_enabled',
                'status'
            ]),
            ['updated_by' => Auth::id()]
        ));

        session()->flash('success', 'Teleconsultation updated successfully.');
        return redirect()->route('teleconsultations.show', $teleconsultation);
    }

    /**
     * Remove the specified teleconsultation from storage.
     */
    public function destroy(Teleconsultation $teleconsultation)
    {
        // SECURITY: If user is a doctor, ensure they can only delete their own teleconsultations
        if (Auth::user()->hasRole('doctor') && $teleconsultation->doctor_id != Auth::id()) {
            abort(403, 'You can only delete your own teleconsultations.');
        }

        $teleconsultation->delete();
        session()->flash('success', 'Teleconsultation deleted successfully.');
        return redirect()->route('teleconsultations.index');
    }

    /**
     * Start a teleconsultation.
     */
    public function start(Teleconsultation $teleconsultation)
    {
        if (!$teleconsultation->canStart()) {
            session()->flash('error', 'Teleconsultation cannot be started at this time');
            return redirect()->back();
        }

        $teleconsultation->start();
        session()->flash('success', 'Teleconsultation started successfully');
        return redirect()->route('teleconsultations.show', $teleconsultation);
    }

    /**
     * End a teleconsultation.
     */
    public function end(Teleconsultation $teleconsultation)
    {
        if (!$teleconsultation->isActive()) {
            session()->flash('error', 'Teleconsultation is not active');
            return redirect()->back();
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

            // Get workflow next step and redirect
            return $this->redirectToNextStep($consultation, 'Teleconsultation ended successfully! Consultation #' . $consultation->id . ' created.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error ending teleconsultation: ' . $e->getMessage(), [
                'teleconsultation_id' => $teleconsultation->id,
                'trace' => $e->getTraceAsString()
            ]);

            session()->flash('error', 'Failed to end teleconsultation: ' . $e->getMessage());
            return redirect()->back();
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
        $visit = Visit::create([
            'patient_id' => $teleconsultation->patient_id,
            'branch_id' => $teleconsultation->branch_id,
            'visit_type' => 'OPD',
            'status' => 'active',
            'check_in_time' => $teleconsultation->started_at ?? now(),
            'created_by' => $teleconsultation->created_by ?? Auth::id(),
        ]);

        // Initialize workflow for visit
        $this->initializeWorkflowForEntity($visit, 'OPD Visit');

        return $visit;
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
    public function cancel(Request $request, Teleconsultation $teleconsultation)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $teleconsultation->cancel($request->reason);
        session()->flash('success', 'Teleconsultation cancelled successfully');
        return redirect()->route('teleconsultations.show', $teleconsultation);
    }

    /**
     * Give patient consent for teleconsultation.
     */
    public function giveConsent(Teleconsultation $teleconsultation)
    {
        if ($teleconsultation->patient_consent_given) {
            session()->flash('warning', 'Consent already given');
            return redirect()->back();
        }

        $teleconsultation->update([
            'patient_consent_given' => true,
            'consent_given_at' => now(),
        ]);

        session()->flash('success', 'Consent given successfully');
        return redirect()->route('teleconsultations.show', $teleconsultation);
    }

    /**
     * Generate patient JWT token for joining meeting
     */
    public function generatePatientToken(Teleconsultation $teleconsultation)
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
}
