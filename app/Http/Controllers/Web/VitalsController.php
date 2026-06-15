<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Http\Controllers\Concerns\WorkflowNavigation;
use App\Models\Vital;
use App\Models\Patient;
use App\Models\Consultation;
use App\Models\Visit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VitalsController extends Controller
{
    use ExportsListData, ResolvesUserBranch, WorkflowNavigation;

    /**
     * Display a listing of all vital signs records
     */
    public function index(Request $request)
    {
        $query = Vital::with(['consultation.patient', 'recordedBy'])
            ->orderBy('recorded_at', 'desc');

        if ($portalPatient = $this->portalPatient()) {
            $query->whereHas('consultation', function ($q) use ($portalPatient) {
                $q->where('patient_id', $portalPatient->id);
            });
        }

        // Filter by patient - query through consultation relationship (only if consultation exists)
        if ($request->has('patient_id') && $request->patient_id) {
            $query->whereHas('consultation', function($q) use ($request) {
                $q->where('patient_id', $request->patient_id);
            });
        }

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('recorded_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('recorded_at', '<=', $request->end_date);
        }

        // Filter by recorded_by
        if ($request->has('recorded_by') && $request->recorded_by) {
            $query->where('recorded_by', $request->recorded_by);
        }

        // Search by patient name - query through consultation relationship (only if consultation exists)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('consultation.patient', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('other_names', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $vitals = $query->paginate($perPage);

        // Get all patients for filter dropdown (limit to prevent performance issues)
        $patients = Patient::orderBy('first_name')->limit(1000)->get();

        return view('vitals.index', compact('vitals', 'patients'));
    }

    /**
     * Show the form for recording vital signs
     */
    public function create(Request $request)
    {
        $patients = Patient::latest()->get();
        $consultations = Consultation::where('consultation_status', 'ongoing')
            ->with('patient')
            ->latest()
            ->get();
        
        // Pre-select patient if provided
        $selectedPatientId = $request->get('patient_id');
        $selectedPatient = null;
        $selectedVisitId = $request->get('visit_id');
        $selectedConsultationId = null;
        
        if ($selectedPatientId) {
            $selectedPatient = Patient::find($selectedPatientId);
        }
        
        // If visit_id is provided, try to find associated consultation and prevent duplicate vitals
        if ($selectedVisitId) {
            $visit = Visit::find($selectedVisitId);
            if ($visit) {
                // Find consultation for this visit
                $consultation = Consultation::where('visit_id', $visit->id)
                    ->where('consultation_status', 'ongoing')
                    ->first();
                if ($consultation) {
                    $selectedConsultationId = $consultation->id;
                    // Prevent duplicate: if vitals already recorded for this visit's consultation, redirect to visit with message
                    if (Vital::where('consultation_id', $consultation->id)->exists()) {
                        return redirect()->route('visits.show', $visit)
                            ->with('info', 'Vital signs have already been recorded for this visit. Patient is in the queue for consultation.');
                    }
                }
                // When no consultation exists (legacy visit), duplicate check cannot run; VisitController now assigns default doctor so consultation is always created for OPD/Emergency
            }
        }
        
        return view('vitals.record', compact(
            'patients', 
            'consultations', 
            'selectedPatient', 
            'selectedPatientId',
            'selectedVisitId',
            'selectedConsultationId'
        ));
    }

    /**
     * Store newly recorded vital signs
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'consultation_id' => 'nullable|exists:consultations,id',
            'blood_pressure_systolic' => 'nullable|integer|min:50|max:300',
            'blood_pressure_diastolic' => 'nullable|integer|min:30|max:200',
            'pulse_rate' => 'nullable|integer|min:30|max:300',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'respiratory_rate' => 'nullable|integer|min:5|max:60',
            'oxygen_saturation' => 'nullable|integer|min:50|max:100',
            'height' => 'nullable|numeric|min:50|max:250',
            'weight' => 'nullable|numeric|min:10|max:300',
        ]);

        // Calculate BMI if height and weight are provided
        if (isset($validated['height']) && isset($validated['weight']) && $validated['height'] && $validated['weight']) {
            $heightInMeters = $validated['height'] / 100;
            $validated['bmi'] = round($validated['weight'] / ($heightInMeters * $heightInMeters), 2);
        }

        // Add metadata
        $validated['recorded_at'] = now();
        $validated['recorded_by'] = auth()->id();

        // Get patient_id before creating vital (since vitals table doesn't store patient_id)
        $patientId = $validated['patient_id'];
        $consultationId = $validated['consultation_id'] ?? null;
        
        // Check if coming from create patient flow (no visit_id in request means it came from create patient page)
        $visitIdFromRequest = $request->get('visit_id');
        $fromCreatePatient = $request->get('from_create', false) || !$visitIdFromRequest;
        
        // Remove patient_id from validated data before creating vital
        unset($validated['patient_id']);
        
        $vital = Vital::create($validated);

        // Determine which visit to link vitals to. When visit_id is in the request (from check-in flow), use that visit
        // so the same patient can have many visits at different times/dates and each gets its own vitals.
        $activeVisit = null;
        if ($visitIdFromRequest) {
            $activeVisit = \App\Models\Visit::find($visitIdFromRequest);
            if ($activeVisit && (int) $activeVisit->patient_id !== (int) $patientId) {
                $activeVisit = null; // Security: visit must belong to this patient
            }
        }
        if (!$activeVisit) {
            $activeVisit = \App\Models\Visit::where('patient_id', $patientId)
                ->where('status', 'active')
                ->whereIn('visit_type', ['OPD', 'IPD', 'Emergency'])
                ->latest()
                ->first();
        }
            
        $consultation = null;
        $visitWasAutoCreated = false;
        
        // If coming from create patient page, skip visit/consultation linking and redirect to check-in
        // This ensures vitals are recorded standalone, then user creates a new visit via check-in
        if ($fromCreatePatient) {
            return redirect()->route('visits.create', ['patient_id' => $patientId])
                ->with('success', 'Vital signs recorded successfully! Please proceed with patient check-in.');
        }
        
        // If no active visit exists and NOT coming from create patient flow, create one automatically to ensure vitals can be linked
        if (!$activeVisit && !$fromCreatePatient) {
            try {
                $patient = Patient::findOrFail($patientId);
                $userBranch = $this->resolveUserBranchId('record_vitals');
                
                // Try to find an available doctor from the branch to assign to the visit
                $availableDoctor = User::role('doctor')
                    ->whereHas('staffProfile', function($q) use ($userBranch) {
                        $q->where('branch_id', $userBranch);
                    })
                    ->where('is_active', true)
                    ->first();
                
                // Create a visit automatically for vitals recording
                $activeVisit = \App\Models\Visit::create([
                    'patient_id' => $patientId,
                    'branch_id' => $userBranch,
                    'visit_type' => 'OPD', // Default to OPD for vitals recording
                    'status' => 'active',
                    'assigned_doctor_id' => $availableDoctor ? $availableDoctor->id : null,
                    'check_in_time' => now(),
                    'created_by' => auth()->id(),
                ]);
                
                $visitWasAutoCreated = true;
                
                \Log::info('Auto-created visit for vitals recording', [
                    'visit_id' => $activeVisit->id,
                    'patient_id' => $patientId,
                    'assigned_doctor_id' => $activeVisit->assigned_doctor_id,
                    'created_by' => auth()->id(),
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to auto-create visit for vitals', [
                    'patient_id' => $patientId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        if ($activeVisit) {
            // Use ConsultationService to get or create consultation
            $consultationService = app(\App\Services\ConsultationService::class);
            
            // Prepare vitals data for consultation
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
            
            // Get or create consultation for vitals
            $consultation = $consultationService->getOrCreateConsultationForVitals(
                $activeVisit,
                $vitalsData,
                $consultationId
            );
            
            // Link the vital record to the consultation (if not already linked)
            if ($consultation && !$vital->consultation_id) {
                $vital->update(['consultation_id' => $consultation->id]);
            }
            
            // If visit doesn't have assigned doctor, log warning
            if (!$activeVisit->assigned_doctor_id) {
                \Log::warning('Vitals recorded for visit without assigned doctor', [
                    'visit_id' => $activeVisit->id,
                    'patient_id' => $patientId,
                ]);
            }
        } else {
            // If we still don't have a visit or consultation, log error
            \Log::error('Vitals recorded but could not create visit or consultation', [
                'patient_id' => $patientId,
                'vital_id' => $vital->id,
                'recorded_by' => auth()->id(),
            ]);
        }

        // Complete workflow step if visit has workflow
        if ($activeVisit) {
            $this->completeWorkflowStep($activeVisit, 'vitals_recording', [
                'vital_id' => $vital->id,
                'consultation_id' => $consultation->id ?? null,
            ]);
        }

        // Prepare success message
        $successMessage = 'Vital signs recorded successfully!';
        if ($consultation && $consultation->doctor) {
            $successMessage .= ' Patient has been added to Dr. ' . $consultation->doctor->name . '\'s consultation queue.';
        }

        // Redirect based on user role after recording vitals
        $user = auth()->user();
        
        if ($activeVisit) {
            // Use workflow navigation if available
            return $this->redirectToNextStep($activeVisit, $successMessage);
        }
        
        // Fallback to role-based redirect if no workflow
        if ($user->hasRole('doctor')) {
            // If user is a doctor, redirect to create consultation for the patient
            return redirect()->route('consultations.create-for-patient', $patientId)
                ->with('success', 'Vital signs recorded successfully! Please proceed with consultation.');
        } elseif ($user->hasRole('nurse')) {
            // If user is a nurse, show the vitals they just recorded with option to continue
            $message = 'Vital signs recorded successfully!';
            if ($activeVisit && $activeVisit->assigned_doctor_id) {
                $doctor = User::find($activeVisit->assigned_doctor_id);
                $doctorName = $doctor ? $doctor->name : 'the assigned doctor';
                $message .= ' Patient has been added to ' . $doctorName . '\'s consultation queue.';
            }
            // Redirect to show the vitals they just recorded
            return redirect()->route('vitals.show', $vital)
                ->with('success', $message)
                ->with('show_queue_link', true); // Flag to show link to queues
        } else {
            // For other roles, show the vitals record
            return redirect()->route('vitals.show', $vital)
                ->with('success', 'Vital signs recorded successfully!');
        }
    }

    /**
     * Display recorded vital signs
     */
    public function show(Vital $vital)
    {
        $vital->load(['consultation']);
        if ($vital->consultation) {
            $this->assertPortalPatientOwns($vital->consultation->patient_id);
        } elseif ($this->portalPatient()) {
            abort(403, 'You do not have access to this resource.');
        }

        // Ensure we have the latest attributes from DB (vitals data can be missing if model was not fully hydrated)
        $vital->refresh();
        // Load relationships - patient through consultation if consultation exists
        $vital->load(['consultation.patient', 'recordedBy']);
        
        // Also try to load patient directly (for backwards compatibility)
        // This will work if consultation_id exists, otherwise will be null
        try {
            if ($vital->consultation_id) {
                $vital->load('patient');
            }
        } catch (\Exception $e) {
            // If patient relationship fails (e.g., no consultation), continue without it
            \Log::debug('Could not load patient relationship for vital', [
                'vital_id' => $vital->id,
                'consultation_id' => $vital->consultation_id,
                'error' => $e->getMessage()
            ]);
        }

        // If vital record has no values but consultation was updated with vitals (ConsultationService), use consultation for display
        $consultation = $vital->consultation;
        $hasVitalData = $vital->blood_pressure_systolic !== null || $vital->blood_pressure_diastolic !== null
            || $vital->pulse_rate !== null || $vital->temperature !== null || $vital->respiratory_rate !== null
            || $vital->oxygen_saturation !== null || $vital->height !== null || $vital->weight !== null || $vital->bmi !== null;
        if (!$hasVitalData && $consultation) {
            $vitalsFromConsultation = [
                'blood_pressure_systolic' => $consultation->blood_pressure_systolic ?? null,
                'blood_pressure_diastolic' => $consultation->blood_pressure_diastolic ?? null,
                'pulse_rate' => $consultation->pulse_rate ?? null,
                'temperature' => $consultation->temperature ?? null,
                'respiratory_rate' => $consultation->respiratory_rate ?? null,
                'oxygen_saturation' => $consultation->oxygen_saturation ?? null,
                'height' => $consultation->height ?? null,
                'weight' => $consultation->weight ?? null,
                'bmi' => $consultation->bmi ?? null,
            ];
            foreach ($vitalsFromConsultation as $key => $value) {
                if ($value !== null && $value !== '') {
                    $vital->setAttribute($key, $value);
                }
            }
        }

        return view('vitals.show', compact('vital'));
    }

    /**
     * Show the form for editing vital signs
     */
    public function edit(Vital $vital)
    {
        $this->authorize('edit', $vital);
        
        return view('vitals.edit', compact('vital'));
    }

    /**
     * Update vital signs
     */
    public function update(Request $request, Vital $vital)
    {
        $this->authorize('edit', $vital);
        
        try {
            $validated = $request->validate([
                'blood_pressure_systolic' => 'nullable|integer|min:50|max:300',
                'blood_pressure_diastolic' => 'nullable|integer|min:30|max:200',
                'pulse_rate' => 'nullable|integer|min:30|max:300',
                'temperature' => 'nullable|numeric|min:30|max:45',
                'respiratory_rate' => 'nullable|integer|min:5|max:60',
                'oxygen_saturation' => 'nullable|integer|min:50|max:100',
                'height' => 'nullable|numeric|min:50|max:250',
                'weight' => 'nullable|numeric|min:10|max:300',
            ]);

            // Recalculate BMI if height and weight are provided
            if ($validated['height'] && $validated['weight']) {
                $heightInMeters = $validated['height'] / 100;
                $validated['bmi'] = round($validated['weight'] / ($heightInMeters * $heightInMeters), 2);
            }

            $vital->update($validated);

            return redirect()->route('vitals.show', $vital)
                ->with('success', 'Vital signs updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating vital signs: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'vital_id' => $vital->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update vital signs. Please try again.');
        }
    }

    /**
     * Delete vital signs record
     */
    public function destroy(Vital $vital)
    {
        $this->authorize('delete', $vital);
        
        try {
            // Get patient through consultation
            $patient = null;
            if ($vital->consultation) {
                $patient = $vital->consultation->patient;
            }
            
            $vital->delete();

            if ($patient) {
                return redirect()->route('patients.show', $patient)
                    ->with('success', 'Vital signs record deleted successfully!');
            }
            
            return redirect()->route('vitals.index')
                ->with('success', 'Vital signs record deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting vital signs: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'vital_id' => $vital->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete vital signs record. Please try again.');
        }
    }

    public function export(Request $request)
    {
        $query = Vital::with(['consultation.patient', 'recordedBy'])->orderBy('recorded_at', 'desc');

        if ($request->has('patient_id') && $request->patient_id) {
            $query->whereHas('consultation', fn ($q) => $q->where('patient_id', $request->patient_id));
        }
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('recorded_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('recorded_at', '<=', $request->end_date);
        }
        if ($request->has('recorded_by') && $request->recorded_by) {
            $query->where('recorded_by', $request->recorded_by);
        }
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('consultation.patient', fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('other_names', 'like', "%{$search}%"));
        }

        return $this->exportFromQuery($request, $query, [
            'Patient' => fn ($v) => $v->consultation?->patient?->full_name ?? '',
            'Blood Pressure' => fn ($v) => ($v->blood_pressure_systolic && $v->blood_pressure_diastolic)
                ? "{$v->blood_pressure_systolic}/{$v->blood_pressure_diastolic}" : '',
            'Pulse' => 'pulse_rate',
            'Temperature' => 'temperature',
            'Weight' => 'weight',
            'Recorded By' => fn ($v) => $this->formatExportUserName($v->recordedBy),
            'Recorded At' => fn ($v) => $this->formatExportDate($v->recorded_at, 'Y-m-d H:i'),
        ], 'vitals', 'view_vitals');
    }
}
