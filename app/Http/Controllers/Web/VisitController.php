<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Http\Controllers\Concerns\WorkflowNavigation;
use App\Models\Visit;
use App\Services\PaymentPolicyService;
use App\Services\PricingService;
use App\Models\Patient;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Services\PatientPortalAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VisitController extends Controller
{
    use ExportsListData, ResolvesUserBranch, WorkflowNavigation;
    /**
     * Display a listing of visits with optimized queries and caching
     * 
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        try {
            // Eager load relationships to avoid N+1 queries
            $query = Visit::with([
                    'patient:id,patient_number,first_name,last_name,other_names', 
                    'assignedDoctor:id,first_name,last_name',
                    'assignedNurse:id,first_name,last_name',
                    'branch:id,name'
                ]);

            // SECURITY: If user is a doctor, show only visits assigned to them
            if (auth()->user()->hasRole('doctor')) {
                $query->where('assigned_doctor_id', auth()->id());
            }

            $this->scopeQueryToPortalPatient($query);

            if ($request->filled('status') && in_array($request->status, ['active', 'completed', 'cancelled', 'transferred'], true)) {
                $query->where('status', $request->status);
            }

            $visits = $query->latest('id')->paginate(20)->withQueryString();
            
            $statsQuery = Visit::query();
            if ($portalPatient = $this->portalPatient()) {
                $statsQuery->where('patient_id', $portalPatient->id);
            }

            $statistics = [
                'total' => (clone $statsQuery)->count(),
                'active' => (clone $statsQuery)->where('status', 'active')->count(),
                'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
                'today' => (clone $statsQuery)->whereDate('check_in_time', today())->count(),
            ];
            
            return view('visits.index', compact('visits', 'statistics'));
            
        } catch (\Exception $e) {
            \Log::error('Error loading visits index: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Unable to load visits. Please try again.');
        }
    }
    
    public function create(Request $request)
    {
        // Load only pre-selected patient; others are fetched via AJAX search
        $selectedPatientId = old('patient_id', $request->get('patient_id'));
        $patients = collect();
        $selectedPatient = null;

        if ($selectedPatientId) {
            $selectedPatient = Patient::find($selectedPatientId);
            if ($selectedPatient) {
                $patients = collect([$selectedPatient]);
            }
        }

        $branchId = $this->resolveUserBranchId('create_visits');

        // SECURITY: If user is a doctor, only show themselves
        if (auth()->user()->hasRole('doctor')) {
            $doctors = collect([auth()->user()]);
        } else {
            $doctors = User::role('doctor')
                ->where('is_active', true)
                ->whereHas('staffProfile', fn ($q) => $q->where('branch_id', $branchId))
                ->get();

            if ($doctors->isEmpty()) {
                $doctors = User::role('doctor')->where('is_active', true)->get();
            }
        }

        $nurses = User::role('nurse')
            ->where('is_active', true)
            ->whereHas('staffProfile', fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        if ($nurses->isEmpty()) {
            $nurses = User::role('nurse')->where('is_active', true)->get();
        }
        
        return view('visits.create', compact('patients', 'doctors', 'nurses', 'selectedPatient', 'selectedPatientId'));
    }
    
    /**
     * Store a newly created visit in storage
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            // SECURITY: If user is a doctor, force assigned_doctor_id to be their own ID
            if (auth()->user()->hasRole('doctor')) {
                $request->merge(['assigned_doctor_id' => auth()->id()]);
            }

            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'visit_type' => 'required|in:OPD,IPD,Emergency,LabOnly,PharmacyOnly,RadiologyOnly',
                'assigned_doctor_id' => 'nullable|exists:users,id',
                'assigned_nurse_id' => 'nullable|exists:users,id',
                'chief_complaint' => 'nullable|string|max:1000',
                'priority' => 'nullable|in:routine,urgent,critical',
            ]);

            // Double-check: If user is a doctor, ensure they can only assign themselves
            if (auth()->user()->hasRole('doctor') && $validated['assigned_doctor_id'] != auth()->id()) {
                return back()->with('error', 'You can only assign visits to yourself.')->withInput();
            }
            
            if ($validated['visit_type'] === 'Emergency') {
                $validated['priority'] = 'critical';
            }

            $validated['check_in_time'] = now();
            $validated['status'] = 'active';
            $validated['branch_id'] = $this->resolveUserBranchId('create_visits');
            $validated['created_by'] = auth()->id();
            
            // Use database transaction to ensure data consistency
            \DB::beginTransaction();

            $patient = Patient::findOrFail($validated['patient_id']);
            $portalResult = null;
            if (!$patient->user_id) {
                $portalResult = app(PatientPortalAccountService::class)->ensurePortalUserForPatient($patient);
            }
            
            $visit = Visit::create($validated);
            
            // Automatically create appropriate queue based on visit type
            $this->createQueueForVisit($visit, $validated['visit_type']);
            
            // Only OPD, IPD, and Emergency go through consultation flow: assign doctor, create draft consultation, and initialize workflow.
            // LabOnly, PharmacyOnly, RadiologyOnly do NOT get a doctor or consultation — patient goes directly to the selected service.
            $consultationVisitTypes = ['OPD', 'IPD', 'Emergency'];
            if (in_array($visit->visit_type, $consultationVisitTypes)) {
                $branchId = $validated['branch_id'];
                if (!$visit->assigned_doctor_id) {
                    $defaultDoctor = User::role('doctor')
                        ->whereHas('staffProfile', fn($q) => $q->where('branch_id', $branchId))
                        ->where('is_active', true)
                        ->first();
                    if (!$defaultDoctor) {
                        $defaultDoctor = User::role('doctor')->where('is_active', true)->first();
                    }
                    if ($defaultDoctor) {
                        $visit->update(['assigned_doctor_id' => $defaultDoctor->id]);
                        $visit->refresh();
                    }
                }
                if ($visit->assigned_doctor_id) {
                    $consultationService = app(\App\Services\ConsultationService::class);
                    $consultationService->createDraftConsultationForVisit($visit);
                }
                $workflowName = $this->determineWorkflowName($visit);
                $this->initializeWorkflowForEntity($visit, $workflowName);
            }
            
            \DB::commit();
            
            // Clear cached statistics
            \Cache::forget('visit_statistics_' . $validated['branch_id']);

            $portalFlash = function ($redirect) use ($portalResult) {
                if ($portalResult && $portalResult['created'] && !empty($portalResult['password'])) {
                    return $redirect
                        ->with('portal_password', $portalResult['password'])
                        ->with('portal_email', $portalResult['email']);
                }
                return $redirect;
            };
            
            $paymentPolicy = app(PaymentPolicyService::class);
            $paymentSummary = $paymentPolicy->getPaymentStatusSummary(
                (int) $visit->patient_id,
                (int) $visit->branch_id,
                $visit
            );

            $paymentFlash = function ($redirect) use ($paymentSummary) {
                if ($paymentSummary['payment_required'] ?? false) {
                    return $redirect
                        ->with('warning', $paymentSummary['message'])
                        ->with('payment_required', true)
                        ->with('amount_due', $paymentSummary['amount_due'])
                        ->with('cashier_url', $paymentSummary['cashier_url']);
                }
                return $redirect;
            };

            $adminPricingNotice = function ($redirect) use ($visit) {
                if (!auth()->user()->can('manage_service_pricing') && !auth()->user()->can('create_service_pricing')) {
                    return $redirect;
                }
                if (!in_array($visit->visit_type, ['OPD', 'IPD', 'Emergency'], true)) {
                    return $redirect;
                }
                $pricingService = app(PricingService::class);
                if (!$pricingService->hasConfiguredPrice('consultation', (int) $visit->branch_id)) {
                    return $redirect->with(
                        'info',
                        'No consultation fee is configured for this branch. Add pricing under Service Pricing (service_id: consultation) before charges apply.'
                    );
                }
                return $redirect;
            };

            $afterCheckIn = function ($redirect) use ($portalFlash, $paymentFlash, $adminPricingNotice) {
                return $adminPricingNotice($paymentFlash($portalFlash($redirect)));
            };

            // --- Flow 2: Route by visit type (patient follows the selected path) ---
            // OPD / Emergency: vitals → then consultation flow (queue, doctor, lab/pharmacy as ordered).
            if (in_array($visit->visit_type, ['OPD', 'Emergency'])) {
                if (auth()->user()->can('record_vitals')) {
                    return $afterCheckIn(redirect()->route('vitals.create', [
                        'patient_id' => $visit->patient_id,
                        'visit_id' => $visit->id
                    ])->with('success', 'Patient checked in successfully! Visit Token: ' . $visit->visit_token . '. Please record patient vitals.'));
                }
                return $afterCheckIn(redirect()->route('visits.show', $visit)
                    ->with('success', 'Patient checked in successfully! Visit Token: ' . $visit->visit_token . '. Please ask a nurse to record vital signs, or record them yourself if you have access.'));
            }
            
            // IPD: same consultation flow (admission → bed → vitals → doctor consultation).
            if ($visit->visit_type === 'IPD') {
                $redirect = $this->redirectToNextStep($visit, 'Patient checked in successfully! Visit Token: ' . $visit->visit_token);
                return $afterCheckIn($redirect);
            }
            
            // Direct-service visits: patient does NOT go to consulting doctor; they go straight to the selected service.
            if ($visit->visit_type === 'LabOnly') {
                $success = 'Patient checked in successfully! Visit Token: ' . $visit->visit_token . '. Patient is in the lab queue.';
                if (auth()->user()->can('view_lab_requests') || auth()->user()->can('create_lab_requests')) {
                    return $afterCheckIn(redirect()->route('lab.create-from-walk-in', $visit)
                        ->with('success', $success . ' Proceed to create lab request for this visit.'));
                }
                if (auth()->user()->can('view_lab_queue') || auth()->user()->can('view_queues')) {
                    return $afterCheckIn(redirect()->route('queues.lab')->with('success', $success));
                }
                return $afterCheckIn(redirect()->route('visits.show', $visit)->with('success', $success));
            }
            if ($visit->visit_type === 'PharmacyOnly') {
                $success = 'Patient checked in successfully! Visit Token: ' . $visit->visit_token . '. Patient is in the pharmacy queue.';
                if (auth()->user()->can('view_pharmacy_queue') || auth()->user()->can('view_queues')) {
                    return $afterCheckIn(redirect()->route('queues.pharmacy')->with('success', $success));
                }
                return $afterCheckIn(redirect()->route('visits.show', $visit)->with('success', $success));
            }
            if ($visit->visit_type === 'RadiologyOnly') {
                $success = 'Patient checked in successfully! Visit Token: ' . $visit->visit_token . '. Patient is in the radiology queue.';
                if (auth()->user()->can('view_radiology_requests')
                    || auth()->user()->can('process_radiology_requests')
                    || auth()->user()->can('view_queues')) {
                    return $afterCheckIn(redirect()->route('queues.radiology')->with('success', $success));
                }
                return $afterCheckIn(redirect()->route('visits.show', $visit)->with('success', $success));
            }
            
            return $portalFlash(redirect()->route('visits.show', $visit)->with('success', 'Patient checked in successfully! Visit Token: ' . $visit->visit_token));
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
            
        } catch (\Exception $e) {
            \DB::rollBack();
            
            \Log::error('Error creating visit: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Unable to create visit. Please try again or contact support.')
                        ->withInput();
        }
    }
    
    public function show(Visit $visit)
    {
        $this->assertPortalPatientOwns($visit->patient_id);

        // SECURITY: If user is a doctor, ensure they can only view visits assigned to them
        if (auth()->user()->hasRole('doctor') && $visit->assigned_doctor_id != auth()->id()) {
            abort(403, 'You can only view visits assigned to you.');
        }

        $visit->load(['patient', 'assignedDoctor', 'assignedNurse', 'branch', 'queues', 'consultation.vitals.recordedBy']);

        $paymentPolicy = app(PaymentPolicyService::class);
        $paymentSummary = $paymentPolicy->getPaymentStatusSummary(
            (int) $visit->patient_id,
            (int) $visit->branch_id,
            $visit
        );
        $chargeBreakdown = ($paymentSummary['payment_required'] ?? false)
            ? $paymentPolicy->getChargeBreakdown((int) $visit->patient_id, (int) $visit->branch_id)
            : [];

        return view('visits.show', compact('visit', 'paymentSummary', 'chargeBreakdown'));
    }
    
    /**
     * Show the form for editing the specified visit.
     */
    public function edit(Visit $visit)
    {
        // SECURITY: If user is a doctor, ensure they can only edit visits assigned to them
        if (auth()->user()->hasRole('doctor') && $visit->assigned_doctor_id != auth()->id()) {
            abort(403, 'You can only edit visits assigned to you.');
        }

        $visit->load(['patient', 'assignedDoctor', 'assignedNurse', 'branch']);
        
        $patients = Patient::latest()->get();
        
        // SECURITY: If user is a doctor, only show themselves
        if (auth()->user()->hasRole('doctor')) {
            $doctors = collect([auth()->user()]);
        } else {
            $doctors = User::role('doctor')->get();
        }
        
        $nurses = User::role('nurse')->get();
        
        return view('visits.edit', compact('visit', 'patients', 'doctors', 'nurses'));
    }
    
    public function update(Request $request, Visit $visit)
    {
        // SECURITY: If user is a doctor, ensure they can only update visits assigned to them
        if (auth()->user()->hasRole('doctor') && $visit->assigned_doctor_id != auth()->id()) {
            abort(403, 'You can only update visits assigned to you.');
        }

        try {
            // SECURITY: Doctors cannot change assigned_doctor_id - it's already set and disabled in form
            // If doctor is updating, preserve their ID
            if (auth()->user()->hasRole('doctor')) {
                $request->merge(['assigned_doctor_id' => auth()->id()]);
            }

            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'visit_type' => 'required|in:OPD,IPD,Emergency,LabOnly,PharmacyOnly,RadiologyOnly',
                'assigned_doctor_id' => 'nullable|exists:users,id',
                'assigned_nurse_id' => 'nullable|exists:users,id',
                'chief_complaint' => 'nullable|string|max:1000',
                'priority' => 'nullable|in:routine,urgent,critical',
                'status' => 'required|in:active,completed,cancelled',
            ]);
            
            if ($validated['status'] === 'completed' && !$visit->check_out_time) {
                $validated['check_out_time'] = now();
            }
            
            $validated['updated_by'] = auth()->id();
            $previousStatus = $visit->status;
            $visit->update($validated);

            if (in_array($validated['status'], ['completed', 'cancelled'], true)
                && $previousStatus !== $validated['status']) {
                $this->completeWorkflowStep($visit, 'visit_closure', [
                    'visit_status' => $validated['status'],
                ]);
                $this->completeWorkflowForEntity($visit, 'visit_' . $validated['status']);
            }
            
            // Clear cached statistics
            \Cache::forget('visit_statistics_' . $visit->branch_id);
            
            return redirect()->route('visits.show', $visit)
                ->with('success', 'Visit updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating visit: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'visit_id' => $visit->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update visit. Please try again.');
        }
    }
    
    public function destroy(Visit $visit)
    {
        // SECURITY: If user is a doctor, ensure they can only delete visits assigned to them
        if (auth()->user()->hasRole('doctor') && $visit->assigned_doctor_id != auth()->id()) {
            abort(403, 'You can only delete visits assigned to you.');
        }

        try {
            // Prevent deletion of active visits
            if ($visit->status === 'active') {
                return back()
                    ->with('error', 'Cannot delete an active visit. Please complete or cancel it first.');
            }
            
            $visit->delete();
            
            return redirect()->route('visits.index')
                ->with('success', 'Visit deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting visit: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'visit_id' => $visit->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete visit. Please try again.');
        }
    }
    
    /**
     * Bulk delete visits
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:visits,id',
        ]);
        
        $deletedCount = 0;
        $skippedCount = 0;
        $errors = [];
        
        foreach ($validated['ids'] as $id) {
            $visit = Visit::find($id);
            
            if (!$visit) {
                $skippedCount++;
                continue;
            }
            
            // SECURITY: If user is a doctor, ensure they can only delete visits assigned to them
            if (auth()->user()->hasRole('doctor') && $visit->assigned_doctor_id != auth()->id()) {
                $skippedCount++;
                $errors[] = "Visit {$visit->visit_token} cannot be deleted - not assigned to you.";
                continue;
            }
            
            // Prevent deletion of active visits
            if ($visit->status === 'active') {
                $skippedCount++;
                $errors[] = "Visit {$visit->visit_token} is active and cannot be deleted.";
                continue;
            }
            
            // Check for associated records
            $hasQueues = $visit->queues()->count() > 0;
            $hasConsultations = $visit->consultations()->count() > 0;
            $hasLabRequests = $visit->labRequests()->count() > 0;
            $hasPrescriptions = $visit->prescriptions()->count() > 0;
            
            if ($hasQueues || $hasConsultations || $hasLabRequests || $hasPrescriptions) {
                $skippedCount++;
                $errors[] = "Visit {$visit->visit_token} has associated records and cannot be deleted.";
                continue;
            }
            
            try {
                $visitToken = $visit->visit_token;
                $visit->delete();
                $deletedCount++;
                
                // Clear cached statistics
                \Cache::forget('visit_statistics_' . $visit->branch_id);
                
                Log::info('Visit deleted (bulk)', [
                    'visit_id' => $id,
                    'visit_token' => $visitToken,
                    'deleted_by' => auth()->id(),
                    'deleted_at' => now(),
                ]);
            } catch (\Exception $e) {
                $skippedCount++;
                $errors[] = "Failed to delete visit {$visit->visit_token}: " . $e->getMessage();
                Log::error('Error deleting visit (bulk): ' . $e->getMessage(), [
                    'user_id' => auth()->id(),
                    'visit_id' => $id,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        // Build response message
        $message = "Successfully deleted {$deletedCount} visit(s).";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} visit(s) were skipped.";
            if (!empty($errors)) {
                $message .= " " . implode(' ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $message .= " (and " . (count($errors) - 3) . " more)";
                }
            }
        }
        
        if ($deletedCount === 0) {
            return redirect()->route('visits.index')
                ->with('error', $message);
        }
        
        return redirect()->route('visits.index')
            ->with('success', $message);
    }
    
    /**
     * Create appropriate queue for visit based on visit type.
     * Idempotent: does not create a second queue if this visit already has an active queue of this type.
     */
    private function createQueueForVisit($visit, $visitType)
    {
        $queueType = null;
        $priority = $visit->priority ?? 'routine';

        switch ($visitType) {
            case 'OPD':
                $queueType = 'OPD';
                break;
            case 'Emergency':
                $queueType = 'Emergency';
                $priority = 'critical'; // Emergency visits are always critical priority
                break;
            case 'LabOnly':
                $queueType = 'Lab';
                break;
            case 'PharmacyOnly':
                $queueType = 'Pharmacy';
                break;
            case 'RadiologyOnly':
                $queueType = 'Radiology';
                break;
            case 'IPD':
                // IPD visits don't need queues, they go directly to bed assignment
                return;
        }

        if (!$queueType) {
            return;
        }

        // Prevent duplicate: one active queue per visit per queue type (waiting/called/serving)
        $existing = \App\Models\Queue::where('visit_id', $visit->id)
            ->where('queue_type', $queueType)
            ->whereIn('status', ['waiting', 'called', 'serving'])
            ->exists();
        if ($existing) {
            return;
        }

        // Position must be unique per queue_type + branch_id (DB constraint), across all statuses
        $lastPosition = \App\Models\Queue::where('queue_type', $queueType)
            ->where('branch_id', $visit->branch_id)
            ->max('position') ?? 0;

        \App\Models\Queue::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'queue_type' => $queueType,
            'status' => 'waiting',
            'priority' => $priority,
            'position' => $lastPosition + 1,
            'branch_id' => $visit->branch_id,
            'queued_at' => now(),
            'created_by' => auth()->id(),
        ]);
    }

    public function export(Request $request)
    {
        $query = Visit::with(['patient', 'assignedDoctor', 'branch']);

        if (auth()->user()->hasRole('doctor')) {
            $query->where('assigned_doctor_id', auth()->id());
        }

        $query->latest('id');

        return $this->exportFromQuery($request, $query, [
            'Visit Token' => 'visit_token',
            'Patient' => fn ($v) => $v->patient?->full_name ?? '',
            'Patient Number' => fn ($v) => $v->patient?->patient_number ?? '',
            'Visit Type' => 'visit_type',
            'Status' => 'status',
            'Priority' => 'priority',
            'Check-in Time' => fn ($v) => $this->formatExportDate($v->check_in_time, 'Y-m-d H:i'),
            'Assigned Doctor' => fn ($v) => $this->formatExportUserName($v->assignedDoctor),
            'Branch' => fn ($v) => $v->branch?->name ?? '',
        ], 'visits', 'view_visits');
    }
}
