<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Branch;
use App\Services\PatientDuplicateService;
use App\Services\PatientPortalAccountService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PatientController extends Controller
{
    /**
     * Display a listing of patients.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $query = Patient::with(['branch', 'creator', 'updater'])
                ->orderBy('id', 'desc');

            // Role-based data filtering
            if ($user->hasRole('patient')) {
                // Patients can only see their own data
                $query->where('id', $user->id);
            } elseif ($user->hasRole(['doctor', 'nurse', 'pharmacist', 'receptionist', 'lab_technician'])) {
                // Medical staff can see patients from their branch
                if ($user->staffProfile && $user->staffProfile->branch_id) {
                    $query->where('branch_id', $user->staffProfile->branch_id);
                }
            }
            // Super admin and other roles can see all patients

            // Search functionality
            if ($request->has('search') && $request->search) {
                $query->search($request->search);
            }

            // Filter by branch (only for non-patient roles)
            if (!$user->hasRole('patient') && $request->has('branch_id') && $request->branch_id) {
                $query->where('branch_id', $request->branch_id);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $patients = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $patients->items(),
                'meta' => [
                    'current_page' => $patients->currentPage(),
                    'last_page' => $patients->lastPage(),
                    'per_page' => $patients->perPage(),
                    'total' => $patients->total()
                ],
                'message' => 'Patients retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving patients: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created patient.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
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
                'branch_id' => 'nullable|exists:branches,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            $patientData = $validator->validated();
            
            // If age is provided but date_of_birth is not, calculate approximate date_of_birth
            if ($request->filled('age') && !isset($patientData['date_of_birth'])) {
                $age = (int) $request->age;
                // Calculate approximate date of birth (using January 1st as default)
                $patientData['date_of_birth'] = now()->subYears($age)->startOfYear()->format('Y-m-d');
            }
            
            // If neither age nor date_of_birth is provided, set date_of_birth to null
            if (!$request->filled('age') && !isset($patientData['date_of_birth'])) {
                $patientData['date_of_birth'] = null;
            }
            
            // Remove age from patientData as it's not a database column
            if (isset($patientData['age'])) {
            unset($patientData['age']);
            }
            
            // Set default branch if not provided - try user's branch first, then default
            if (!isset($patientData['branch_id'])) {
                $user = auth()->user();
                $userBranch = $user->branches()->first();
                $branchId = $userBranch ? $userBranch->id : null;
                
                // Fallback to staff profile branch_id if no branch relationship
                if (!$branchId && $user->staffProfile && $user->staffProfile->branch_id) {
                    $branchId = $user->staffProfile->branch_id;
                }
                
                // Final fallback: use default branch
                $patientData['branch_id'] = $branchId ?? (Branch::where('code', 'MAIN')->first()->id ?? 1);
            }
            
            // Verify branch exists
            $branch = Branch::find($patientData['branch_id']);
            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid branch selected. Please select a valid branch.'
                ], 422);
            }
            
            // Check for duplicate patients BEFORE creating
            $duplicateService = app(PatientDuplicateService::class);
            $branchIdForCheck = $patientData['branch_id'] ?? null; // Safe access with null fallback
            $duplicateCheck = $duplicateService->checkForDuplicates($patientData, null, $branchIdForCheck);
            
            if ($duplicateCheck['is_duplicate']) {
                // Format matches for API response
                $formattedMatches = $duplicateService->formatMatchesForResponse($duplicateCheck['matches']);
                
                // Build error message
                $errorMessage = 'A patient with similar information already exists in the system. ';
                
                if ($duplicateCheck['has_high_confidence_match']) {
                    $errorMessage .= 'Please review the existing patient(s) before creating a new record.';
                } else {
                    $errorMessage .= 'Please review potential matches.';
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'duplicate_patients' => $formattedMatches,
                    'duplicate_count' => $duplicateCheck['count'],
                    'has_high_confidence_match' => $duplicateCheck['has_high_confidence_match']
                ], 409); // 409 Conflict status code
            }
            
            // Generate patient number using ID prefix service
            $patientData['patient_number'] = $this->generatePatientNumber();
            $patientData['registration_source'] = 'api'; // Tag as registered from API (staff-created via API)
            $patientData['account_status'] = 'active'; // Staff-created patients are automatically active
            $patientData['account_activated_at'] = now(); // Set activation timestamp
            $patientData['activated_by'] = auth()->id(); // Staff member who created the patient
            $patientData['created_by'] = auth()->id();
            $patientData['updated_by'] = auth()->id();

            DB::beginTransaction();
            
            try {
                $patient = Patient::create($patientData);

                $portalResult = app(PatientPortalAccountService::class)->ensurePortalUserForPatient($patient);
                $patient->refresh()->load('user');

                try {
                    app(\App\Services\RegistrationFeeService::class)->createInvoiceForPatient($patient, $patient->branch_id);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Registration fee invoice creation failed for new patient', ['patient_id' => $patient->id, 'error' => $e->getMessage()]);
                }
                
                DB::commit();
                
                $response = [
                    'success' => true,
                    'data' => $patient->load(['branch', 'creator', 'user']),
                    'message' => 'Patient created successfully',
                ];

                if ($portalResult['created'] && !empty($portalResult['password'])) {
                    $response['portal_credentials'] = [
                        'email' => $portalResult['email'],
                        'password' => $portalResult['password'],
                    ];
                }

                return response()->json($response, 201);
                
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();
                
                // Provide more specific error messages
                $errorMessage = 'Failed to create patient. ';
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $errorMessage .= 'Patient number already exists. Please try again.';
                } elseif (str_contains($e->getMessage(), 'Column') && str_contains($e->getMessage(), 'cannot be null')) {
                    $errorMessage .= 'Required information is missing. Please check all required fields.';
                } else {
                    $errorMessage .= 'Please check your input and try again.';
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }

        } catch (\Exception $e) {
            $transactionLevel = DB::transactionLevel();
            if ($transactionLevel > 0) {
                DB::rollBack();
            }
            
            Log::error('Error creating patient via API: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token', 'password']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating patient: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Display the specified patient.
     */
    public function show(Patient $patient): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Check if user can access this patient
            // If user is a patient, they can only see their own data
            if ($user->hasRole('patient') && $patient->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions',
                    'errors' => null
                ], 403);
            }
            
            $patient->load(['branch', 'creator', 'appointments', 'consultations']);

            return response()->json([
                'success' => true,
                'data' => $patient,
                'message' => 'Patient retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving patient: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get the authenticated patient's own profile
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user->hasRole('patient')) {
                return response()->json([
                    'success' => false,
                    'message' => 'This endpoint is only available for patients'
                ], 403);
            }
            
            $patient = Patient::where('user_id', $user->id)->first();
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient record not found'
                ], 404);
            }
            
            $patient->load(['branch', 'creator', 'appointments', 'consultations']);

            return response()->json([
                'success' => true,
                'data' => $patient,
                'message' => 'Patient profile retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving patient profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the authenticated patient's own profile.
     * 
     * This endpoint allows patients to update their own profile information,
     * including optional fields like nhis_number, ghana_card_number, and emergency contact details.
     * 
     * All fields are optional except those marked as required.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateMe(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user->hasRole('patient')) {
                return response()->json([
                    'success' => false,
                    'message' => 'This endpoint is only available for patients'
                ], 403);
            }
            
            $patient = Patient::where('user_id', $user->id)->first();
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient record not found'
                ], 404);
            }
            
            $validator = Validator::make($request->all(), [
                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
                'other_names' => 'nullable|string|max:255',
                'gender' => 'sometimes|required|in:Male,Female',
                'date_of_birth' => 'nullable|date|before:today',
                'age' => 'nullable|integer|min:0|max:150',
                'phone' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
                // Optional fields - can be updated by patient
                'nhis_number' => 'nullable|string|max:255',
                'ghana_card_number' => 'nullable|string|max:255',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:255',
                'emergency_contact_relationship' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Build patient data array - only include fields that are present in the request
            // This allows clearing fields by sending null/empty values
            $patientData = [];
            
            // Always update required fields if provided
            if ($request->has('first_name')) {
                $patientData['first_name'] = $request->input('first_name');
            }
            if ($request->has('last_name')) {
                $patientData['last_name'] = $request->input('last_name');
            }
            if ($request->has('email')) {
                $patientData['email'] = $request->input('email');
            }
            
            // Update optional fields if they are present in the request
            // This allows users to clear fields by submitting null/empty values
            if ($request->has('other_names')) {
                $patientData['other_names'] = $request->input('other_names');
            }
            if ($request->has('gender')) {
                $patientData['gender'] = $request->input('gender');
            }
            if ($request->has('phone')) {
                $patientData['phone'] = $request->input('phone');
            }
            if ($request->has('address')) {
                $patientData['address'] = $request->input('address');
            }
            
            // Handle date_of_birth with age fallback
            if ($request->filled('age') && !$request->filled('date_of_birth')) {
                $age = (int) $request->input('age');
                $patientData['date_of_birth'] = now()->subYears($age)->startOfYear()->format('Y-m-d');
            } elseif ($request->has('date_of_birth')) {
                $patientData['date_of_birth'] = $request->input('date_of_birth');
            }
            
            // Patient-specific optional fields - allow clearing by checking if key exists in request
            if ($request->has('nhis_number')) {
                $patientData['nhis_number'] = $request->input('nhis_number');
            }
            if ($request->has('ghana_card_number')) {
                $patientData['ghana_card_number'] = $request->input('ghana_card_number');
            }
            if ($request->has('emergency_contact_name')) {
                $patientData['emergency_contact_name'] = $request->input('emergency_contact_name');
            }
            if ($request->has('emergency_contact_phone')) {
                $patientData['emergency_contact_phone'] = $request->input('emergency_contact_phone');
            }
            if ($request->has('emergency_contact_relationship')) {
                $patientData['emergency_contact_relationship'] = $request->input('emergency_contact_relationship');
            }
            
            // Update user's name if first_name or last_name changed
            if (isset($patientData['first_name']) || isset($patientData['last_name'])) {
                $firstName = $patientData['first_name'] ?? $patient->first_name;
                $lastName = $patientData['last_name'] ?? $patient->last_name;
                $user->update([
                    'name' => trim($firstName . ' ' . $lastName),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ]);
            }
            
            // Update user's email if provided
            if (isset($patientData['email'])) {
                $user->update(['email' => $patientData['email']]);
            }
            
            $patientData['updated_by'] = $user->id;
            
            // Only update if there's data to update
            if (!empty($patientData)) {
                $patient->update($patientData);
            }

            return response()->json([
                'success' => true,
                'data' => $patient->fresh()->load(['branch', 'creator', 'updater']),
                'message' => 'Profile updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified patient.
     */
    public function update(Request $request, Patient $patient): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
                'other_names' => 'nullable|string|max:255',
                'gender' => 'sometimes|required|in:Male,Female',
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
                'branch_id' => 'nullable|exists:branches,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            $patientData = $validator->validated();
            
            // If age is provided but date_of_birth is not, calculate approximate date_of_birth
            if ($request->filled('age') && !isset($patientData['date_of_birth'])) {
                $age = (int) $request->age;
                // Calculate approximate date of birth (using January 1st as default)
                $patientData['date_of_birth'] = now()->subYears($age)->startOfYear()->format('Y-m-d');
            }
            
            // If neither age nor date_of_birth is provided and updating, keep existing date_of_birth
            // Only set to null if explicitly provided as null
            if (!$request->filled('age') && !isset($patientData['date_of_birth']) && !$request->has('date_of_birth')) {
                // Don't update date_of_birth if not provided
                unset($patientData['date_of_birth']);
            }
            
            // Remove age from patientData as it's not a database column
            if (isset($patientData['age'])) {
            unset($patientData['age']);
            }
            
            $patientData['updated_by'] = auth()->id();

            // Update patient record
            $patient->update($patientData);
            
            // If patient has a linked user account, synchronize user data when patient fields change
            if ($patient->user_id && $patient->user) {
                $user = $patient->user;
                $userData = [];
                
                // Update user's name if first_name or last_name changed
                if (isset($patientData['first_name']) || isset($patientData['last_name'])) {
                    $firstName = $patientData['first_name'] ?? $patient->first_name;
                    $lastName = $patientData['last_name'] ?? $patient->last_name;
                    $userData['name'] = trim($firstName . ' ' . $lastName);
                    $userData['first_name'] = $firstName;
                    $userData['last_name'] = $lastName;
                }
                
                // Update user's email if provided
                if (isset($patientData['email'])) {
                    $userData['email'] = $patientData['email'];
                }
                
                // Only update user if there's data to update
                if (!empty($userData)) {
                    $user->update($userData);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $patient->fresh()->load(['branch', 'creator', 'updater', 'user']),
                'message' => 'Patient updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating patient: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified patient.
     */
    public function destroy(Patient $patient): JsonResponse
    {
        try {
            $patient->delete();

            return response()->json([
                'success' => true,
                'message' => 'Patient deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting patient: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search patients.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            $patients = Patient::search($query)
                ->with(['branch', 'creator'])
                ->limit(20)
                ->get();

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
     * Get patient statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_patients' => Patient::count(),
                'male_patients' => Patient::where('gender', 'Male')->count(),
                'female_patients' => Patient::where('gender', 'Female')->count(),
                'patients_with_nhis' => Patient::whereNotNull('nhis_number')->count(),
                'new_patients_this_month' => Patient::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'patients_by_age_group' => [
                    '0-17' => Patient::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 0 AND 17')->count(),
                    '18-35' => Patient::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 35')->count(),
                    '36-60' => Patient::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 36 AND 60')->count(),
                    '60+' => Patient::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) > 60')->count(),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics: ' . $e->getMessage()
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

}