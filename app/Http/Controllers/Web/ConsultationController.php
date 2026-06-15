<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Http\Controllers\Concerns\WorkflowNavigation;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\WorkflowInstance;
use App\Models\Diagnosis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ConsultationController extends Controller
{
    use ExportsListData, ResolvesUserBranch, WorkflowNavigation;

    public function index(Request $request)
    {
        $branchId = $this->resolveUserBranchId('view_consultations');
        
        $query = Consultation::with(['patient', 'doctor', 'visit', 'vitals']);

        if ($portalPatient = $this->portalPatient()) {
            $query->where('patient_id', $portalPatient->id);
        } else {
            $query->where('branch_id', $branchId);
        }
        
        // If user is a doctor, show consultations assigned to them OR visits assigned to them
        if (auth()->user()->hasRole('doctor')) {
            $doctorId = auth()->id();
            
            // Get consultations assigned to this doctor
            $query->where(function($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId)
                  // Also include consultations from visits assigned to this doctor
                  ->orWhereHas('visit', function($visitQuery) use ($doctorId) {
                      $visitQuery->where('assigned_doctor_id', $doctorId);
                  });
            });
        }
        
        // Apply filters with comprehensive status handling
        if ($request->has('filter') && $request->filter !== 'all') {
            switch ($request->filter) {
                case 'draft':
                    // Draft consultations (not yet started by doctor)
                    $query->where('is_draft', true)
                          ->where('consultation_status', 'ongoing');
                    break;
                case 'pending':
                    // Pending/Not started consultations (draft, ongoing, not cancelled)
                    $query->where('is_draft', true)
                          ->where('consultation_status', 'ongoing');
                    break;
                case 'in_progress':
                    // In progress consultations (doctor has started, not draft, ongoing)
                    $query->where('is_draft', false)
                          ->where('consultation_status', 'ongoing');
                    break;
                case 'completed':
                    // Completed consultations (not draft, status completed)
                    $query->where('consultation_status', 'completed')
                          ->where('is_draft', false);
                    break;
                case 'ongoing':
                    // All ongoing consultations (both draft and in-progress)
                    $query->where('consultation_status', 'ongoing')
                          ->where('consultation_status', '!=', 'cancelled');
                    break;
                case 'cancelled':
                    // Cancelled consultations
                    $query->where('consultation_status', 'cancelled');
                    break;
            }
        }
        
        // Apply search filter if provided
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('consultation_number', 'like', "%{$search}%")
                  ->orWhere('chief_complaint', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($patientQuery) use ($search) {
                      $patientQuery->where('first_name', 'like', "%{$search}%")
                                   ->orWhere('last_name', 'like', "%{$search}%")
                                   ->orWhere('patient_number', 'like', "%{$search}%")
                                   ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }
        
        // Apply date filter if provided
        if ($request->filled('date_from')) {
            $query->whereDate('consultation_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('consultation_date', '<=', $request->date_to);
        }
        
        // Order by: priority (urgent first), then by creation date (newest first)
        $consultations = $query->orderByRaw("CASE WHEN urgency = 'critical' THEN 1 WHEN urgency = 'urgent' THEN 2 ELSE 3 END")
                              ->orderBy('created_at', 'desc')
                              ->paginate(20);
        
        // Calculate comprehensive statistics - if doctor, only count their consultations
        $statsQuery = Consultation::where('branch_id', $branchId);
        if (auth()->user()->hasRole('doctor')) {
            $doctorId = auth()->id();
            $statsQuery->where(function($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId)
                  ->orWhereHas('visit', function($visitQuery) use ($doctorId) {
                      $visitQuery->where('assigned_doctor_id', $doctorId);
                  });
            });
        }
        
        // Calculate all status categories
        $statistics = [
            'total' => (clone $statsQuery)->where('consultation_status', '!=', 'cancelled')->count(),
            'today' => (clone $statsQuery)->whereDate('consultation_date', today())->where('consultation_status', '!=', 'cancelled')->count(),
            'pending' => (clone $statsQuery)->where('is_draft', true)
                                            ->where('consultation_status', 'ongoing')
                                            ->count(),
            'in_progress' => (clone $statsQuery)->where('is_draft', false)
                                               ->where('consultation_status', 'ongoing')
                                               ->count(),
            'ongoing' => (clone $statsQuery)->where('consultation_status', 'ongoing')
                                           ->where('consultation_status', '!=', 'cancelled')
                                           ->count(),
            'completed' => (clone $statsQuery)->where('consultation_status', 'completed')
                                              ->where('is_draft', false)
                                              ->count(),
            'drafts' => (clone $statsQuery)->where('is_draft', true)
                                          ->where('consultation_status', '!=', 'cancelled')
                                          ->count(),
            'cancelled' => (clone $statsQuery)->where('consultation_status', 'cancelled')->count(),
        ];
        
        $currentFilter = $request->get('filter', 'all');
        $searchTerm = $request->get('search', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');
        
        return view('consultations.index', compact(
            'consultations', 
            'statistics', 
            'currentFilter',
            'searchTerm',
            'dateFrom',
            'dateTo'
        ));
    }
    
    /**
     * Doctor consultation queue - shows consultation requests awaiting doctor review
     */
    public function doctorQueue(Request $request)
    {
        $branchId = $request->get('branch_id') ?? $this->resolveUserBranchId('view_consultations');
        $doctorId = auth()->id();
        
        // Get consultation requests that are in queue (draft consultations awaiting doctor)
        // Explicitly exclude completed and cancelled consultations
        $consultationQueue = Consultation::with(['patient', 'visit', 'vitals'])
            ->where('branch_id', $branchId)
            ->where('consultation_status', 'ongoing') // Only ongoing consultations
            ->whereNotIn('consultation_status', ['completed', 'cancelled', 'transferred']) // Explicit exclusion
            ->where('is_draft', true) // Only draft consultations (created by reception/nurse)
            ->where('doctor_id', $doctorId) // Only consultations assigned to current doctor
            ->orderByRaw("CASE urgency WHEN 'critical' THEN 1 WHEN 'urgent' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Also check for visits assigned to this doctor that don't have consultations yet
        // This handles cases where a nurse assigned a patient to a doctor via visit but consultation wasn't created
        $visitsWithoutConsultations = Visit::with(['patient'])
            ->where('branch_id', $branchId)
            ->where('assigned_doctor_id', $doctorId)
            ->where('status', 'active')
            ->whereDoesntHave('consultation', function($query) {
                // Exclude visits that have any non-cancelled consultation (ongoing or completed)
                $query->whereNotIn('consultation_status', ['cancelled']);
            })
            ->orderByRaw("CASE WHEN priority = 'critical' THEN 1 WHEN priority = 'urgent' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Create draft consultations for visits that don't have consultations yet
        $consultationService = app(\App\Services\ConsultationService::class);
        
        foreach ($visitsWithoutConsultations as $visit) {
            // Use service to create consultation
            $draftConsultation = $consultationService->createDraftConsultationForVisit($visit, $doctorId);
            
            if ($draftConsultation) {
                // Reload with relationships
                $draftConsultation->load(['patient', 'visit', 'vitals']);
                
                // Add to queue
                $consultationQueue->push($draftConsultation);
            }
        }
        
        // Re-sort the queue after adding new consultations
        $consultationQueue = $consultationQueue->sortBy(function($consultation) {
            $priorityOrder = ['critical' => 1, 'urgent' => 2, 'routine' => 3];
            return [
                $priorityOrder[$consultation->urgency ?? 'routine'] ?? 3,
                $consultation->created_at->timestamp
            ];
        })->values();
        
        // Get current consultation being worked on by this doctor (Currently Consulting)
        $currentConsultation = Consultation::with(['patient', 'visit', 'vitals'])
            ->where('branch_id', $branchId)
            ->where('consultation_status', 'ongoing') // Only ongoing
            ->whereNotIn('consultation_status', ['completed', 'cancelled', 'transferred']) // Explicit exclusion
            ->where('is_draft', false) // Not draft (doctor has started working on it)
            ->where('doctor_id', $doctorId)
            ->first();
        
        // Calculate statistics
        $stats = [
            'pending_consultations' => $consultationQueue->count(),
            'in_progress' => $currentConsultation ? 1 : 0, // Only count if there's an active consultation
            'completed_today' => Consultation::where('branch_id', $branchId)
                ->where('doctor_id', $doctorId) // Only this doctor's completed consultations
                ->where('consultation_status', 'completed')
                ->whereDate('updated_at', today())
                ->count(),
            'avg_wait_time' => 15, // This could be calculated from actual data
        ];
        
        // Get branches for filter
        $branches = \App\Models\Branch::all();
        
        return view('consultations.doctor-queue', compact(
            'consultationQueue', 
            'currentConsultation', 
            'stats', 
            'branches', 
            'branchId'
        ));
    }
    
    /**
     * Show doctor's completed consultations
     */
    public function completedConsultations(Request $request)
    {
        $branchId = $request->get('branch_id', auth()->user()->staffProfile->branch_id ?? 1);
        $dateFilter = $request->get('date_filter', 'today'); // today, week, month, all
        
        // Build query for completed consultations
        $query = Consultation::with(['patient', 'visit', 'vitals', 'diagnoses', 'prescriptions'])
            ->where('branch_id', $branchId)
            ->where('doctor_id', auth()->id()) // Only this doctor's consultations
            ->where('consultation_status', 'completed')
            ->where('is_draft', false);
        
        // Apply date filter
        switch ($dateFilter) {
            case 'today':
                $query->whereDate('updated_at', today());
                break;
            case 'week':
                $query->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('updated_at', now()->month)
                      ->whereYear('updated_at', now()->year);
                break;
            case 'all':
                // No additional filter
                break;
        }
        
        // Get completed consultations with pagination
        $completedConsultations = $query->orderBy('updated_at', 'desc')->paginate(20);
        
        // Calculate statistics
        $stats = [
            'total_completed' => Consultation::where('branch_id', $branchId)
                ->where('doctor_id', auth()->id())
                ->where('consultation_status', 'completed')
                ->count(),
            'completed_today' => Consultation::where('branch_id', $branchId)
                ->where('doctor_id', auth()->id())
                ->where('consultation_status', 'completed')
                ->whereDate('updated_at', today())
                ->count(),
            'completed_this_week' => Consultation::where('branch_id', $branchId)
                ->where('doctor_id', auth()->id())
                ->where('consultation_status', 'completed')
                ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
            'completed_this_month' => Consultation::where('branch_id', $branchId)
                ->where('doctor_id', auth()->id())
                ->where('consultation_status', 'completed')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
        ];
        
        // Get branches for filter
        $branches = \App\Models\Branch::all();
        
        return view('consultations.completed', compact(
            'completedConsultations',
            'stats',
            'branches',
            'branchId',
            'dateFilter'
        ));
    }
    
    public function create()
    {
        $patients = Patient::latest()->get();
        
        // SECURITY: If user is a doctor, only show themselves
        if (auth()->user()->hasRole('doctor')) {
            $doctors = collect([auth()->user()]);
        } else {
            $doctors = User::role('doctor')->get();
        }
        
        $visits = Visit::where('status', 'active')
            ->whereHas('patient') // Only include visits with valid patients
            ->with('patient')
            ->get();
        $templates = \App\Models\ConsultationTemplate::active()->get();
        
        return view('consultations.create', compact('patients', 'doctors', 'visits', 'templates'));
    }

    /**
     * Create consultation for specific patient (for doctors)
     */
    public function createForPatient(Patient $patient)
    {
        // Check if user has permission to create consultations (flexible RBAC)
        if (!auth()->user()->can('create_consultations')) {
            return redirect()->back()->with('error', 'You do not have permission to create consultations.');
        }

        // SECURITY: If user is a doctor, only show themselves
        if (auth()->user()->hasRole('doctor')) {
            $doctors = collect([auth()->user()]);
        } else {
            $doctors = User::role('doctor')->get();
        }
        
        $visits = Visit::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->with('patient')
            ->get();
        $templates = \App\Models\ConsultationTemplate::active()->get();
        
        // Pre-select the current doctor
        $selectedDoctor = auth()->user();
        
        return view('consultations.create-for-patient', compact('patient', 'doctors', 'visits', 'templates', 'selectedDoctor'));
    }

    /**
     * Create consultation from queue (for doctors)
     */
    public function createFromQueue(Consultation $consultation)
    {
        $consultation->loadMissing(['visit', 'patient']);

        try {
            app(\App\Services\PaymentPolicyService::class)->assertCanProceedWithConsultation(
                $consultation->visit,
                (int) $consultation->patient_id,
                $consultation->branch_id
            );
        } catch (\App\Exceptions\PaymentGateException $e) {
            $redirect = auth()->user()->hasRole('doctor')
                ? redirect()->route('consultations.doctor-queue')
                : redirect()->route('consultations.index');

            return $redirect->with('error', $e->getMessage())
                ->with('payment_required', true)
                ->with('amount_due', $e->getAmountDue())
                ->with('cashier_url', url('/cashier'));
        }

        // Check if user has permission to create consultations (flexible RBAC)
        if (!auth()->user()->can('create_consultations')) {
            // Redirect to doctor queue instead of back to avoid potential redirect loops
            if (auth()->user()->hasRole('doctor')) {
                return redirect()->route('consultations.doctor-queue')
                    ->with('error', 'You do not have permission to create consultations.');
            }
            return redirect()->route('consultations.index')
                ->with('error', 'You do not have permission to create consultations.');
        }

        // Check if consultation is in draft status (created by reception)
        if (!$consultation->is_draft) {
            // If consultation is completed, redirect to show page
            if ($consultation->consultation_status === 'completed') {
                return redirect()->route('consultations.show', $consultation)
                    ->with('info', 'This consultation has already been completed.');
            }
            // If consultation is in progress (not draft), redirect to edit page
            return redirect()->route('consultations.edit', $consultation)
                ->with('info', 'This consultation is already in progress. You can continue editing it here.');
        }

        // Refresh consultation to get latest data
        $consultation->refresh();
        
        // Load all necessary relationships
        $consultation->load([
            'patient',
            'doctor',
            'visit',
            'vitals' => function($query) {
                $query->latest('recorded_at');
            },
            'consultationDiagnoses',
            'interventions'
        ]);

        $patient = $consultation->patient;
        
        if (!$patient) {
            // Redirect to doctor queue instead of back to avoid potential redirect loops
            if (auth()->user()->hasRole('doctor')) {
                return redirect()->route('consultations.doctor-queue')
                    ->with('error', 'Patient not found for this consultation.');
            }
            return redirect()->route('consultations.index')
                ->with('error', 'Patient not found for this consultation.');
        }
        
        // SECURITY: If user is a doctor, ensure they can only access consultations assigned to them
        if (auth()->user()->hasRole('doctor')) {
            if ($consultation->doctor_id != auth()->id()) {
                abort(403, 'You can only access consultations assigned to you.');
            }
            $doctors = collect([auth()->user()]);
        } else {
            $doctors = User::role('doctor')->get();
        }
        
        $visits = Visit::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->with('patient')
            ->get();
        $templates = \App\Models\ConsultationTemplate::active()->get();
        
        // Pre-select the current doctor and visit
        $selectedDoctor = auth()->user();
        $selectedVisit = $consultation->visit;
        
        // Get workflow instance if visit exists
        $workflowInstance = null;
        if ($selectedVisit) {
            $workflowInstance = WorkflowInstance::where('entity_id', $selectedVisit->id)
                ->where('status', 'active')
                ->whereIn('entity_type', ['visit', 'App\\Models\\Visit'])
                ->with([
                    'workflow.steps' => function($query) {
                        $query->orderBy('order');
                    },
                    'currentStep',
                    'actionLogs' => function($query) {
                        $query->latest('created_at');
                    }
                ])
                ->latest('created_at')
                ->first();
        }
        
        // Load existing prescription orders if any
        $existingPrescriptions = \App\Models\Prescription::where('consultation_id', $consultation->id)
            ->with('orders.drug')
            ->get();
        
        // Load existing lab orders if any
        $existingLabOrders = \App\Models\LabRequest::where('consultation_id', $consultation->id)
            ->with(['template', 'testType'])
            ->get();
        
        // Load existing radiology orders if any
        $existingRadiologyOrders = \App\Models\RadiologyRequest::where('consultation_id', $consultation->id)
            ->with(['modality', 'department'])
            ->get();
        
        return view('consultations.create-from-queue', compact(
            'patient', 
            'doctors', 
            'visits', 
            'templates', 
            'selectedDoctor', 
            'selectedVisit', 
            'consultation', 
            'workflowInstance',
            'existingPrescriptions',
            'existingLabOrders',
            'existingRadiologyOrders'
        ));
    }
    
    /**
     * Create consultation request (for reception/nurse)
     */
    public function createRequest()
    {
        // SECURITY: Reception/nurses can create requests for any doctor, but doctors can only for themselves
        $patients = Patient::latest()->get();
        
        if (auth()->user()->hasRole('doctor')) {
            $doctors = collect([auth()->user()]);
        } else {
            $doctors = User::role('doctor')->get();
        }
        
        $visits = Visit::where('status', 'active')->whereHas('patient')->with('patient')->get();
        
        return view('consultations.create-request', compact('patients', 'doctors', 'visits'));
    }
    
    /**
     * Store consultation request (for reception/nurse)
     */
    public function storeRequest(Request $request)
    {
        // SECURITY: If user is a doctor, force doctor_id to be their own ID
        if (auth()->user()->hasRole('doctor')) {
            $request->merge(['doctor_id' => auth()->id()]);
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'visit_id' => 'nullable|exists:visits,id',
            'consultation_date' => 'required|date',
            'consultation_type' => 'required|in:in-person,teleconsultation',
            'chief_complaint' => 'required|string|max:1000',
            'history_of_present_illness' => 'nullable|string|max:2000',
            'reception_notes' => 'nullable|string|max:1000',
            'doctor_remarks' => 'nullable|string',
            'urgency' => 'nullable|in:routine,urgent,critical',
            // Vitals validation
            'blood_pressure_systolic' => 'nullable|integer|min:50|max:300',
            'blood_pressure_diastolic' => 'nullable|integer|min:30|max:200',
            'pulse_rate' => 'nullable|integer|min:30|max:300',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'respiratory_rate' => 'nullable|integer|min:5|max:60',
            'oxygen_saturation' => 'nullable|integer|min:50|max:100',
            'height' => 'nullable|numeric|min:50|max:250',
            'weight' => 'nullable|numeric|min:10|max:300',
            'bmi' => 'nullable|numeric|min:10|max:100',
        ]);
        
        // Set default values for consultation request
        $validated['consultation_status'] = 'ongoing';
        $validated['is_draft'] = true; // This is a draft until doctor completes it
        $validated['branch_id'] = auth()->user()->staffProfile->branch_id ?? 1;
        $validated['created_by'] = auth()->id();
        $validated['urgency'] = $validated['urgency'] ?? 'routine';
        
        // Calculate BMI if height and weight are provided
        if (!empty($validated['height']) && !empty($validated['weight'])) {
            $heightInMeters = $validated['height'] / 100;
            $validated['bmi'] = round($validated['weight'] / ($heightInMeters * $heightInMeters), 2);
        }
        
        $consultation = Consultation::create($validated);
        
        // Create vitals record if any vitals data is provided AND user has permission
        if (auth()->user()->can('record_vitals')) {
            $vitalsData = [];
            $vitalsFields = [
                'blood_pressure_systolic', 'blood_pressure_diastolic', 'pulse_rate',
                'temperature', 'respiratory_rate', 'oxygen_saturation', 'height', 'weight', 'bmi'
            ];
            
            foreach ($vitalsFields as $field) {
                if (isset($validated[$field]) && $validated[$field] !== null) {
                    $vitalsData[$field] = $validated[$field];
                }
            }
            
            if (!empty($vitalsData)) {
                $vitalsData['consultation_id'] = $consultation->id;
                $vitalsData['recorded_at'] = now();
                $vitalsData['recorded_by'] = auth()->id();
                
                \App\Models\Vital::create($vitalsData);
            }
        }
        
        $message = auth()->user()->can('record_vitals') 
            ? 'Consultation request created successfully with vitals! Doctor will be notified.'
            : 'Consultation request created successfully! Doctor will be notified.';
            
        return redirect()->route('consultations.index')->with('success', $message);
    }
    
    public function store(Request $request)
    {
        try {
            // SECURITY: If user is a doctor, force doctor_id to be their own ID
            if (auth()->user()->hasRole('doctor')) {
                $request->merge(['doctor_id' => auth()->id()]);
            }

            // Filter empty order rows so validation does not require fields on blank rows
            $request->merge([
                'prescription_orders' => array_values(array_filter($request->input('prescription_orders', []), fn ($o) => !empty($o['drug_id']))),
                'lab_orders' => array_values(array_filter($request->input('lab_orders', []), fn ($o) => !empty($o['test_type_id']))),
                'radiology_orders' => array_values(array_filter($request->input('radiology_orders', []), fn ($o) => !empty($o['modality_id']) && !empty($o['department_id']))),
            ]);

            $isDraft = $request->boolean('is_draft', false);

            $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'visit_id' => 'nullable|exists:visits,id',
            'consultation_date' => 'required|date',
            'consultation_time' => 'nullable|date_format:H:i',
            'consultation_type' => 'required|in:in-person,teleconsultation',
            'chief_complaint' => ($isDraft ? 'nullable' : 'required') . '|string',
            'urgency' => 'nullable|in:routine,urgent,critical',
            'history_of_present_illness' => 'nullable|string',
            'on_direct_questioning' => 'nullable|string',
            'past_medical_history' => 'nullable|string',
            'family_history' => 'nullable|string',
            'social_history' => 'nullable|string',
            'drug_history' => 'nullable|string',
            'allergy_history' => 'nullable|string',
            'past_medical_history_others' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'drug_allergies' => 'nullable|string',
            'past_drug_usage' => 'nullable|string',
            'social_history_details' => 'nullable|string',
            'general_examination' => 'nullable|string',
            'cardiovascular_examination' => 'nullable|string',
            'respiratory_examination' => 'nullable|string',
            'abdominal_examination' => 'nullable|string',
            'neurological_examination' => 'nullable|string',
            'blood_pressure_systolic' => 'nullable|numeric|min:50|max:300',
            'blood_pressure_diastolic' => 'nullable|numeric|min:30|max:200',
            'pulse_rate' => 'nullable|integer|min:30|max:300',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'respiratory_rate' => 'nullable|integer|min:5|max:60',
            'oxygen_saturation' => 'nullable|integer|min:50|max:100',
            'height' => 'nullable|numeric|min:50|max:250',
            'weight' => 'nullable|numeric|min:10|max:300',
            'bmi' => 'nullable|numeric|min:10|max:100',
            'doctors_impression' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'icd_10_code' => 'nullable|string|max:10',
            'clinical_notes' => 'nullable|string',
            'follow_up_instructions' => 'nullable|string',
            'is_draft' => 'nullable|boolean',
            'next_stage' => 'nullable|string',
            // Prescription orders validation
            'prescription_orders' => 'nullable|array',
            'prescription_orders.*.drug_id' => 'required_with:prescription_orders|exists:drugs,id',
            'prescription_orders.*.quantity' => 'required_with:prescription_orders|integer|min:1',
            'prescription_orders.*.dosage_instructions' => 'required_with:prescription_orders|string|max:500',
            'prescription_orders.*.duration' => 'nullable|string|max:100',
            // Lab orders validation
            'lab_orders' => 'nullable|array',
            'lab_orders.*.category_id' => 'nullable|exists:lab_test_categories,id',
            'lab_orders.*.test_type_id' => 'required_with:lab_orders|exists:lab_test_types,id',
            'lab_orders.*.priority' => 'nullable|in:routine,urgent,stat',
            'lab_orders.*.specimen_type' => 'nullable|string|max:100',
            // Radiology orders validation
            'radiology_orders' => 'nullable|array',
            'radiology_orders.*.modality_id' => 'required_with:radiology_orders|exists:imaging_modalities,id',
            'radiology_orders.*.department_id' => 'required_with:radiology_orders|exists:radiology_departments,id',
            'radiology_orders.*.priority' => 'nullable|in:routine,urgent,stat',
            'radiology_orders.*.clinical_history' => 'nullable|string|max:1000',
            'radiology_orders.*.clinical_question' => 'nullable|string|max:500',
            'radiology_orders.*.indication' => 'nullable|string|max:500',
            'radiology_orders.*.scheduled_date' => 'nullable|date',
            'radiology_orders.*.scheduled_time' => 'nullable|date_format:H:i',
            ]);
            
            // Process presenting complaints
            if ($request->has('complaints')) {
                $complaints = [];
                foreach ($request->complaints as $complaint) {
                    if (!empty($complaint['text'])) {
                        $complaints[] = [
                            'text' => $complaint['text'],
                            'duration' => $complaint['duration'] ?? null
                        ];
                    }
                }
                $validated['presenting_complaints'] = $complaints;
            }
            
            // Process past medical history details
            if ($request->has('past_medical_history_details')) {
                $validated['past_medical_history_details'] = $request->past_medical_history_details;
            }
            
            $validated['consultation_status'] = $isDraft ? 'ongoing' : 'completed';
            $validated['is_draft'] = $isDraft;
            $validated['branch_id'] = auth()->user()->staffProfile->branch_id ?? 1;
            $validated['created_by'] = auth()->id();
            $validated['urgency'] = $validated['urgency'] ?? 'routine';
            $validated['consultation_time'] = $validated['consultation_time'] ?? now()->format('H:i');
            $validated['started_at'] = now();
            
            if (!$isDraft) {
                $validated['completed_at'] = now();
            }
            
            // Calculate BMI if height and weight are provided
            if (!empty($validated['height']) && !empty($validated['weight'])) {
                $heightInMeters = $validated['height'] / 100;
                $validated['bmi'] = round($validated['weight'] / ($heightInMeters * $heightInMeters), 2);
            }
            
            $consultation = Consultation::create($validated);

            if (!$isDraft) {
                $this->syncDiagnosisRecords($consultation, $request);
            }
            
            // Process prescription orders (save on draft and complete so data is never lost)
            $hasPrescriptions = false;
            if ($request->has('prescription_orders') && is_array($request->prescription_orders) && count($request->prescription_orders) > 0) {
                if ($isDraft) {
                    $this->updateOrCreatePrescriptionOrders($consultation, $request->prescription_orders);
                } else {
                    $this->createPrescriptionOrders($consultation, $request->prescription_orders);
                }
                $hasPrescriptions = true;
            }
            
            // Process lab orders (save on draft and complete)
            $hasLabOrders = false;
            if ($request->has('lab_orders') && is_array($request->lab_orders) && count($request->lab_orders) > 0) {
                if ($isDraft) {
                    $this->updateOrCreateLabOrders($consultation, $request->lab_orders);
                } else {
                    $this->createLabOrders($consultation, $request->lab_orders);
                }
                $hasLabOrders = true;
            }
            
            // Process radiology orders (queues created only on completion)
            $hasRadiologyOrders = false;
            if ($request->has('radiology_orders') && is_array($request->radiology_orders) && count($request->radiology_orders) > 0) {
                if ($isDraft) {
                    $this->updateOrCreateRadiologyOrders($consultation, $request->radiology_orders);
                } else {
                    $this->createRadiologyOrders($consultation, $request->radiology_orders);
                }
                $hasRadiologyOrders = true;
            }
            
            // Determine next stage and create appropriate workflow
            if (!$isDraft && $request->has('next_stage')) {
                $this->createWorkflowSteps($consultation, $request->next_stage);
            }
            
            // Complete workflow step if visit has workflow
            if (!$isDraft && $consultation->visit) {
                $metadata = [];
                if ($hasPrescriptions) {
                    $metadata['prescription_ordered'] = true;
                }
                if ($hasLabOrders) {
                    $metadata['lab_ordered'] = true;
                }
                if ($hasRadiologyOrders) {
                    $metadata['imaging_ordered'] = true;
                }
                
                $this->completeWorkflowStep($consultation->visit, 'consultation', $metadata);
            }
            
            $message = $isDraft ? 'Consultation saved as draft successfully!' : 'Consultation completed successfully!';
            
            // Use workflow navigation if available
            if (!$isDraft && $consultation->visit) {
                // Get workflow instance - it should have moved to next step after completion
                $instance = $this->getWorkflowInstance($consultation->visit);
                if ($instance) {
                    // Refresh to get latest state after step completion
                    $instance->refresh();
                    
                    $navigationService = app(\App\Services\WorkflowNavigationService::class);
                    // Get current step suggestion (which is the step we moved to after consultation)
                    $suggestion = $navigationService->getCurrentStepSuggestion($instance->id, auth()->id());
                    
                    // Workflow suggestion removed - no auto-redirect on consultation show page
                }
            }
            
            return redirect()->route('consultations.show', $consultation)
                ->with('success', $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Error storing consultation: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', '_token'])
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating consultation: ' . $e->getMessage());
        }
    }
    
    public function show(Consultation $consultation)
    {
        $this->assertPortalPatientOwns($consultation->patient_id);

        // SECURITY: If user is a doctor, ensure they can only view their own consultations
        if (auth()->user()->hasRole('doctor') && $consultation->doctor_id != auth()->id()) {
            abort(403, 'You can only view your own consultations.');
        }

        // Eager-load all relationships in one batch (avoids N+1; vitals = nurse-recorded at check-in)
        $consultation->load([
            'patient',
            'doctor',
            'amendedBy',
            'visit',
            'consultationDiagnoses',
            'vitals' => function ($query) {
                $query->with('recordedBy')->latest('recorded_at');
            },
            'interventions',
            'prescriptions.orders.drug',
            'labRequests' => function ($query) {
                $query->with([
                    'results' => fn ($q) => $q->orderBy('parameter_name'),
                    'testType',
                    'template',
                ])->latest('updated_at');
            },
        ]);

        $this->attachConsultationLabRequests($consultation);
        
        return view('consultations.show', compact('consultation'));
    }
    
    public function edit(Consultation $consultation)
    {
        // SECURITY: If user is a doctor, ensure they can only edit their own consultations
        if (auth()->user()->hasRole('doctor') && $consultation->doctor_id != auth()->id()) {
            abort(403, 'You can only edit your own consultations.');
        }

        $patient = $consultation->patient;
        
        // SECURITY: If user is a doctor, only show themselves
        if (auth()->user()->hasRole('doctor')) {
            $doctors = collect([auth()->user()]);
        } else {
            $doctors = User::role('doctor')->get();
        }
        $visits = Visit::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->with('patient')
            ->get();
        $templates = \App\Models\ConsultationTemplate::active()->get();
        
        // Pre-select the doctor who created/is handling the consultation
        $selectedDoctor = $consultation->doctor ?? auth()->user();
        $selectedVisit = $consultation->visit;
        
        // Load existing prescription orders with drug orders
        $existingPrescriptions = \App\Models\Prescription::where('consultation_id', $consultation->id)
            ->with('orders.drug')
            ->get();
        
        // Load existing lab orders
        $existingLabOrders = \App\Models\LabRequest::where('consultation_id', $consultation->id)
            ->with(['template', 'testType'])
            ->get();
        
        // Load existing radiology orders
        $existingRadiologyOrders = \App\Models\RadiologyRequest::where('consultation_id', $consultation->id)
            ->with(['modality', 'department'])
            ->get();
        
        return view('consultations.edit', compact(
            'patient', 
            'doctors', 
            'visits', 
            'templates', 
            'selectedDoctor', 
            'selectedVisit', 
            'consultation',
            'existingPrescriptions',
            'existingLabOrders',
            'existingRadiologyOrders'
        ));
    }
    
    public function update(Request $request, Consultation $consultation)
    {
        // SECURITY: If user is a doctor, ensure they can only update their own consultations
        if (auth()->user()->hasRole('doctor') && $consultation->doctor_id != auth()->id()) {
            abort(403, 'You can only update your own consultations.');
        }

        try {
            // Filter empty order rows so validation does not require fields on empty rows (draft save with blank rows)
            $request->merge([
                'prescription_orders' => array_values(array_filter($request->input('prescription_orders', []), fn($o) => !empty($o['drug_id']))),
                'lab_orders' => array_values(array_filter($request->input('lab_orders', []), fn($o) => !empty($o['test_type_id']))),
                'radiology_orders' => array_values(array_filter($request->input('radiology_orders', []), fn($o) => !empty($o['modality_id']) && !empty($o['department_id']))),
            ]);

            $validated = $request->validate([
            'chief_complaint' => 'required|string',
            'consultation_status' => 'nullable|in:ongoing,completed,cancelled',
            'history_of_present_illness' => 'nullable|string',
            'on_direct_questioning' => 'nullable|string',
            'past_medical_history' => 'nullable|string',
            'family_history' => 'nullable|string',
            'social_history' => 'nullable|string',
            'drug_history' => 'nullable|string',
            'allergy_history' => 'nullable|string',
            'past_medical_history_others' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'drug_allergies' => 'nullable|string',
            'past_drug_usage' => 'nullable|string',
            'social_history_details' => 'nullable|string',
            'general_examination' => 'nullable|string',
            'cardiovascular_examination' => 'nullable|string',
            'respiratory_examination' => 'nullable|string',
            'abdominal_examination' => 'nullable|string',
            'neurological_examination' => 'nullable|string',
            'blood_pressure_systolic' => 'nullable|numeric|min:50|max:300',
            'blood_pressure_diastolic' => 'nullable|numeric|min:30|max:200',
            'pulse_rate' => 'nullable|integer|min:30|max:300',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'respiratory_rate' => 'nullable|integer|min:5|max:60',
            'oxygen_saturation' => 'nullable|integer|min:50|max:100',
            'height' => 'nullable|numeric|min:50|max:250',
            'weight' => 'nullable|numeric|min:10|max:300',
            'bmi' => 'nullable|numeric|min:10|max:100',
            'doctors_impression' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'physical_examination' => 'nullable|string',
            'icd_10_code' => 'nullable|string|max:10',
            'medications_prescribed' => 'nullable|string',
            'investigations_ordered' => 'nullable|string',
            'follow_up_instructions' => 'nullable|string',
            'is_draft' => 'nullable|boolean',
            'next_stage' => 'nullable|string',
            'urgency' => 'nullable|in:routine,urgent,critical',
            'consultation_time' => 'nullable|date_format:H:i',
            'completion_notes' => 'nullable|string|max:2000',
            'completion_type' => 'nullable|string|max:50',
            'amendment_notes' => 'nullable|string|max:2000',
            // Prescription orders validation
            'prescription_orders' => 'nullable|array',
            'prescription_orders.*.drug_id' => 'required_with:prescription_orders|exists:drugs,id',
            'prescription_orders.*.quantity' => 'required_with:prescription_orders|integer|min:1',
            'prescription_orders.*.dosage_instructions' => 'required_with:prescription_orders|string|max:500',
            'prescription_orders.*.duration' => 'nullable|string|max:100',
            // Lab orders validation
            'lab_orders' => 'nullable|array',
            'lab_orders.*.category_id' => 'nullable|exists:lab_test_categories,id',
            'lab_orders.*.test_type_id' => 'required_with:lab_orders|exists:lab_test_types,id',
            'lab_orders.*.priority' => 'nullable|in:routine,urgent,stat',
            'lab_orders.*.specimen_type' => 'nullable|string|max:100',
            // Radiology orders validation
            'radiology_orders' => 'nullable|array',
            'radiology_orders.*.modality_id' => 'required_with:radiology_orders|exists:imaging_modalities,id',
            'radiology_orders.*.department_id' => 'required_with:radiology_orders|exists:radiology_departments,id',
            'radiology_orders.*.priority' => 'nullable|in:routine,urgent,stat',
            'radiology_orders.*.clinical_history' => 'nullable|string|max:1000',
            'radiology_orders.*.clinical_question' => 'nullable|string|max:500',
            'radiology_orders.*.indication' => 'nullable|string|max:500',
            'radiology_orders.*.scheduled_date' => 'nullable|date',
            'radiology_orders.*.scheduled_time' => 'nullable|date_format:H:i',
            ]);
            
            // Process presenting complaints if provided
            if ($request->has('complaints')) {
                $complaints = [];
                foreach ($request->complaints as $complaint) {
                    if (!empty($complaint['text'])) {
                        $complaints[] = [
                            'text' => $complaint['text'],
                            'duration' => $complaint['duration'] ?? null
                        ];
                    }
                }
                $validated['presenting_complaints'] = $complaints;
            }
            
            // Process past medical history details if provided
            if ($request->has('past_medical_history_details')) {
                $validated['past_medical_history_details'] = $request->past_medical_history_details;
            }
            
            $wasCompleted = $consultation->isCompleted();
            $isCompleting = $request->has('consultation_status') && $request->consultation_status === 'completed';

            // Handle consultation completion
            if ($isCompleting) {
                $validated['consultation_status'] = 'completed';
                $validated['is_draft'] = false;
                $validated['completed_at'] = $consultation->completed_at ?? now();
                
                // Store completion notes if provided
                if ($request->has('completion_notes') && $request->completion_notes) {
                    $validated['completion_notes'] = $request->completion_notes;
                }
                
                // Store completion type for tracking
                if ($request->has('completion_type')) {
                    $validated['completion_type'] = $request->completion_type;
                }
            } elseif ($request->has('is_draft')) {
                // Never revert a completed consultation to draft
                if (!$wasCompleted) {
                    $validated['is_draft'] = $request->boolean('is_draft');
                }
            }

            // Track amendments to completed consultations without changing status or billing
            if ($wasCompleted && !$isCompleting) {
                $validated['consultation_status'] = 'completed';
                $validated['is_draft'] = false;
                $validated['amended_at'] = now();
                $validated['amended_by'] = auth()->id();
                if ($request->filled('amendment_notes')) {
                    $validated['amendment_notes'] = $request->amendment_notes;
                }
            }
            
            // If this was a draft consultation being completed by doctor
            if ($consultation->is_draft && $request->has('consultation_status') && $request->consultation_status === 'completed') {
                $validated['doctor_id'] = auth()->id(); // Assign to current doctor
            }
            
            $validated['updated_by'] = auth()->id();
            
            // Calculate BMI if height and weight are provided
            if (isset($validated['height']) && isset($validated['weight']) && $validated['height'] && $validated['weight']) {
                $heightInMeters = $validated['height'] / 100;
                $validated['bmi'] = round($validated['weight'] / ($heightInMeters * $heightInMeters), 2);
            }
            
            $consultation->update($validated);
            
            // Refresh consultation to get latest status after update
            $consultation->refresh();
            
            // Determine if this is a draft save or completion
            $isDraft = $wasCompleted ? false : ($request->has('is_draft') ? $request->boolean('is_draft') : ($consultation->is_draft ?? false));

            if ($isCompleting || ($wasCompleted && !$isCompleting)) {
                $this->syncDiagnosisRecords($consultation, $request);
            }
            
            // Process prescription orders (save on both draft and complete so data is never lost)
            $hasPrescriptions = false;
            if ($request->has('prescription_orders') && is_array($request->prescription_orders) && count($request->prescription_orders) > 0) {
                $this->updateOrCreatePrescriptionOrders($consultation, $request->prescription_orders);
                $hasPrescriptions = true;
            }
            
            // Process lab orders (save on both draft and complete)
            $hasLabOrders = false;
            if ($request->has('lab_orders') && is_array($request->lab_orders) && count($request->lab_orders) > 0) {
                $this->updateOrCreateLabOrders($consultation, $request->lab_orders);
                $hasLabOrders = true;
            }
            
            // Process radiology orders (save on both draft and complete)
            $hasRadiologyOrders = false;
            if ($request->has('radiology_orders') && is_array($request->radiology_orders) && count($request->radiology_orders) > 0) {
                $this->updateOrCreateRadiologyOrders($consultation, $request->radiology_orders);
                $hasRadiologyOrders = true;
            }
            
            // On completion only: ensure radiology queue exists for this consultation's visit
            if ($isCompleting && $hasRadiologyOrders) {
                $this->ensureRadiologyQueueForConsultation($consultation);
            }
            
            // Determine next stage and create appropriate workflow
            if (!$isDraft && $isCompleting && $request->has('next_stage')) {
                $this->createWorkflowSteps($consultation, $request->next_stage);
            }
            
            // Complete workflow step if visit has workflow and consultation is completed
            if (!$isDraft && $isCompleting && $consultation->visit) {
                $metadata = [];
                if ($hasPrescriptions) {
                    $metadata['prescription_ordered'] = true;
                }
                if ($hasLabOrders) {
                    $metadata['lab_ordered'] = true;
                }
                if ($hasRadiologyOrders) {
                    $metadata['imaging_ordered'] = true;
                }
                
                $this->completeWorkflowStep($consultation->visit, 'consultation', $metadata);
            }
            
            $message = $wasCompleted && !$isCompleting
                ? 'Consultation amended successfully!'
                : 'Consultation updated successfully!';
            if ($isCompleting && !$wasCompleted) {
                $message = 'Consultation completed successfully!';
                
                // If doctor completed consultation from queue, redirect back to doctor queue
                // Check if request came from doctor queue (via form input, referrer, or session)
                $fromQueue = $request->input('from_queue') == '1' || 
                            $request->input('from_queue') == 1 ||
                            session('from_doctor_queue') || 
                            str_contains($request->header('referer', ''), 'create-from-queue') ||
                            str_contains($request->header('referer', ''), 'doctor-queue');
                
                if ($fromQueue && auth()->user()->hasRole('doctor')) {
                    // Clear the session flag
                    session()->forget('from_doctor_queue');
                    
                    // Redirect back to doctor queue (this is the final destination, no further redirects)
                    return redirect()->route('consultations.doctor-queue')
                        ->with('success', $message);
                }
            }
            
            // For updates that don't complete consultation, stay on show page
            // But if coming from queue and user is doctor, redirect to queue instead
            if ($request->input('from_queue') == '1' || $request->input('from_queue') == 1) {
                if (auth()->user()->hasRole('doctor')) {
                    return redirect()->route('consultations.doctor-queue')
                        ->with('success', $message);
                }
            }
            
            return redirect()->route('consultations.show', $consultation)
                ->with('success', $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // If coming from queue, redirect to edit page instead of back to avoid redirect loop
            if ($request->input('from_queue') == '1' || $request->input('from_queue') == 1) {
                return redirect()->route('consultations.edit', $consultation)
                    ->withErrors($e->errors())
                    ->withInput();
            }
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Error updating consultation: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'consultation_id' => $consultation->id,
                'request_data' => $request->except(['password', '_token'])
            ]);
            
            // If coming from queue, redirect to edit page instead of back to avoid redirect loop
            if ($request->input('from_queue') == '1' || $request->input('from_queue') == 1) {
                return redirect()->route('consultations.edit', $consultation)
                    ->withInput()
                    ->with('error', 'Error updating consultation: ' . $e->getMessage());
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating consultation: ' . $e->getMessage());
        }
    }
    
    public function destroy(Consultation $consultation)
    {
        // SECURITY: If user is a doctor, ensure they can only delete their own consultations
        if (auth()->user()->hasRole('doctor') && $consultation->doctor_id != auth()->id()) {
            abort(403, 'You can only delete your own consultations.');
        }

        try {
            // Prevent deletion of consultations with associated records
            $hasPrescriptions = $consultation->prescriptions()->count() > 0;
            $hasLabRequests = $consultation->labRequests()->count() > 0;
            $hasRadiologyRequests = $consultation->radiologyRequests()->count() > 0;
            
            if ($hasPrescriptions || $hasLabRequests || $hasRadiologyRequests) {
                return back()
                    ->with('error', 'Cannot delete consultation with associated prescriptions, lab requests, or radiology requests.');
            }
            
            $consultation->delete();
            
            return redirect()->route('consultations.index')
                ->with('success', 'Consultation deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting consultation: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'consultation_id' => $consultation->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete consultation. Please try again.');
        }
    }
    
    /**
     * Bulk delete consultations
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:consultations,id',
        ]);
        
        $deletedCount = 0;
        $skippedCount = 0;
        $errors = [];
        
        foreach ($validated['ids'] as $id) {
            $consultation = Consultation::find($id);
            
            if (!$consultation) {
                $skippedCount++;
                continue;
            }
            
            // SECURITY: If user is a doctor, ensure they can only delete their own consultations
            if (auth()->user()->hasRole('doctor') && $consultation->doctor_id != auth()->id()) {
                $skippedCount++;
                $errors[] = "Consultation {$consultation->consultation_number} cannot be deleted - not assigned to you.";
                continue;
            }
            
            // Check if consultation has associated records
            $hasPrescriptions = $consultation->prescriptions()->count() > 0;
            $hasLabRequests = $consultation->labRequests()->count() > 0;
            $hasRadiologyRequests = $consultation->radiologyRequests()->count() > 0;
            
            if ($hasPrescriptions || $hasLabRequests || $hasRadiologyRequests) {
                $skippedCount++;
                $errors[] = "Consultation {$consultation->consultation_number} has associated records and cannot be deleted.";
                continue;
            }
            
            try {
                $consultationNumber = $consultation->consultation_number ?? $consultation->id;
                $consultation->delete();
                $deletedCount++;
                
                Log::info('Consultation deleted (bulk)', [
                    'consultation_id' => $id,
                    'consultation_number' => $consultationNumber,
                    'deleted_by' => auth()->id(),
                    'deleted_at' => now(),
                ]);
            } catch (\Exception $e) {
                $skippedCount++;
                $errors[] = "Failed to delete consultation {$consultation->consultation_number}: " . $e->getMessage();
                Log::error('Error deleting consultation (bulk): ' . $e->getMessage(), [
                    'user_id' => auth()->id(),
                    'consultation_id' => $id,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        // Build response message
        $message = "Successfully deleted {$deletedCount} consultation(s).";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} consultation(s) were skipped.";
            if (!empty($errors)) {
                $message .= " " . implode(' ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $message .= " (and " . (count($errors) - 3) . " more)";
                }
            }
        }
        
        if ($deletedCount === 0) {
            return redirect()->route('consultations.index')
                ->with('error', $message);
        }
        
        return redirect()->route('consultations.index')
            ->with('success', $message);
    }
    
    /**
     * Mark consultation as no-show
     */
    public function markNoShow(Consultation $consultation)
    {
        try {
            $consultation->update([
                'consultation_status' => 'cancelled',
                'is_draft' => false,
                'updated_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancellation_reason' => 'No Show'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Consultation marked as no-show successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking consultation as no-show: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'consultation_id' => $consultation->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark consultation as no-show. Please try again.'
            ], 500);
        }
    }

    /**
     * Update existing or create new prescription orders
     */
    private function updateOrCreatePrescriptionOrders(Consultation $consultation, array $prescriptionOrders)
    {
        if (empty($prescriptionOrders)) {
            return;
        }

        foreach ($prescriptionOrders as $orderData) {
            if (empty($orderData['drug_id'])) {
                continue; // Skip empty orders
            }

            // Check if this is an existing prescription being updated
            if (!empty($orderData['prescription_id'])) {
                $prescription = \App\Models\Prescription::find($orderData['prescription_id']);
                
                if ($prescription) {
                    // Update existing prescription
                    $prescription->update([
                        'quantity' => $orderData['quantity'],
                        'dosage_instructions' => $orderData['dosage_instructions'],
                        'duration' => $orderData['duration'] ?? $prescription->duration,
                        'updated_by' => auth()->id()
                    ]);
                    
                    // Update the drug order if it exists
                    $drugOrder = \App\Models\DrugOrder::where('prescription_id', $prescription->id)
                        ->where('drug_id', $orderData['drug_id'])
                        ->first();
                    
                    if ($drugOrder) {
                        $drugOrder->update([
                            'quantity' => $orderData['quantity'],
                            'dosage_instructions' => $orderData['dosage_instructions'],
                            'instructions' => $orderData['dosage_instructions'],
                            'duration' => $orderData['duration'] ?? 'Until finished',
                        ]);
                    }
                }
            } else {
                // Create new prescription order
                $this->createSinglePrescriptionOrder($consultation, $orderData);
            }
        }
    }
    
    /**
     * Create a single prescription order
     */
    private function createSinglePrescriptionOrder(Consultation $consultation, array $orderData)
    {
        // Check stock availability
        $stockAvailable = $this->checkStockAvailability($orderData['drug_id'], $orderData['quantity'], $consultation->branch_id);
        
        // Create prescription
        $prescription = \App\Models\Prescription::create([
            'patient_id' => $consultation->patient_id,
            'consultation_id' => $consultation->id,
            'doctor_id' => $consultation->doctor_id,
            'branch_id' => $consultation->branch_id,
            'prescription_date' => $consultation->consultation_date,
            'drug_id' => $orderData['drug_id'],
            'quantity' => $orderData['quantity'],
            'dosage_instructions' => $orderData['dosage_instructions'],
            'duration' => $orderData['duration'] ?? 'Until finished',
            'status' => 'active',
            'notes' => 'Generated from consultation',
            'created_by' => auth()->id()
        ]);

        // Create drug order
        \App\Models\DrugOrder::create([
            'prescription_id' => $prescription->id,
            'drug_id' => $orderData['drug_id'],
            'quantity' => $orderData['quantity'],
            'dosage_instructions' => $orderData['dosage_instructions'],
            'instructions' => $orderData['dosage_instructions'],
            'frequency' => $orderData['frequency'] ?? 'As prescribed',
            'duration' => $orderData['duration'] ?? 'Until finished',
            'status' => 'pending'
        ]);

        // Create consultation intervention
        \App\Models\ConsultationIntervention::create([
            'consultation_id' => $consultation->id,
            'intervention_type' => 'medication',
            'description' => 'Prescription: ' . \App\Models\Drug::find($orderData['drug_id'])->name,
            'medication_id' => $orderData['drug_id'],
            'dosage_instructions' => $orderData['dosage_instructions'],
            'frequency' => $orderData['frequency'] ?? 'As prescribed',
            'duration' => $orderData['duration'] ?? 'Until finished',
            'priority' => $orderData['priority'] ?? 'routine',
            'status' => 'ordered',
            'ordered_by' => auth()->id(),
            'ordered_at' => now()
        ]);
    }
    
    /**
     * Update existing or create new lab orders
     */
    private function updateOrCreateLabOrders(Consultation $consultation, array $labOrders)
    {
        if (empty($labOrders)) {
            return;
        }

        foreach ($labOrders as $orderData) {
            if (empty($orderData['test_type_id'])) {
                continue; // Skip empty orders
            }

            // Check if this is an existing lab order being updated
            if (!empty($orderData['lab_request_id'])) {
                $labRequest = \App\Models\LabRequest::find($orderData['lab_request_id']);
                
                if ($labRequest) {
                    // Update existing lab request
                    $testType = \App\Models\LabTestType::find($orderData['test_type_id']);
                    
                    $updateData = [
                        'test_type_id' => $orderData['test_type_id'],
                        'test_type' => $testType->test_name,
                        'test_description' => $testType->test_name . ' (' . $testType->test_code . ')',
                        'priority' => $orderData['priority'] ?? $labRequest->priority,
                        'specimen_type' => $orderData['specimen_type'] ?? $testType->specimen_type,
                        'updated_by' => auth()->id()
                    ];
                    
                    $templateId = $testType ? $testType->getResolvedTemplateId() : null;

                    if ($templateId) {
                        $updateData['template_id'] = $templateId;
                    }
                    
                    $labRequest->update($updateData);
                    
                    if ($templateId) {
                        $labRequest->addTemplates([$templateId]);
                    }
                }
            } else {
                // Create new lab order
                $this->createSingleLabOrder($consultation, $orderData);
            }
        }
    }
    
    /**
     * Create a single lab order
     */
    private function createSingleLabOrder(Consultation $consultation, array $orderData)
    {
        $testType = \App\Models\LabTestType::find($orderData['test_type_id']);
        
        if ($testType) {
            $templateId = $testType->getResolvedTemplateId();

            $labRequest = \App\Models\LabRequest::create([
                'patient_id' => $consultation->patient_id,
                'consultation_id' => $consultation->id,
                'doctor_id' => $consultation->doctor_id,
                'branch_id' => $consultation->branch_id,
                'test_type_id' => $testType->id,
                'template_id' => $templateId,
                'test_type' => $testType->test_name,
                'test_description' => $testType->test_name . ' (' . $testType->test_code . ')',
                'clinical_notes' => $consultation->doctors_impression,
                'priority' => $orderData['priority'] ?? 'routine',
                'specimen_type' => $orderData['specimen_type'] ?? $testType->specimen_type,
                'collection_instructions' => $testType->collection_instructions,
                'special_instructions' => $testType->preparation_instructions,
                'status' => 'pending',
                'created_by' => auth()->id()
            ]);
            
            if ($templateId) {
                $labRequest->addTemplates([$templateId]);
            }
        }
    }
    
    /**
     * Create prescription orders from consultation
     */
    private function createPrescriptionOrders(Consultation $consultation, array $prescriptionOrders)
    {
        if (empty($prescriptionOrders)) {
            return;
        }

        // Check for drug interactions before creating prescription
        $this->checkDrugInteractions($prescriptionOrders);

        // Create prescription
        $prescription = \App\Models\Prescription::create([
            'patient_id' => $consultation->patient_id,
            'consultation_id' => $consultation->id,
            'doctor_id' => $consultation->doctor_id,
            'branch_id' => $consultation->branch_id,
            'prescription_date' => $consultation->consultation_date,
            'status' => 'active',
            'notes' => 'Generated from consultation',
            'created_by' => auth()->id()
        ]);

        // Create drug orders and check stock availability
        foreach ($prescriptionOrders as $orderData) {
            if (!empty($orderData['drug_id'])) {
                // Check stock availability
                $stockAvailable = $this->checkStockAvailability($orderData['drug_id'], $orderData['quantity'], $consultation->branch_id);
                
                $drugOrder = \App\Models\DrugOrder::create([
                    'prescription_id' => $prescription->id,
                    'drug_id' => $orderData['drug_id'],
                    'quantity' => $orderData['quantity'],
                    'dosage_instructions' => $orderData['dosage_instructions'],
                    'instructions' => $orderData['dosage_instructions'], // Required field
                    'frequency' => $orderData['frequency'] ?? 'As prescribed',
                    'duration' => $orderData['duration'] ?? 'Until finished',
                    'status' => 'pending'
                ]);

                // Create consultation intervention for tracking
                \App\Models\ConsultationIntervention::create([
                    'consultation_id' => $consultation->id,
                    'intervention_type' => 'medication',
                    'description' => 'Prescription: ' . \App\Models\Drug::find($orderData['drug_id'])->name,
                    'medication_id' => $orderData['drug_id'],
                    'dosage_instructions' => $orderData['dosage_instructions'],
                    'frequency' => $orderData['frequency'] ?? 'As prescribed',
                    'duration' => $orderData['duration'] ?? 'Until finished',
                    'priority' => $orderData['priority'] ?? 'routine',
                    'status' => 'ordered',
                    'ordered_by' => auth()->id(),
                    'ordered_at' => now()
                ]);
            }
        }

        // Create notification for patient about new prescription
        \App\Models\PrescriptionNotification::create([
            'prescription_id' => $prescription->id,
            'patient_id' => $consultation->patient_id,
            'doctor_id' => $consultation->doctor_id,
            'notification_type' => 'prescription_ready',
            'title' => 'New Prescription Ready',
            'message' => 'Your prescription has been created and is ready for dispensing at the pharmacy.',
            'priority' => 'medium',
            'status' => 'pending'
        ]);

        // Create pharmacy visit if not exists
        $existingVisit = \App\Models\Visit::where('patient_id', $consultation->patient_id)
            ->where('visit_type', 'PharmacyOnly')
            ->where('status', 'active')
            ->first();

        if (!$existingVisit) {
            $pharmacyVisit = \App\Models\Visit::create([
                'patient_id' => $consultation->patient_id,
                'visit_token' => 'PHARM-' . strtoupper(uniqid()),
                'visit_type' => 'PharmacyOnly',
                'status' => 'active',
                'branch_id' => $consultation->branch_id,
                'created_by' => auth()->id()
            ]);

            // Add to pharmacy queue
            $nextPosition = \App\Models\Queue::where('queue_type', 'Pharmacy')
                ->where('branch_id', $consultation->branch_id)
                ->whereDate('queued_at', today())
                ->where('status', '!=', 'cancelled')
                ->max('position') + 1;
            
            \App\Models\Queue::create([
                'visit_id' => $pharmacyVisit->id,
                'patient_id' => $consultation->patient_id,
                'branch_id' => $consultation->branch_id,
                'queue_type' => 'Pharmacy',
                'position' => $nextPosition,
                'status' => 'waiting',
                'priority' => 'routine',
                'queued_at' => now()
            ]);
        }
    }

    /**
     * Create lab orders from consultation
     */
    private function createLabOrders(Consultation $consultation, array $labOrders)
    {
        if (empty($labOrders)) {
            return;
        }

        foreach ($labOrders as $orderData) {
            if (!empty($orderData['test_type_id'])) {
                // Get the lab test type details
                $testType = \App\Models\LabTestType::find($orderData['test_type_id']);
                
                if ($testType) {
                    $templateId = $testType->getResolvedTemplateId();

                    $labRequest = \App\Models\LabRequest::create([
                        'patient_id' => $consultation->patient_id,
                        'consultation_id' => $consultation->id,
                        'doctor_id' => $consultation->doctor_id,
                        'branch_id' => $consultation->branch_id,
                        'test_type_id' => $testType->id,
                        'template_id' => $templateId,
                        'test_type' => $testType->test_name,
                        'test_description' => $testType->test_name . ' (' . $testType->test_code . ')',
                        'clinical_notes' => $consultation->doctors_impression,
                        'priority' => $orderData['priority'] ?? 'routine',
                        'specimen_type' => $orderData['specimen_type'] ?? $testType->specimen_type,
                        'collection_instructions' => $testType->collection_instructions,
                        'special_instructions' => $testType->preparation_instructions,
                        'status' => 'pending',
                        'created_by' => auth()->id()
                    ]);
                    
                    if ($templateId) {
                        $labRequest->addTemplates([$templateId]);
                    }
                }
            }
        }

        // Create lab visit if not exists
        $existingVisit = \App\Models\Visit::where('patient_id', $consultation->patient_id)
            ->where('visit_type', 'LabOnly')
            ->where('status', 'active')
            ->first();

        if (!$existingVisit) {
            $labVisit = \App\Models\Visit::create([
                'patient_id' => $consultation->patient_id,
                'visit_token' => 'LAB-' . strtoupper(uniqid()),
                'visit_type' => 'LabOnly',
                'status' => 'active',
                'branch_id' => $consultation->branch_id,
                'created_by' => auth()->id()
            ]);

            // Add to lab queue
            $nextPosition = \App\Models\Queue::where('queue_type', 'Lab')
                ->where('branch_id', $consultation->branch_id)
                ->whereDate('queued_at', today())
                ->where('status', '!=', 'cancelled')
                ->max('position') + 1;
            
            \App\Models\Queue::create([
                'visit_id' => $labVisit->id,
                'patient_id' => $consultation->patient_id,
                'branch_id' => $consultation->branch_id,
                'queue_type' => 'Lab',
                'position' => $nextPosition,
                'status' => 'waiting',
                'priority' => 'routine',
                'queued_at' => now()
            ]);
        }
    }

    /**
     * Update existing or create new radiology orders (used for both draft save and completion).
     * Queue is created only on completion via ensureRadiologyQueueForConsultation().
     */
    private function updateOrCreateRadiologyOrders(Consultation $consultation, array $radiologyOrders)
    {
        if (empty($radiologyOrders)) {
            return;
        }

        foreach ($radiologyOrders as $orderData) {
            if (empty($orderData['modality_id']) || empty($orderData['department_id'])) {
                continue;
            }

            if (!empty($orderData['radiology_request_id'])) {
                $radiologyRequest = \App\Models\RadiologyRequest::find($orderData['radiology_request_id']);
                if ($radiologyRequest) {
                    $radiologyRequest->update([
                        'modality_id' => $orderData['modality_id'],
                        'department_id' => $orderData['department_id'],
                        'clinical_history' => $orderData['clinical_history'] ?? $radiologyRequest->clinical_history,
                        'clinical_question' => $orderData['clinical_question'] ?? $radiologyRequest->clinical_question,
                        'indication' => $orderData['indication'] ?? $radiologyRequest->indication,
                        'priority' => $orderData['priority'] ?? $radiologyRequest->priority,
                        'scheduled_date' => !empty($orderData['scheduled_date']) ? $orderData['scheduled_date'] : null,
                        'scheduled_time' => !empty($orderData['scheduled_time']) ? $orderData['scheduled_time'] : null,
                    ]);
                }
                continue;
            }

            // Create new radiology request
            $requestNumber = 'RAD-' . strtoupper(\Illuminate\Support\Str::random(8));
            $modality = \App\Models\ImagingModality::find($orderData['modality_id']);

            $radiologyRequest = \App\Models\RadiologyRequest::create([
                'request_number' => $requestNumber,
                'patient_id' => $consultation->patient_id,
                'consultation_id' => $consultation->id,
                'branch_id' => $consultation->branch_id ?? $consultation->patient?->branch_id,
                'doctor_id' => $consultation->doctor_id,
                'modality_id' => $orderData['modality_id'],
                'department_id' => $orderData['department_id'],
                'clinical_history' => $orderData['clinical_history'] ?? $consultation->chief_complaint ?? '',
                'clinical_question' => $orderData['clinical_question'] ?? '',
                'indication' => $orderData['indication'] ?? '',
                'priority' => $orderData['priority'] ?? 'routine',
                'scheduled_date' => !empty($orderData['scheduled_date']) ? $orderData['scheduled_date'] : null,
                'scheduled_time' => !empty($orderData['scheduled_time']) ? $orderData['scheduled_time'] : null,
                'requested_date' => now(),
                'status' => 'requested',
                'billing_status' => 'pending',
                'billing_amount' => $modality?->base_cost,
            ]);
            \App\Models\ConsultationIntervention::create([
                'consultation_id' => $consultation->id,
                'intervention_type' => 'imaging_order',
                'description' => 'Radiology Request: ' . ($modality ? $modality->name : 'Unknown Modality'),
                'procedure_code' => $radiologyRequest->request_number,
                'priority' => $orderData['priority'] ?? 'routine',
                'status' => 'ordered',
                'ordered_by' => auth()->id(),
                'ordered_at' => now()
            ]);
        }
    }

    /**
     * Ensure radiology queue exists for this consultation's visit (call on completion only).
     */
    private function ensureRadiologyQueueForConsultation(Consultation $consultation): void
    {
        $visit = $consultation->visit;
        
        if (!$visit) {
            // If no visit exists, create one with RadiologyOnly visit type
            $visit = \App\Models\Visit::create([
                'patient_id' => $consultation->patient_id,
                'visit_token' => 'RAD-' . strtoupper(uniqid()),
                'visit_type' => 'RadiologyOnly',
                'status' => 'active',
                'branch_id' => $consultation->branch_id,
                'check_in_time' => now(),
                'created_by' => auth()->id()
            ]);
            
            // Automatically create queue for RadiologyOnly visit (consistent with VisitController)
            $visitController = app(\App\Http\Controllers\Web\VisitController::class);
            $reflection = new \ReflectionClass($visitController);
            $method = $reflection->getMethod('createQueueForVisit');
            $method->setAccessible(true);
            $method->invoke($visitController, $visit, 'RadiologyOnly');
        }

        // Check if radiology queue entry already exists for this visit
        $existingQueue = \App\Models\Queue::where('visit_id', $visit->id)
            ->where('queue_type', 'Radiology')
            ->where('status', '!=', 'cancelled')
            ->first();
        
        if (!$existingQueue) {
            // Add to radiology queue
            $nextPosition = \App\Models\Queue::where('queue_type', 'Radiology')
                ->where('branch_id', $consultation->branch_id)
                ->whereDate('queued_at', today())
                ->where('status', '!=', 'cancelled')
                ->max('position');
            
            $nextPosition = ($nextPosition ?? 0) + 1;
            
            \App\Models\Queue::create([
                'visit_id' => $visit->id,
                'patient_id' => $consultation->patient_id,
                'branch_id' => $consultation->branch_id,
                'queue_type' => 'Radiology',
                'position' => $nextPosition,
                'status' => 'waiting',
                'priority' => $consultation->priority ?? 'routine',
                'queued_at' => now(),
                'created_by' => auth()->id()
            ]);
        }
    }

    /**
     * Create radiology orders from consultation (used by store() for new consultations).
     */
    private function createRadiologyOrders(Consultation $consultation, array $radiologyOrders)
    {
        $this->updateOrCreateRadiologyOrders($consultation, $radiologyOrders);
        $this->ensureRadiologyQueueForConsultation($consultation);
    }

    /**
     * Create workflow steps based on next stage
     */
    private function createWorkflowSteps(Consultation $consultation, string $nextStage)
    {
        $workflowSteps = [];

        switch ($nextStage) {
            case 'pharmacy':
                $workflowSteps[] = [
                    'step' => 'pharmacy_dispensing',
                    'description' => 'Patient directed to pharmacy for medication dispensing',
                    'status' => 'pending',
                    'priority' => 'routine'
                ];
                break;

            case 'laboratory':
                $workflowSteps[] = [
                    'step' => 'laboratory_testing',
                    'description' => 'Patient directed to laboratory for testing',
                    'status' => 'pending',
                    'priority' => 'routine'
                ];
                break;

            case 'pharmacy_lab':
                $workflowSteps[] = [
                    'step' => 'pharmacy_dispensing',
                    'description' => 'Patient directed to pharmacy for medication dispensing',
                    'status' => 'pending',
                    'priority' => 'routine'
                ];
                $workflowSteps[] = [
                    'step' => 'laboratory_testing',
                    'description' => 'Patient will proceed to laboratory after pharmacy',
                    'status' => 'pending',
                    'priority' => 'routine'
                ];
                break;

            case 'completed':
                $workflowSteps[] = [
                    'step' => 'consultation_complete',
                    'description' => 'Consultation completed - patient can be discharged',
                    'status' => 'completed',
                    'priority' => 'routine'
                ];
                break;
        }

        // Store workflow steps in consultation
        $consultation->update([
            'workflow_steps' => $workflowSteps,
            'next_stage' => $nextStage
        ]);
    }

    /**
     * Call next patient in consultation queue
     */
    public function callNextConsultation(Request $request)
    {
        try {
            $request->validate([
                'branch_id' => 'required|exists:branches,id'
            ]);

            $branchId = $request->branch_id;

        // Find next consultation in queue (priority-based, scoped to current doctor)
        $nextConsultation = Consultation::with(['patient', 'visit'])
            ->where('branch_id', $branchId)
            ->where('doctor_id', auth()->id())
            ->where('consultation_status', 'ongoing')
            ->where('is_draft', true) // Only draft consultations (created by reception/nurse)
            ->orderByRaw("CASE urgency WHEN 'critical' THEN 1 WHEN 'urgent' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$nextConsultation) {
            return response()->json([
                'success' => false,
                'message' => 'No consultations waiting in queue'
            ], 404);
        }

        try {
            app(\App\Services\PaymentPolicyService::class)->assertCanProceedWithConsultation(
                $nextConsultation->visit,
                (int) $nextConsultation->patient_id,
                $nextConsultation->branch_id
            );
        } catch (\App\Exceptions\PaymentGateException $e) {
            return response()->json(array_merge([
                'success' => false,
            ], $e->toArray()), 402);
        }

        // Update consultation status to indicate it's being called
        $nextConsultation->update([
            'called_at' => now(),
            'called_by' => auth()->id()
        ]);

        // Prepare data for audio announcement
        $audioData = [
            'consultation_id' => $nextConsultation->id,
            'patient' => [
                'first_name' => $nextConsultation->patient->first_name,
                'last_name' => $nextConsultation->patient->last_name,
                'patient_number' => $nextConsultation->patient->patient_number
            ],
            'visit_token' => $nextConsultation->visit->visit_token ?? 'N/A',
            'priority' => $nextConsultation->urgency ?? 'routine',
            'chief_complaint' => $nextConsultation->chief_complaint
        ];

            return response()->json([
                'success' => true,
                'data' => $audioData,
                'message' => 'Patient called successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . implode(', ', $e->errors()),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Call next consultation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check for drug interactions between prescribed medications
     */
    private function checkDrugInteractions(array $prescriptionOrders)
    {
        $drugIds = collect($prescriptionOrders)->pluck('drug_id')->filter()->toArray();
        
        if (count($drugIds) < 2) {
            return; // No interactions possible with single drug
        }

        // Check for drug interactions using the database
        $interactions = \App\Models\DrugInteraction::checkMultipleDrugs($drugIds);
        
        if (!empty($interactions)) {
            // Log serious interactions for pharmacist review
            $seriousInteractions = collect($interactions)->whereIn('severity', ['major', 'severe']);
            
            if ($seriousInteractions->isNotEmpty()) {
                \Log::warning('Serious drug interactions detected', [
                    'drug_ids' => $drugIds,
                    'consultation_id' => request()->input('consultation_id'),
                    'doctor_id' => auth()->id(),
                    'interactions' => $seriousInteractions->map(function($interaction) {
                        return [
                            'drug1' => $interaction->drug1->name,
                            'drug2' => $interaction->drug2->name,
                            'severity' => $interaction->severity,
                            'description' => $interaction->description
                        ];
                    })->toArray()
                ]);
            }
        }

        // Log prescription creation for audit
        \Log::info('Prescription created with multiple drugs', [
            'drug_ids' => $drugIds,
            'consultation_id' => request()->input('consultation_id'),
            'doctor_id' => auth()->id(),
            'interactions_found' => count($interactions)
        ]);
    }

    /**
     * Check stock availability for a drug
     */
    private function checkStockAvailability($drugId, $quantity, $branchId)
    {
        $stock = \App\Models\DrugStock::where('drug_id', $drugId)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();

        if (!$stock) {
            return false;
        }

        return $stock->current_stock >= $quantity;
    }

    /**
     * Show form to create radiology request from consultation
     */
    public function createRadiologyRequest(Consultation $consultation)
    {
        // SECURITY: If user is a doctor, ensure they can only create radiology requests for their own consultations
        if (auth()->user()->hasRole('doctor') && $consultation->doctor_id != auth()->id()) {
            abort(403, 'You can only create radiology requests for your own consultations.');
        }

        $modalities = \App\Models\ImagingModality::where('is_active', true)->orderBy('name')->get();
        $departments = \App\Models\RadiologyDepartment::where('is_active', true)->orderBy('name')->get();
        
        // SECURITY: If user is a doctor, only show themselves
        if (auth()->user()->hasRole('doctor')) {
            $doctors = collect([auth()->user()]);
        } else {
            $doctors = \App\Models\User::whereHas('roles', function($q) {
                $q->whereIn('name', ['doctor', 'consultant', 'radiologist']);
            })->orderBy('first_name')->get();
        }

        return view('radiology.create-from-consultation', compact('consultation', 'modalities', 'departments', 'doctors'));
    }

    /**
     * Create Diagnosis records from consultation ICD/impression fields when present.
     */
    protected function syncDiagnosisRecords(Consultation $consultation, Request $request): void
    {
        $icdCode = $request->input('icd_10_code') ?: $consultation->icd_10_code;
        $description = $request->input('doctors_impression') ?: $consultation->doctors_impression;

        if (empty($icdCode) && empty($description)) {
            return;
        }

        $existingQuery = Diagnosis::where('consultation_id', $consultation->id);
        if (Schema::hasColumn('diagnoses', 'is_primary')) {
            $existing = (clone $existingQuery)->where('is_primary', true)->first();
        } else {
            $existing = (clone $existingQuery)->where('diagnosis_type', 'primary')->first();
        }

        $payload = [
            'consultation_id' => $consultation->id,
            'icd_code' => $icdCode ?: 'UNSPECIFIED',
            'diagnosis_description' => $description ?: ($icdCode ? 'Diagnosis ' . $icdCode : 'Clinical impression'),
            'diagnosis_type' => 'primary',
            'confidence_level' => 'probable',
            'diagnosed_by' => auth()->id(),
            'diagnosis_date' => now()->toDateString(),
        ];

        if (Schema::hasColumn('diagnoses', 'is_primary')) {
            $payload['is_primary'] = true;
        }
        if (Schema::hasColumn('diagnoses', 'is_active')) {
            $payload['is_active'] = true;
        }

        if ($existing) {
            $existing->update($payload);
        } else {
            Diagnosis::create($payload);
        }
    }

    /**
     * Load lab requests for a consultation, including legacy rows missing consultation_id.
     */
    private function attachConsultationLabRequests(Consultation $consultation): void
    {
        if ($consultation->labRequests->isNotEmpty()) {
            return;
        }

        if (!$consultation->patient_id || !$consultation->doctor_id) {
            return;
        }

        $legacyLabRequests = \App\Models\LabRequest::with([
            'results' => fn ($q) => $q->orderBy('parameter_name'),
            'testType',
            'template',
        ])
            ->where('patient_id', $consultation->patient_id)
            ->where('doctor_id', $consultation->doctor_id)
            ->whereNull('consultation_id')
            ->whereDate('created_at', $consultation->consultation_date)
            ->latest('updated_at')
            ->get();

        $consultation->setRelation('labRequests', $legacyLabRequests);
    }

    public function export(Request $request)
    {
        $branchId = $this->resolveUserBranchId('view_consultations');
        $query = Consultation::with(['patient', 'doctor'])
            ->where('branch_id', $branchId);

        if (auth()->user()->hasRole('doctor')) {
            $doctorId = auth()->id();
            $query->where(function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId)
                    ->orWhereHas('visit', fn ($visitQuery) => $visitQuery->where('assigned_doctor_id', $doctorId));
            });
        }

        if ($request->has('filter') && $request->filter !== 'all') {
            match ($request->filter) {
                'draft', 'pending' => $query->where('is_draft', true)->where('consultation_status', 'ongoing'),
                'in_progress' => $query->where('is_draft', false)->where('consultation_status', 'ongoing'),
                'completed' => $query->where('consultation_status', 'completed')->where('is_draft', false),
                'ongoing' => $query->where('consultation_status', 'ongoing')->where('consultation_status', '!=', 'cancelled'),
                'cancelled' => $query->where('consultation_status', 'cancelled'),
                default => null,
            };
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('consultation_number', 'like', "%{$search}%")
                    ->orWhere('chief_complaint', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('patient_number', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('consultation_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('consultation_date', '<=', $request->date_to);
        }

        $query->orderByRaw("CASE WHEN urgency = 'critical' THEN 1 WHEN urgency = 'urgent' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'desc');

        return $this->exportFromQuery($request, $query, $this->consultationExportColumns(), 'consultations', 'view_consultations');
    }

    public function exportCompleted(Request $request)
    {
        $branchId = $request->get('branch_id', auth()->user()->staffProfile->branch_id ?? 1);
        $dateFilter = $request->get('date_filter', 'today');

        $query = Consultation::with(['patient', 'doctor'])
            ->where('branch_id', $branchId)
            ->where('doctor_id', auth()->id())
            ->where('consultation_status', 'completed')
            ->where('is_draft', false);

        match ($dateFilter) {
            'today' => $query->whereDate('updated_at', today()),
            'week' => $query->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year),
            default => null,
        };

        $query->orderBy('updated_at', 'desc');

        return $this->exportFromQuery($request, $query, $this->consultationExportColumns(), 'completed-consultations', 'view_consultations');
    }

    /**
     * @return array<string, string|callable>
     */
    private function consultationExportColumns(): array
    {
        return [
            'Consultation #' => 'consultation_number',
            'Patient' => fn ($c) => $c->patient?->full_name ?? '',
            'Patient Number' => fn ($c) => $c->patient?->patient_number ?? '',
            'Doctor' => fn ($c) => $this->formatExportUserName($c->doctor),
            'Date' => fn ($c) => $this->formatExportDate($c->consultation_date),
            'Status' => 'consultation_status',
            'Chief Complaint' => 'chief_complaint',
            'Urgency' => 'urgency',
        ];
    }
}
