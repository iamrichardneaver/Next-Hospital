<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\Patient;
use App\Models\Branch;
use App\Services\PatientDuplicateService;
use App\Services\PatientPortalAccountService;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        
        return view('auth.login');
    }
    
    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
        
        $remember = $request->filled('remember');
        
        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            $user = Auth::user();

            if ($user->hasRole('patient')) {
                $user->loadMissing('patient');
                $patient = $user->patient;

                if (!$patient) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()
                        ->withErrors(['email' => 'Your patient profile is not linked. Please contact the hospital.'])
                        ->onlyInput('email');
                }

                if ($patient->account_status === 'pending') {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()
                        ->withErrors(['email' => 'Your account is pending approval. You will be notified when it is activated.'])
                        ->onlyInput('email');
                }

                if (!$user->is_active) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()
                        ->withErrors(['email' => 'Your account is inactive. Please contact the hospital.'])
                        ->onlyInput('email');
                }
            }

            // CRITICAL: Eager load permissions during login to improve performance
            // This ensures permissions are available immediately without additional queries
            $user->loadMissing(['permissions', 'roles.permissions']);
            
            // TODO: Add activity logging if Spatie package is installed
            // activity()->causedBy(Auth::user())->log('User logged in');
            
            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome back, ' . $user->name . '!');
        }
        
        return back()
            ->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])
            ->onlyInput('email');
    }
    
    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        // TODO: Add activity logging if Spatie package is installed
        // activity()->causedBy(Auth::user())->log('User logged out');
        
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
    
    /**
     * Show password reset request form
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }
    
    /**
     * Send password reset link
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);
        
        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );
        
        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
    
    /**
     * Show registration form (for patient self-registration)
     */
    public function showRegister()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('auth.register', compact('branches'));
    }
    
    /**
     * Handle patient registration request
     */
    public function register(Request $request)
    {
        // Validate the registration data
        $validated = $request->validate([
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'other_names' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'in:Male,Female'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:patients,email'],
            'address' => ['nullable', 'string'],
            'nhis_number' => ['nullable', 'string', 'max:50'],
            'ghana_card_number' => ['nullable', 'string', 'max:50'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['required', 'accepted'],
        ]);

        $branchId = (int) $validated['branch_id'];

        // Handle date of birth - use age if provided, otherwise use date_of_birth
        $dateOfBirth = null;
        if (!empty($validated['date_of_birth'])) {
            $dateOfBirth = $validated['date_of_birth'];
        } elseif (!empty($validated['age'])) {
            $age = (int) $validated['age'];
            $dateOfBirth = now()->subYears($age)->startOfYear()->format('Y-m-d');
        }

        $patientDataForCheck = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'nhis_number' => $validated['nhis_number'] ?? null,
            'date_of_birth' => $dateOfBirth,
        ];

        $duplicateService = app(PatientDuplicateService::class);
        $duplicateCheck = $duplicateService->checkForDuplicates($patientDataForCheck, null, $branchId);

        if ($duplicateCheck['is_duplicate']) {
            $errorMessage = 'An account with this email, phone number, or similar information already exists. ';
            $errorMessage .= $duplicateCheck['has_high_confidence_match']
                ? 'Please use the existing account or contact support if you believe this is an error.'
                : 'Please review potential matches or contact support.';

            return back()
                ->withInput()
                ->with('error', $errorMessage);
        }
        
        try {
            DB::beginTransaction();
            
            // Create the patient record with pending status (patient-only; portal User provisioned below)
            $patient = Patient::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'other_names' => $validated['other_names'] ?? null,
                'gender' => $validated['gender'],
                'date_of_birth' => $dateOfBirth,
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'address' => $validated['address'] ?? null,
                'nhis_number' => $validated['nhis_number'] ?? null,
                'ghana_card_number' => $validated['ghana_card_number'] ?? null,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                'emergency_contact_relationship' => $validated['emergency_contact_relationship'] ?? null,
                'password' => Hash::make($validated['password']),
                'account_status' => 'pending', // Set to pending for approval
                'branch_id' => $branchId,
                'registration_source' => 'web', // Tag as registered from web
                'created_by' => 1, // Temporary - self-registration
            ]);

            app(PatientPortalAccountService::class)->ensurePortalUserForPatient($patient);

            try {
                app(\App\Services\RegistrationFeeService::class)->createInvoiceForPatient($patient, $patient->branch_id);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Registration fee invoice creation failed for new patient', ['patient_id' => $patient->id, 'error' => $e->getMessage()]);
            }
            
            DB::commit();
            
            return redirect()->route('login')
                ->with('success', 'Registration successful! Your account is pending approval. You will receive an email and SMS notification once your account is activated.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()
                ->withInput()
                ->with('error', 'Registration failed. Please try again. Error: ' . $e->getMessage());
        }
    }
}
