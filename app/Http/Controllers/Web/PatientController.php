<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\Patient;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RevenueTransaction;
use App\Models\Vital;
use App\Services\PaymentService;
use App\Services\PatientDuplicateService;
use App\Services\PatientPortalAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class PatientController extends Controller
{
    use ExportsListData, ResolvesUserBranch;

    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    /**
     * Display a listing of patients (server-side rendering)
     */
    public function index(Request $request)
    {
        // Get user's branch - try multiple sources
        $user = auth()->user();
        $userBranch = $user->branches()->first();
        $branchId = $userBranch ? $userBranch->id : null;
        
        // Fallback to staff profile branch_id if no branch relationship
        if (!$branchId && $user->staffProfile && $user->staffProfile->branch_id) {
            $branchId = $user->staffProfile->branch_id;
        }
        
        // Fallback to current_branch_id if set
        if (!$branchId && $user->current_branch_id) {
            $branchId = $user->current_branch_id;
        }
        
        // Final fallback: use default branch (ID 1) if user has permissions but no branch assigned
        // This prevents 403 errors for users with valid permissions but missing branch assignment
        if (!$branchId) {
            // Check if user has view_patients permission - if yes, allow access with default branch
            if ($user->can('view_patients')) {
                $branchId = 1; // Default branch
                \Log::warning('User accessing patients without branch assignment', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'using_default_branch' => true
                ]);
            } else {
                abort(403, 'User not assigned to any branch');
            }
        }
        
        // Fetch patients with relationships (server-side) - filtered by branch
        $patients = Patient::with(['branch', 'creator'])
            ->where('branch_id', $branchId)
            ->latest('id')
            ->paginate(20);
        
        // Get statistics - filtered by branch
        $statistics = [
            'total' => Patient::where('branch_id', $branchId)->count(),
            'male' => Patient::where('branch_id', $branchId)->where('gender', 'Male')->count(),
            'female' => Patient::where('branch_id', $branchId)->where('gender', 'Female')->count(),
            'with_nhis' => Patient::where('branch_id', $branchId)->whereNotNull('nhis_number')->count(),
        ];
        
        return view('patients.index', compact('patients', 'statistics'));
    }
    
    /**
     * Show the form for creating a new patient
     */
    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        
        return view('patients.create', compact('branches'));
    }
    
    /**
     * Store a newly created patient in database
     */
    public function store(Request $request)
    {
        // Get user's branch - try multiple sources
        $user = auth()->user();
        $userBranch = $user->branches()->first();
        $branchId = $userBranch ? $userBranch->id : null;
        
        // Fallback to staff profile branch_id if no branch relationship
        if (!$branchId && $user->staffProfile && $user->staffProfile->branch_id) {
            $branchId = $user->staffProfile->branch_id;
        }
        
        // Fallback to current_branch_id if set
        if (!$branchId && $user->current_branch_id) {
            $branchId = $user->current_branch_id;
        }
        
        // Final fallback: use default branch (ID 1) if user has permissions but no branch assigned
        if (!$branchId) {
            if ($user->can('edit_patients')) {
                $branchId = 1; // Default branch
                \Log::warning('User creating patient without branch assignment', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'using_default_branch' => true
                ]);
            } else {
                abort(403, 'User not assigned to any branch');
            }
        }
        
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'other_names' => 'nullable|string|max:255',
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => 'nullable|date|before:today',
            'age' => 'nullable|integer|min:0|max:150',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'nhis_number' => 'nullable|string|max:255',
            'ghana_card_number' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:255',
            'branch_id' => 'nullable|exists:branches,id', // Validate branch_id if provided
        ]);
        
        // Use branch_id from form if provided and valid, otherwise use user's branch
        if ($request->filled('branch_id') && Branch::find($request->branch_id)) {
            $finalBranchId = $request->branch_id;
        } else {
            $finalBranchId = $branchId; // Use resolved branch ID from user
        }
        
        // Verify branch exists
        $userBranch = Branch::find($finalBranchId);
        if (!$userBranch) {
            return back()
                ->withInput()
                ->with('error', 'Invalid branch selected. Please select a valid branch.');
        }
        
        // If age is provided but date_of_birth is not, calculate approximate date_of_birth
        if ($request->filled('age') && !$request->filled('date_of_birth')) {
            $age = (int) $request->age;
            // Calculate approximate date of birth (using January 1st as default)
            $validated['date_of_birth'] = now()->subYears($age)->startOfYear()->format('Y-m-d');
        }
        
        // If neither age nor date_of_birth is provided, set date_of_birth to null
        if (!$request->filled('age') && !$request->filled('date_of_birth')) {
            $validated['date_of_birth'] = null;
        }
        
        // Remove age from validated data as it's not a database column
        unset($validated['age']);
        
        // Check for duplicate patients BEFORE creating
        $duplicateService = app(PatientDuplicateService::class);
        $duplicateCheck = $duplicateService->checkForDuplicates($validated, null, $finalBranchId);
        
        if ($duplicateCheck['is_duplicate'] && !$request->boolean('confirmed_no_duplicate')) {
            Log::info('Patient registration blocked by duplicate check', [
                'user_id' => auth()->id(),
                'match_count' => $duplicateCheck['count'],
                'confirmed_override' => false,
            ]);
            // Format matches for display
            $formattedMatches = $duplicateService->formatMatchesForResponse($duplicateCheck['matches']);
            
            // Build error message with duplicate details
            $errorMessage = 'A patient with similar information already exists in the system. ';
            
            if ($duplicateCheck['has_high_confidence_match']) {
                $errorMessage .= 'Please review the following existing patient(s) before creating a new record:';
            } else {
                $errorMessage .= 'Please review potential matches:';
            }
            
            // Store duplicate information in session for display
            session()->flash('duplicate_patients', $formattedMatches);
            session()->flash('duplicate_error', $errorMessage);
            
            return back()
                ->withInput()
                ->with('error', $errorMessage)
                ->with('duplicate_patients', $formattedMatches);
        }

        if ($duplicateCheck['is_duplicate'] && $request->boolean('confirmed_no_duplicate')) {
            Log::info('Patient registration proceeding despite duplicate match (user confirmed)', [
                'user_id' => auth()->id(),
                'match_count' => $duplicateCheck['count'],
            ]);
        }
        
        // Generate patient number using ID prefix service
        $validated['patient_number'] = $this->generatePatientNumber();
        $validated['branch_id'] = $finalBranchId; // Use final resolved branch ID
        $validated['registration_source'] = 'web'; // Tag as registered from web
        $validated['account_status'] = 'active'; // Staff-created patients are automatically active
        $validated['account_activated_at'] = now(); // Set activation timestamp
        $validated['activated_by'] = auth()->id(); // Staff member who created the patient
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();
        
        try {
            \DB::beginTransaction();
            
            $patient = Patient::create($validated);

            $portalResult = app(PatientPortalAccountService::class)->ensurePortalUserForPatient($patient);

            // One-time registration fee: create invoice if enabled in system settings
            try {
                app(\App\Services\RegistrationFeeService::class)->createInvoiceForPatient($patient, $patient->branch_id);
            } catch (\Throwable $e) {
                Log::warning('Registration fee invoice creation failed for new patient', ['patient_id' => $patient->id, 'error' => $e->getMessage()]);
            }
            
            \DB::commit();

            $redirect = redirect()->route('visits.create', ['patient_id' => $patient->id])
                ->with('success', 'Patient registered successfully! Patient #: ' . $patient->patient_number . '. Please complete patient check-in, then record vital signs.');

            if ($portalResult['created'] && !empty($portalResult['password'])) {
                $redirect->with('portal_password', $portalResult['password'])
                    ->with('portal_email', $portalResult['email']);
            }

            return $redirect;
        } catch (\Illuminate\Validation\ValidationException $e) {
            \DB::rollBack();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Illuminate\Database\QueryException $e) {
            \DB::rollBack();
            Log::error('Database error creating patient: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token', 'password']),
                'sql_state' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Provide more specific error messages
            $errorMessage = 'Failed to register patient. ';
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $errorMessage .= 'Patient number already exists. Please try again.';
            } elseif (str_contains($e->getMessage(), 'Column') && str_contains($e->getMessage(), 'cannot be null')) {
                $errorMessage .= 'Required information is missing. Please check all required fields.';
            } else {
                $errorMessage .= 'Please check your input and try again.';
            }
            
            return back()
                ->withInput()
                ->with('error', $errorMessage);
        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('Error creating patient: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token', 'password']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to register patient. Please try again. If the problem persists, contact support.');
        }
    }
    
    /**
     * Redirect to visit creation page with the new patient pre-selected
     */
    private function redirectToVisitCreation(Patient $patient)
    {
        return redirect()->route('visits.create', ['patient_id' => $patient->id])
            ->with('success', 'Patient registered successfully! Patient ID: ' . $patient->patient_number . '. Please create a visit for this patient.');
    }
    
    /**
     * Display the specified patient
     */
    public function show(Patient $patient)
    {
        if ($patient->branch_id) {
            $this->assertResourceInUserBranch((int) $patient->branch_id, 'view_patients');
        }

        // Load all relationships for comprehensive view
        $patient->load([
            'branch',
            'visits.queues',
            'visits.assignedDoctor',
            'visits.assignedNurse',
            'appointments.doctor',
            'consultations.consultationDiagnoses',
            'consultations.interventions',
            'consultations.doctor',
            'consultations.vitals.recordedBy',
            'consultations.prescriptions.orders.drug',
            'consultations.labRequests',
            'prescriptions.orders.drug',
            'labRequests.results',
            'radiologyRequests.modality',
            'scans',
            'allergies',
            'medicalHistory',
            'invoices.payments',
            'bedAssignments.bed.ward',
            'insurancePolicies.insuranceProvider',
            'creator',
            'updater',
            'user'
        ]);
        
        // Get all vitals for this patient (through consultations)
        // This ensures we get vitals even if they're not loaded through the relationship
        $allPatientVitals = Vital::with(['consultation.patient', 'recordedBy'])
            ->whereHas('consultation', function($q) use ($patient) {
                $q->where('patient_id', $patient->id);
            })
            ->orderBy('recorded_at', 'desc')
            ->get();
        
        // Attach the vitals collection to the patient object for use in the view
        $patient->allVitals = $allPatientVitals;

        $this->scopePatientLabRequestsForDoctor($patient);
        $this->scopePatientRadiologyRequestsForDoctor($patient);
        $this->scopePatientConsultationsForDoctor($patient);
        
        // Get comprehensive financial summary
        $financialSummary = $this->getFinancialSummary($patient->id);
        
        return view('patients.show', compact('patient', 'financialSummary'));
    }

    /**
     * Get comprehensive financial summary for a patient.
     * 
     * @param int $patientId
     * @return array
     */
    public function getFinancialSummary($patientId)
    {
        $patient = Patient::findOrFail($patientId);
        
        // Get all invoices
        $invoices = Invoice::where('patient_id', $patientId)
            ->with(['payments', 'branch'])
            ->orderBy('invoice_date', 'desc')
            ->get();
        
        // Get all payments
        $payments = Payment::where('patient_id', $patientId)
            ->with(['invoice', 'processor', 'branch'])
            ->where('status', 'completed')
            ->orderBy('payment_date', 'desc')
            ->get();
        
        // Calculate totals
        $totalInvoiced = $invoices->sum('total_amount');
        $totalPaid = $invoices->sum('paid_amount');
        $totalOutstanding = $invoices->sum('balance_amount');
        
        // Get invoice status breakdown
        $invoicesByStatus = [
            'unpaid' => $invoices->where('payment_status', 'unpaid')->count(),
            'partial' => $invoices->where('payment_status', 'partial')->count(),
            'paid' => $invoices->where('payment_status', 'paid')->count(),
            'overdue' => $invoices->where('payment_status', 'overdue')->count()
        ];
        
        // Get payment method breakdown
        $paymentsByMethod = $payments->groupBy('payment_method')->map(function($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('amount')
            ];
        });
        
        // Get revenue by service type
        $revenueByService = RevenueTransaction::where('patient_id', $patientId)
            ->where('status', 'completed')
            ->selectRaw('service_type, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('service_type')
            ->get()
            ->keyBy('service_type');
        
        // Get outstanding invoices
        $outstandingInvoices = $invoices->filter(function($invoice) {
            return in_array($invoice->payment_status, ['unpaid', 'partial', 'overdue']);
        })->values();
        
        // Get recent payments
        $recentPayments = $payments->take(10);
        
        return [
            'summary' => [
                'total_invoiced' => $totalInvoiced,
                'total_paid' => $totalPaid,
                'total_outstanding' => $totalOutstanding,
                'collection_rate' => $totalInvoiced > 0 ? round(($totalPaid / $totalInvoiced) * 100, 2) : 0
            ],
            'invoices' => [
                'total_count' => $invoices->count(),
                'by_status' => $invoicesByStatus,
                'outstanding_count' => $outstandingInvoices->count(),
                'all_invoices' => $invoices,
                'outstanding_invoices' => $outstandingInvoices
            ],
            'payments' => [
                'total_count' => $payments->count(),
                'by_method' => $paymentsByMethod,
                'recent_payments' => $recentPayments,
                'last_payment_date' => $payments->first()?->payment_date
            ],
            'revenue_breakdown' => $revenueByService
        ];
    }

    /**
     * Get financial summary as JSON (AJAX endpoint for web, also usable by API).
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function financialSummary($id)
    {
        try {
            $summary = $this->getFinancialSummary($id);
            
            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching financial summary: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Show the form for editing the specified patient
     */
    public function edit(Patient $patient)
    {
        $branches = Branch::where('is_active', true)->get();
        
        return view('patients.edit', compact('patient', 'branches'));
    }
    
    /**
     * Update the specified patient in database
     */
    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'other_names' => 'nullable|string|max:255',
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => 'nullable|date|before:today',
            'age' => 'nullable|integer|min:0|max:150',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'nhis_number' => 'nullable|string|max:255',
            'ghana_card_number' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:255',
            'branch_id' => 'required|exists:branches,id',
        ]);
        
        // If age is provided but date_of_birth is not, calculate approximate date_of_birth
        if ($request->filled('age') && !$request->filled('date_of_birth')) {
            $age = (int) $request->age;
            // Calculate approximate date of birth (using January 1st as default)
            $validated['date_of_birth'] = now()->subYears($age)->startOfYear()->format('Y-m-d');
        }
        
        // If neither age nor date_of_birth is provided, set date_of_birth to null
        if (!$request->filled('age') && !$request->filled('date_of_birth')) {
            $validated['date_of_birth'] = null;
        }
        
        // Remove age from validated data as it's not a database column
        unset($validated['age']);
        
        $validated['updated_by'] = auth()->id();
        
        try {
            $patient->update($validated);
            
            return redirect()->route('patients.show', $patient)
                ->with('success', 'Patient information updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update patient. Please try again.');
        }
    }
    
    /**
     * Remove the specified patient from database
     */
    public function destroy(Patient $patient)
    {
        try {
            // Check if patient has existing records
            $hasRecords = $patient->appointments()->count() > 0 
                || $patient->consultations()->count() > 0 
                || $patient->invoices()->count() > 0
                || $patient->visits()->count() > 0;
            
            if ($hasRecords) {
                return back()
                    ->with('error', 'Cannot delete patient with existing records. Consider deactivating instead.');
            }
            
            $patientNumber = $patient->patient_number;
            $patient->delete();
            
            return redirect()->route('patients.index')
                ->with('success', 'Patient ' . $patientNumber . ' deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting patient: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'patient_id' => $patient->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete patient. They may have existing records.');
        }
    }
    
    /**
     * Bulk delete patients
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:patients,id',
        ]);
        
        $deletedCount = 0;
        $skippedCount = 0;
        $errors = [];
        $skippedPatients = [];
        
        foreach ($validated['ids'] as $id) {
            $patient = Patient::find($id);
            
            if (!$patient) {
                $skippedCount++;
                continue;
            }
            
            // Check if patient has existing records
            $hasRecords = $patient->appointments()->count() > 0 
                || $patient->consultations()->count() > 0 
                || $patient->invoices()->count() > 0
                || $patient->visits()->count() > 0;
            
            if ($hasRecords) {
                $skippedCount++;
                $skippedPatients[] = $patient->patient_number;
                $errors[] = "Patient {$patient->patient_number} has existing records and cannot be deleted.";
                continue;
            }
            
            try {
                $patientNumber = $patient->patient_number;
                $patient->delete();
                $deletedCount++;
                
                // Log the deletion
                Log::info('Patient deleted (bulk)', [
                    'patient_id' => $id,
                    'patient_number' => $patientNumber,
                    'deleted_by' => auth()->id(),
                    'deleted_at' => now(),
                ]);
            } catch (\Exception $e) {
                $skippedCount++;
                $errors[] = "Failed to delete patient {$patient->patient_number}: " . $e->getMessage();
                Log::error('Error deleting patient (bulk): ' . $e->getMessage(), [
                    'user_id' => auth()->id(),
                    'patient_id' => $id,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        // Build response message
        $message = "Successfully deleted {$deletedCount} patient(s).";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} patient(s) were skipped.";
            if (!empty($errors)) {
                $message .= " " . implode(' ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $message .= " (and " . (count($errors) - 3) . " more)";
                }
            }
        }
        
        if ($deletedCount === 0) {
            return redirect()->route('patients.index')
                ->with('error', $message);
        }
        
        return redirect()->route('patients.index')
            ->with('success', $message);
    }
    
    /**
     * Search patients (AJAX endpoint for web)
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q', '');
            
            if (empty($query) || strlen($query) < 2) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Please enter at least 2 characters to search'
                ]);
            }
            
            $user = auth()->user();
            $patientsQuery = Patient::with(['branch', 'creator'])
                ->search($query)
                ->orderBy('id', 'desc')
                ->limit(20);

            // Role-based data filtering
            if ($user->hasRole('patient')) {
                // Patients can only see their own data
                $patientsQuery->where('user_id', $user->id);
            } elseif ($user->hasRole(['doctor', 'nurse', 'pharmacist', 'receptionist', 'lab_technician'])) {
                // Medical staff can see patients from their branch
                if ($user->staffProfile && $user->staffProfile->branch_id) {
                    $patientsQuery->where('branch_id', $user->staffProfile->branch_id);
                }
            }
            // Super admin and other roles can see all patients

            $patients = $patientsQuery->get();

            return response()->json([
                'success' => true,
                'data' => $patients,
                'message' => 'Search completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching patients: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check for potential duplicate patients using comprehensive duplicate detection service
     * This checks phone, email, NHIS, name+DOB, and name similarity
     */
    public function checkDuplicates(Request $request)
    {
        try {
            $firstName = trim($request->get('first_name', ''));
            $lastName = trim($request->get('last_name', ''));
            $phone = trim($request->get('phone', ''));
            $email = trim($request->get('email', ''));
            $nhisNumber = trim($request->get('nhis_number', ''));
            $ghanaCardNumber = trim($request->get('ghana_card_number', ''));
            $dateOfBirth = $request->get('date_of_birth', null);
            $excludeId = $request->get('exclude_id', null);
            $branchIdFromRequest = $request->get('branch_id', null);
            
            // Need at least some identifying information to check
            if (empty($firstName) && empty($lastName) && empty($phone) && empty($email) && empty($nhisNumber)) {
                return response()->json([
                    'success' => true,
                    'has_duplicates' => false,
                    'duplicates' => [],
                    'message' => 'Please provide at least first name, last name, phone, email, or NHIS number'
                ]);
            }
            
            // Get branch for name-similarity scoping (identifiers are checked globally)
            $branchId = null;
            if ($branchIdFromRequest && Branch::find($branchIdFromRequest)) {
                $branchId = (int) $branchIdFromRequest;
            } else {
                $user = auth()->user();
                $userBranch = $user->branches()->first();
                $branchId = $userBranch ? $userBranch->id : null;

                if (!$branchId && $user->staffProfile && $user->staffProfile->branch_id) {
                    $branchId = $user->staffProfile->branch_id;
                }
            }
            
            // Prepare patient data for duplicate checking
            $patientData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone ?: null,
                'email' => $email ?: null,
                'nhis_number' => $nhisNumber ?: null,
                'ghana_card_number' => $ghanaCardNumber ?: null,
                'date_of_birth' => $dateOfBirth,
            ];
            
            // Use the duplicate service
            $duplicateService = app(PatientDuplicateService::class);
            $duplicateCheck = $duplicateService->checkForDuplicates($patientData, $excludeId, $branchId);
            
            // Format matches for response
            $formattedMatches = $duplicateService->formatMatchesForResponse($duplicateCheck['matches']);
            
            return response()->json([
                'success' => true,
                'has_duplicates' => $duplicateCheck['is_duplicate'],
                'has_potential_matches' => $duplicateCheck['count'] > 0,
                'duplicates' => $formattedMatches,
                'count' => $duplicateCheck['count'],
                'has_high_confidence_match' => $duplicateCheck['has_high_confidence_match'],
                'message' => $duplicateCheck['count'] > 0
                    ? 'Found ' . $duplicateCheck['count'] . ' potential duplicate(s)' 
                    : 'No duplicates found'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error checking for duplicate patients: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'has_duplicates' => false,
                'duplicates' => [],
                'message' => 'Error checking for duplicates: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate patient number using ID prefix service
     */
    private function generatePatientNumber()
    {
        try {
            $service = app(\App\Services\IdPrefixService::class);
            return $service->generateId('patient');
        } catch (\Exception $e) {
            // Fallback if no prefix is configured
            return 'PAT-' . str_pad(Patient::count() + 1, 6, '0', STR_PAD_LEFT);
        }
    }
    
    /**
     * Display pending patient registrations
     */
    public function pendingRegistrations()
    {
        // Get pending registrations
        $pendingRegistrations = Patient::where('account_status', 'pending')
            ->latest('id')
            ->paginate(20);
        
        // Statistics
        $pendingCount = Patient::where('account_status', 'pending')->count();
        $approvedTodayCount = Patient::where('account_status', 'active')
            ->whereDate('account_activated_at', today())
            ->count();
        $rejectedCount = Patient::where('account_status', 'rejected')->count();
        $monthlyCount = Patient::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        return view('patients.pending-registrations', compact(
            'pendingRegistrations',
            'pendingCount',
            'approvedTodayCount',
            'rejectedCount',
            'monthlyCount'
        ));
    }
    
    /**
     * Get patient details for modal view
     */
    public function getPatientDetails($id)
    {
        $patient = Patient::findOrFail($id);
        
        return response()->json([
            'patient_number' => $patient->patient_number,
            'full_name' => $patient->full_name,
            'gender' => $patient->gender,
            'date_of_birth' => $patient->date_of_birth->format('d M Y'),
            'age' => $patient->age,
            'phone' => $patient->phone,
            'email' => $patient->email,
            'address' => $patient->address,
            'nhis_number' => $patient->nhis_number,
            'ghana_card_number' => $patient->ghana_card_number,
            'emergency_contact_name' => $patient->emergency_contact_name,
            'emergency_contact_phone' => $patient->emergency_contact_phone,
            'emergency_contact_relationship' => $patient->emergency_contact_relationship,
            'created_at' => $patient->created_at->format('d M Y h:i A'),
            'account_status' => $patient->account_status,
        ]);
    }
    
    /**
     * Generate portal access for a legacy patient without user_id.
     */
    public function generatePortalAccess(Patient $patient)
    {
        try {
            $result = app(PatientPortalAccountService::class)->ensurePortalUserForPatient($patient);

            $redirect = redirect()->route('patients.show', $patient);

            if ($result['created'] && !empty($result['password'])) {
                return $redirect
                    ->with('success', 'Portal access generated successfully.')
                    ->with('portal_password', $result['password'])
                    ->with('portal_email', $result['email']);
            }

            return $redirect->with('info', $result['message']);
        } catch (\Exception $e) {
            Log::error('Failed to generate portal access', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('patients.show', $patient)
                ->with('error', 'Failed to generate portal access. Please try again.');
        }
    }

    /**
     * Reset portal password for a linked patient.
     */
    public function resetPortalPassword(Patient $patient)
    {
        try {
            $result = app(PatientPortalAccountService::class)->resetPortalPassword($patient);

            return redirect()->route('patients.show', $patient)
                ->with('success', 'Portal password reset successfully. Share the new password with the patient.')
                ->with('portal_password', $result['password'])
                ->with('portal_email', $result['email']);
        } catch (\Exception $e) {
            Log::error('Failed to reset portal password', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('patients.show', $patient)
                ->with('error', 'Failed to reset portal password. ' . $e->getMessage());
        }
    }

    /**
     * Approve patient registration
     */
    public function approveRegistration($id)
    {
        try {
            DB::beginTransaction();
            
            $patient = Patient::findOrFail($id);
            
            // Check if already processed
            if ($patient->account_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This registration has already been processed.'
                ], 400);
            }
            
            // Update patient status
            $patient->account_status = 'active';
            $patient->account_activated_at = now();
            $patient->activated_by = auth()->id();
            $patient->save();

            $portalResult = null;
            if (!$patient->user_id) {
                $portalResult = app(PatientPortalAccountService::class)->ensurePortalUserForPatient($patient);
            }
            
            // Send email notification
            $this->sendApprovalEmail($patient);
            
            // Send SMS notification
            $this->sendApprovalSMS($patient);
            
            DB::commit();

            if ($portalResult && $portalResult['created'] && !empty($portalResult['password'])) {
                session()->flash('portal_password', $portalResult['password']);
                session()->flash('portal_email', $portalResult['email']);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Patient registration approved successfully! Email and SMS notifications have been sent.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve registration: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Reject patient registration
     */
    public function rejectRegistration(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:10'
        ]);
        
        try {
            DB::beginTransaction();
            
            $patient = Patient::findOrFail($id);
            
            // Check if already processed
            if ($patient->account_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This registration has already been processed.'
                ], 400);
            }
            
            // Update patient status
            $patient->account_status = 'rejected';
            $patient->rejection_reason = $request->rejection_reason;
            $patient->activated_by = auth()->id();
            $patient->save();
            
            // Send rejection email
            $this->sendRejectionEmail($patient);
            
            // Send rejection SMS
            $this->sendRejectionSMS($patient);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Patient registration rejected. Email and SMS notifications have been sent.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject registration: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Send approval email to patient
     */
    private function sendApprovalEmail($patient)
    {
        try {
            // Get email settings from database
            $emailFrom = Setting::where('key', 'email_from_address')->first()->value ?? config('mail.from.address');
            $emailFromName = Setting::where('key', 'email_from_name')->first()->value ?? config('mail.from.name');
            $platformName = Setting::where('key', 'platform_name')->first()->value ?? config('app.name', 'Hospital');
            
            $subject = "Account Activated - {$platformName}";
            $message = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #1e3a8a 0%, #3498db 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                        .button { display: inline-block; padding: 12px 30px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>🎉 Account Activated!</h1>
                        </div>
                        <div class='content'>
                            <p>Dear {$patient->full_name},</p>
                            
                            <p>Congratulations! Your patient account registration has been approved and activated.</p>
                            
                            <p><strong>Your Account Details:</strong></p>
                            <ul>
                                <li><strong>Patient Number:</strong> {$patient->patient_number}</li>
                                <li><strong>Email:</strong> {$patient->email}</li>
                            </ul>
                            
                            <p>You can now log in to your account and access our services.</p>
                            
                            <a href='" . url('/login') . "' class='button'>Login Now</a>
                            
                            <p>If you have any questions or need assistance, please don't hesitate to contact us.</p>
                            
                            <p>Thank you for choosing {$platformName}!</p>
                            
                            <p>Best regards,<br>{$platformName} Team</p>
                        </div>
                        <div class='footer'>
                            <p>&copy; " . date('Y') . " {$platformName}. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=utf-8\r\n";
            $headers .= "From: {$emailFromName} <{$emailFrom}>\r\n";
            
            mail($patient->email, $subject, $message, $headers);
            
        } catch (\Exception $e) {
            \Log::error('Failed to send approval email: ' . $e->getMessage());
        }
    }
    
    /**
     * Send approval SMS to patient
     */
    private function sendApprovalSMS($patient)
    {
        try {
            // Get SMS settings from database
            $smsEnabled = Setting::where('key', 'sms_enabled')->first()->value ?? false;
            
            if (!$smsEnabled) {
                return;
            }
            
            $smsApiKey = Setting::where('key', 'sms_api_key')->first()->value ?? '';
            $smsSenderId = Setting::where('key', 'sms_sender_id')->first()->value ?? 'HOSPITAL';
            $smsGatewayUrl = Setting::where('key', 'sms_gateway_url')->first()->value ?? '';
            $platformName = Setting::where('key', 'platform_name')->first()->value ?? config('app.name', 'Hospital');
            
            $message = "Dear {$patient->first_name}, your {$platformName} patient account (ID: {$patient->patient_number}) has been activated! You can now log in to access our services.";
            
            // Send SMS via configured gateway
            if ($smsGatewayUrl && $smsApiKey) {
                Http::post($smsGatewayUrl, [
                    'api_key' => $smsApiKey,
                    'sender_id' => $smsSenderId,
                    'to' => $patient->phone,
                    'message' => $message
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to send approval SMS: ' . $e->getMessage());
        }
    }
    
    /**
     * Send rejection email to patient
     */
    private function sendRejectionEmail($patient)
    {
        try {
            $emailFrom = Setting::where('key', 'email_from_address')->first()->value ?? config('mail.from.address');
            $emailFromName = Setting::where('key', 'email_from_name')->first()->value ?? config('mail.from.name');
            $platformName = Setting::where('key', 'platform_name')->first()->value ?? config('app.name', 'Hospital');
            
            $subject = "Registration Update - {$platformName}";
            $message = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                        .reason-box { background: #fff; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Registration Update</h1>
                        </div>
                        <div class='content'>
                            <p>Dear {$patient->full_name},</p>
                            
                            <p>Thank you for your interest in registering with {$platformName}.</p>
                            
                            <p>Unfortunately, we are unable to approve your registration at this time.</p>
                            
                            <div class='reason-box'>
                                <strong>Reason:</strong><br>
                                {$patient->rejection_reason}
                            </div>
                            
                            <p>If you believe this is an error or would like to discuss this decision, please contact our support team.</p>
                            
                            <p>Best regards,<br>{$platformName} Team</p>
                        </div>
                        <div class='footer'>
                            <p>&copy; " . date('Y') . " {$platformName}. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=utf-8\r\n";
            $headers .= "From: {$emailFromName} <{$emailFrom}>\r\n";
            
            mail($patient->email, $subject, $message, $headers);
            
        } catch (\Exception $e) {
            \Log::error('Failed to send rejection email: ' . $e->getMessage());
        }
    }
    
    /**
     * Send rejection SMS to patient
     */
    private function sendRejectionSMS($patient)
    {
        try {
            $smsEnabled = Setting::where('key', 'sms_enabled')->first()->value ?? false;
            
            if (!$smsEnabled) {
                return;
            }
            
            $smsApiKey = Setting::where('key', 'sms_api_key')->first()->value ?? '';
            $smsSenderId = Setting::where('key', 'sms_sender_id')->first()->value ?? 'HOSPITAL';
            $smsGatewayUrl = Setting::where('key', 'sms_gateway_url')->first()->value ?? '';
            $platformName = Setting::where('key', 'platform_name')->first()->value ?? config('app.name', 'Hospital');
            
            $message = "Dear {$patient->first_name}, we regret to inform you that your {$platformName} registration could not be approved. Please contact us for more information.";
            
            if ($smsGatewayUrl && $smsApiKey) {
                Http::post($smsGatewayUrl, [
                    'api_key' => $smsApiKey,
                    'sender_id' => $smsSenderId,
                    'to' => $patient->phone,
                    'message' => $message
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to send rejection SMS: ' . $e->getMessage());
        }
    }

    /**
     * Doctors see only their own consultations on the patient profile.
     */
    private function scopePatientConsultationsForDoctor(Patient $patient): void
    {
        $user = auth()->user();

        if (!$user->hasRole('doctor')) {
            return;
        }

        $scopedConsultations = $patient->consultations
            ->where('doctor_id', $user->id)
            ->sortByDesc('consultation_date')
            ->values();

        $patient->setRelation('consultations', $scopedConsultations);
    }

    /**
     * Doctors see only lab requests they ordered or from their consultations.
     */
    private function scopePatientLabRequestsForDoctor(Patient $patient): void
    {
        $user = auth()->user();

        if (!$user->hasRole('doctor') || $this->userHasLabStaffPermissions()) {
            return;
        }

        $scopedLabRequests = $patient->labRequests()
            ->where(function ($query) use ($user) {
                $query->where('doctor_id', $user->id)
                    ->orWhereHas('consultation', fn ($c) => $c->where('doctor_id', $user->id));
            })
            ->with('results')
            ->orderByDesc('created_at')
            ->get();

        $patient->setRelation('labRequests', $scopedLabRequests);
    }

    /**
     * Doctors see only radiology requests they ordered or from their consultations.
     */
    private function scopePatientRadiologyRequestsForDoctor(Patient $patient): void
    {
        $user = auth()->user();

        if (!$user->hasRole('doctor') || $this->userHasRadiologyStaffPermissions()) {
            return;
        }

        $scopedRadiologyRequests = $patient->radiologyRequests()
            ->where(function ($query) use ($user) {
                $query->where('doctor_id', $user->id)
                    ->orWhereHas('consultation', fn ($c) => $c->where('doctor_id', $user->id));
            })
            ->with('modality')
            ->orderByDesc('created_at')
            ->get();

        $patient->setRelation('radiologyRequests', $scopedRadiologyRequests);
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $branchId = $user->branches()->first()?->id
            ?? $user->staffProfile?->branch_id
            ?? $user->current_branch_id
            ?? ($user->can('view_patients') ? 1 : null);

        if (! $branchId) {
            abort(403, 'User not assigned to any branch');
        }

        $query = Patient::with(['branch'])->where('branch_id', $branchId)->latest('id');

        return $this->exportFromQuery($request, $query, [
            'Patient Number' => 'patient_number',
            'First Name' => 'first_name',
            'Last Name' => 'last_name',
            'Gender' => 'gender',
            'Date of Birth' => fn ($p) => $this->formatExportDate($p->date_of_birth),
            'Phone' => 'phone',
            'NHIS Number' => 'nhis_number',
            'Branch' => fn ($p) => $p->branch?->name ?? '',
            'Registered At' => fn ($p) => $this->formatExportDate($p->created_at, 'Y-m-d H:i'),
        ], 'patients', 'view_patients');
    }
}
