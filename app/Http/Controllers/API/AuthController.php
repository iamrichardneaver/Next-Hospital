<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Models\LoginAudit;
use App\Services\BranchAssignmentService;
use App\Services\PatientDuplicateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user and create token.
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                $this->recordLoginAudit($request, null, 'failed', 'Invalid credentials');

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401)->header('Access-Control-Allow-Origin', '*')
                  ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                  ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            }

            $token = $user->createToken('auth-token')->plainTextToken;
            $this->recordLoginAudit($request, $user, 'success');

            // Load appropriate relationships based on user role
            if ($user->hasRole('patient')) {
                $user->load(['patient', 'roles.permissions', 'permissions']);
                $patient = $user->patient;
                
                // Transform user data to match frontend expectations (for patients)
                $userData = [
                    'id' => $user->id,
                    'username' => $user->email,
                    'first_name' => $user->first_name ?? explode(' ', $user->name)[0] ?? '',
                    'last_name' => $user->last_name ?? explode(' ', $user->name)[1] ?? '',
                    'email' => $user->email,
                    'roles' => $user->roles->map(function($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'permissions' => $role->permissions->pluck('name'),
                        ];
                    }),
                    'branch_id' => $patient?->branch_id,
                    'profile_image' => null,
                    'is_active' => true,
                    // Include patient data for mobile app
                    'patient_id' => $patient?->id,
                    'patient_data' => $patient ? [
                        'id' => $patient->id,
                        'patient_number' => $patient->patient_number,
                        'first_name' => $patient->first_name,
                        'last_name' => $patient->last_name,
                        'phone' => $patient->phone,
                        'email' => $patient->email,
                        'gender' => $patient->gender,
                        'date_of_birth' => $patient->date_of_birth,
                        'address' => $patient->address,
                        'branch_id' => $patient->branch_id,
                    ] : null,
                    'patient_number' => $patient?->patient_number,
                ];
            } else {
                // For staff users
                $user->load(['staffProfile', 'roles.permissions', 'permissions']);
                
                $userData = [
                    'id' => $user->id,
                    'username' => $user->email,
                    'first_name' => $user->staffProfile?->first_name ?? explode(' ', $user->name)[0] ?? '',
                    'last_name' => $user->staffProfile?->last_name ?? explode(' ', $user->name)[1] ?? '',
                    'email' => $user->email,
                    'roles' => $user->roles->map(function($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'permissions' => $role->permissions->map(function($permission) {
                                return [
                                    'id' => $permission->id,
                                    'name' => $permission->name,
                                    'created_at' => $permission->created_at,
                                    'updated_at' => $permission->updated_at,
                                ];
                            }),
                            'created_at' => $role->created_at,
                            'updated_at' => $role->updated_at,
                        ];
                    }),
                    'permissions' => $user->getAllPermissions()->map(function($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'created_at' => $permission->created_at,
                            'updated_at' => $permission->updated_at,
                        ];
                    }),
                    'branch_id' => $user->staffProfile?->branch_id,
                    'profile_image' => $user->staffProfile?->photo,
                    'is_active' => $user->is_active ?? true,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $userData,
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600
                ],
                'message' => 'Login successful'
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string|min:8',
                'role' => 'required|string|in:patient,doctor,nurse,admin,pharmacist,receptionist,lab_technician',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create user with combined name for compatibility
            $fullName = trim($request->first_name . ' ' . $request->last_name);
            
            $user = User::create([
                'name' => $fullName,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Assign role
            $user->assignRole($request->role);
            
            // CRITICAL: Clear permission cache immediately to ensure changes take effect
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            // Refresh the user's permission cache
            $user->load('roles.permissions', 'permissions');

            $branchService = app(BranchAssignmentService::class);
            if ($branchService->isStaffRole($request->role)) {
                $branchService->assignUserToBranch($user, $request->input('branch_id'), [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'contact' => $request->email,
                ]);
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            $userWithProfile = $user->load(['staffProfile', 'patient', 'roles.permissions', 'permissions']);

            $userData = [
                'id' => $userWithProfile->id,
                'username' => $userWithProfile->email,
                'first_name' => $userWithProfile->staffProfile?->first_name ?? $request->first_name,
                'last_name' => $userWithProfile->staffProfile?->last_name ?? $request->last_name,
                'email' => $userWithProfile->email,
                'roles' => $userWithProfile->roles->map(function($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'permissions' => $role->permissions->map(function($permission) {
                            return [
                                'id' => $permission->id,
                                'name' => $permission->name,
                                'created_at' => $permission->created_at,
                                'updated_at' => $permission->updated_at,
                            ];
                        }),
                        'created_at' => $role->created_at,
                        'updated_at' => $role->updated_at,
                    ];
                }),
                'permissions' => $userWithProfile->getAllPermissions()->map(function($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'created_at' => $permission->created_at,
                        'updated_at' => $permission->updated_at,
                    ];
                }),
                'branch_id' => $userWithProfile->staffProfile?->branch_id,
                'profile_image' => $userWithProfile->staffProfile?->photo,
                'is_active' => $userWithProfile->is_active ?? true,
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $userData,
                    'token' => $token,
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600
                ],
                'message' => 'Registration successful'
            ], 201)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        }
    }

    /**
     * Logout user and revoke token.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            if ($request->filled('device_id')) {
                \App\Models\Device::query()
                    ->where('device_id', $request->input('device_id'))
                    ->where('user_id', $request->user()->id)
                    ->update([
                        'fcm_token' => null,
                        'is_active' => false,
                        'last_seen_at' => now(),
                    ]);
            }

            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register a new patient (mobile app specific).
     * 
     * This endpoint is designed for mobile app patient self-registration.
     * 
     * ACCOUNT ACTIVATION:
     * - Mobile app registrations are AUTOMATICALLY ACTIVATED upon registration
     * - Account status is set to 'active' immediately
     * - No admin approval is required for mobile app registrations
     * - Patient can log in immediately after registration
     * 
     * REQUIRED FIELDS:
     * - first_name: Patient's first name
     * - last_name: Patient's last name
     * - email: Unique email address (will be checked against users table)
     * - password: Minimum 8 characters, must be confirmed
     * - password_confirmation: Must match password
     * - phone: Contact phone number
     * - gender: Must be 'Male' or 'Female'
     * - branch_id: Active branch ID the patient is registering under
     *
     * OPTIONAL FIELDS (can be omitted from mobile UI):
     * - other_names: Middle name(s)
     * - date_of_birth: Date of birth (YYYY-MM-DD format)
     * - age: Age in years (used if date_of_birth not provided)
     * - address: Physical address
     * - nhis_number: National Health Insurance Scheme number
     * - ghana_card_number: Ghana Card identification number
     * - emergency_contact_name: Name of emergency contact person
     * - emergency_contact_phone: Phone number of emergency contact
     * - emergency_contact_relationship: Relationship to patient (e.g., "Spouse", "Parent")
     * 
     * NOTE: The following fields are intentionally optional for mobile app registration
     * as they may not be collected in the initial registration flow:
     * - nhis_number, ghana_card_number, emergency_contact_name, emergency_contact_phone, 
     *   emergency_contact_relationship
     * These can be added later through patient profile update.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function registerPatient(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                // Required fields
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string|min:8',
                'phone' => 'required|string|max:20',
                'gender' => 'required|in:Male,Female',
                'branch_id' => [
                    'required',
                    'integer',
                    Rule::exists('branches', 'id')->where('is_active', true),
                ],
                
                // Optional fields
                'other_names' => 'nullable|string|max:255',
                'date_of_birth' => 'nullable|date|before:today',
                'age' => 'nullable|integer|min:0|max:150',
                'address' => 'nullable|string|max:500',
                
                // Optional fields - can be omitted from mobile UI
                'nhis_number' => 'nullable|string|max:50',
                'ghana_card_number' => 'nullable|string|max:50',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:20',
                'emergency_contact_relationship' => 'nullable|string|max:255',
                
                // Backward compatibility - accept emergency_contact as emergency_contact_phone
                'emergency_contact' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            // CRITICAL: Check for duplicate patients BEFORE creating user account
            // This prevents unnecessary user creation and permission cache clearing
            // Handle date of birth - use age if provided, otherwise use date_of_birth
            $dateOfBirth = null;
            if ($request->filled('date_of_birth')) {
                $dateOfBirth = $request->date_of_birth;
            } elseif ($request->filled('age')) {
                $age = (int) $request->age;
                // Calculate approximate date of birth (using January 1st as default)
                $dateOfBirth = now()->subYears($age)->startOfYear()->format('Y-m-d');
            }

            // Prepare patient data for duplicate checking
            $patientDataForCheck = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'nhis_number' => $request->nhis_number ?? null,
                'date_of_birth' => $dateOfBirth,
            ];
            
            // Check for duplicate patients BEFORE creating user account
            // For mobile app, email and phone are required, so we should always check
            $duplicateService = app(PatientDuplicateService::class);
            $branchId = (int) $request->branch_id;
            $duplicateCheck = $duplicateService->checkForDuplicates($patientDataForCheck, null, $branchId);
            
            if ($duplicateCheck['is_duplicate']) {
                // Format matches for API response
                $formattedMatches = $duplicateService->formatMatchesForResponse($duplicateCheck['matches']);
                
                // Build error message
                $errorMessage = 'An account with this email, phone number, or similar information already exists. ';
                
                if ($duplicateCheck['has_high_confidence_match']) {
                    $errorMessage .= 'Please use the existing account or contact support if you believe this is an error.';
                } else {
                    $errorMessage .= 'Please review potential matches.';
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'duplicate_patients' => $formattedMatches,
                    'duplicate_count' => $duplicateCheck['count'],
                    'has_high_confidence_match' => $duplicateCheck['has_high_confidence_match']
                ], 409)->header('Access-Control-Allow-Origin', '*')
                  ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                  ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            }

            DB::beginTransaction();

            try {
                // Create user account (only if no duplicates found)
                $fullName = trim($request->first_name . ' ' . $request->last_name);
                
                $user = User::create([
                    'name' => $fullName,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);

                // Assign patient role
                $user->assignRole('patient');
                
                // CRITICAL: Clear permission cache immediately to ensure changes take effect
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
                
                // Refresh the user's permission cache
                $user->load('roles.permissions', 'permissions');

                // Create patient record with ID prefix
                // NOTE: Optional fields (nhis_number, ghana_card_number, emergency_contact_*) 
                // are set to null if not provided - this allows mobile app to register 
                // without collecting these fields initially. These can be added later via profile update.
                $patientData = [
                    'user_id' => $user->id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'other_names' => $request->other_names ?? null,
                    'gender' => $request->gender,
                    'date_of_birth' => $dateOfBirth,
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'address' => $request->address ?? null,
                    // Optional fields - can be null for mobile app registration
                    'nhis_number' => $request->nhis_number ?? null,
                    'ghana_card_number' => $request->ghana_card_number ?? null,
                    'emergency_contact_name' => $request->emergency_contact_name ?? null,
                    'emergency_contact_phone' => $request->emergency_contact_phone ?? $request->emergency_contact ?? null,
                    'emergency_contact_relationship' => $request->emergency_contact_relationship ?? null,
                    'password' => Hash::make($request->password),
                    'account_status' => 'active', // Auto-activate for mobile app registrations
                    'account_activated_at' => now(), // Set activation timestamp
                    'activated_by' => null, // Auto-activated (no admin approval required)
                    'branch_id' => $branchId,
                    'registration_source' => 'mobile_app', // Tag as registered from mobile app
                    'created_by' => $user->id, // Self-created
                ];

                $patient = \App\Models\Patient::create($patientData);

                // Registration fee is NOT applied to patients who sign up via the mobile app.
                // It applies only to patients created via web (reception, admin, etc.).

                // Generate token
                $token = $user->createToken('auth-token')->plainTextToken;

                // Load user with patient data
                $userWithPatient = $user->load(['patient', 'roles.permissions', 'permissions']);
                
                // Transform user data to match frontend expectations
                $userData = [
                    'id' => $userWithPatient->id,
                    'username' => $userWithPatient->email,
                    'first_name' => $userWithPatient->first_name ?? $request->first_name,
                    'last_name' => $userWithPatient->last_name ?? $request->last_name,
                    'email' => $userWithPatient->email,
                    'roles' => $userWithPatient->roles->map(function($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'permissions' => $role->permissions->map(function($permission) {
                                return [
                                    'id' => $permission->id,
                                    'name' => $permission->name,
                                    'created_at' => $permission->created_at,
                                    'updated_at' => $permission->updated_at,
                                ];
                            }),
                            'created_at' => $role->created_at,
                            'updated_at' => $role->updated_at,
                        ];
                    }),
                    'permissions' => $userWithPatient->getAllPermissions()->map(function($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'created_at' => $permission->created_at,
                            'updated_at' => $permission->updated_at,
                        ];
                    }),
                    'branch_id' => $patient->branch_id,
                    'profile_image' => null,
                    'is_active' => true,
                    'patient_id' => $patient->id,
                    'patient_data' => $patient,
                    'patient_number' => $patient->patient_number,
                ];

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'user' => $userData,
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                        'expires_in' => 3600
                    ],
                    'message' => 'Patient registration successful. Patient number: ' . $patient->patient_number
                ], 201)->header('Access-Control-Allow-Origin', '*')
                  ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                  ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Patient registration failed: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        }
    }

    /**
     * Get authenticated user with patient data (for mobile app).
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Load patient relationship if user is a patient
            if ($user->hasRole('patient')) {
                $user->load(['patient', 'roles.permissions', 'permissions']);
                
                $patient = $user->patient;
                
                $userData = [
                    'id' => $user->id,
                    'username' => $user->email,
                    'first_name' => $user->first_name ?? explode(' ', $user->name)[0] ?? '',
                    'last_name' => $user->last_name ?? explode(' ', $user->name)[1] ?? '',
                    'email' => $user->email,
                    'roles' => $user->roles->map(function($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'permissions' => $role->permissions->pluck('name'),
                        ];
                    }),
                    'branch_id' => $patient?->branch_id,
                    'profile_image' => null,
                    'is_active' => true,
                    // Include patient data for mobile app
                    'patient_id' => $patient?->id,
                    'patient_data' => $patient ? [
                        'id' => $patient->id,
                        'patient_number' => $patient->patient_number,
                        'first_name' => $patient->first_name,
                        'last_name' => $patient->last_name,
                        'phone' => $patient->phone,
                        'email' => $patient->email,
                        'gender' => $patient->gender,
                        'date_of_birth' => $patient->date_of_birth,
                        'address' => $patient->address,
                        'branch_id' => $patient->branch_id,
                    ] : null,
                    'patient_number' => $patient?->patient_number,
                ];
            } else {
                // For staff users
                $user->load(['staffProfile', 'roles.permissions', 'permissions']);
                
                $userData = [
                    'id' => $user->id,
                    'username' => $user->email,
                    'first_name' => $user->staffProfile?->first_name ?? explode(' ', $user->name)[0] ?? '',
                    'last_name' => $user->staffProfile?->last_name ?? explode(' ', $user->name)[1] ?? '',
                    'email' => $user->email,
                    'roles' => $user->roles->map(function($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'permissions' => $role->permissions->pluck('name'),
                        ];
                    }),
                    'branch_id' => $user->staffProfile?->branch_id,
                    'profile_image' => $user->staffProfile?->photo,
                    'is_active' => $user->is_active ?? true,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $userData,
                'message' => 'User retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get authenticated user (alias for me).
     */
    public function user(Request $request): JsonResponse
    {
        return $this->me($request);
    }
    
    /**
     * Send password reset OTP to user's email/phone
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate 6-digit OTP
            $otp = rand(100000, 999999);
            
            // Store OTP in password_reset_tokens table
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($otp),
                    'created_at' => now()
                ]
            );

            // Send OTP via email when mail is configured; log in local/dev for troubleshooting
            $user = User::where('email', $request->email)->first();
            $subject = config('app.name') . ' — Password Reset Code';
            $body = "Your password reset code is: {$otp}\n\nThis code expires in 15 minutes.";

            try {
                if (config('mail.default') && config('mail.default') !== 'log') {
                    Mail::raw($body, function ($message) use ($request, $subject) {
                        $message->to($request->email)->subject($subject);
                    });
                } else {
                    Log::info('Password reset OTP generated', [
                        'email' => $request->email,
                        'user_id' => $user?->id,
                        'otp' => config('app.debug') ? $otp : '[redacted]',
                    ]);
                }
            } catch (\Throwable $mailError) {
                Log::warning('Failed to send password reset OTP email', [
                    'email' => $request->email,
                    'error' => $mailError->getMessage(),
                    'otp' => config('app.debug') ? $otp : '[redacted]',
                ]);
            }

            $response = [
                'success' => true,
                'message' => 'Password reset OTP sent to your email',
            ];

            if (config('app.debug') && config('mail.default') === 'log') {
                $response['otp'] = $otp;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending reset code: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Reset password using OTP
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'otp' => 'required|string|size:6',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get reset token
            $resetToken = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$resetToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset code'
                ], 400);
            }

            // Check if token is expired (60 minutes)
            if (now()->diffInMinutes($resetToken->created_at) > 60) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reset code has expired. Please request a new one'
                ], 400);
            }

            // Verify OTP
            if (!Hash::check($request->otp, $resetToken->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid reset code'
                ], 400);
            }

            // Update password
            $user = User::where('email', $request->email)->first();
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            // Delete used token
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error resetting password: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function recordLoginAudit(Request $request, ?User $user, string $status, ?string $failureReason = null): void
    {
        if (!Schema::hasTable('login_audit')) {
            return;
        }

        try {
            LoginAudit::create([
                'user_id' => $user?->id,
                'email' => $request->email ?? $user?->email,
                'action' => 'login',
                'status' => $status,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device' => $request->header('X-Device-Platform'),
                'failure_reason' => $failureReason,
                'logged_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record login audit', ['error' => $e->getMessage()]);
        }
    }
}