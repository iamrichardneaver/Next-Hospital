<?php

namespace App\Services;

use App\Models\User;
use App\Models\Patient;
use App\Services\BranchAssignmentService;
use App\Services\RegistrationFeeService;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService
{
    /**
     * Create a user with appropriate ID generation based on role
     */
    public function createUser(array $userData, array $roles = [])
    {
        return DB::transaction(function () use ($userData, $roles) {
            // Hash password if provided
            if (isset($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            }

            // Create the user
            $user = User::create($userData);

            // Assign roles
            if (!empty($roles)) {
                $user->assignRole($roles);
                
                // CRITICAL: Clear permission cache immediately to ensure changes take effect
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
                
                // Refresh the user's permission cache
                $user->load('roles.permissions', 'permissions');
            }
            
            $branchService = app(BranchAssignmentService::class);
            if ($branchService->isStaffRole($roles)) {
                $branchService->assignUserToBranch($user, $userData['branch_id'] ?? null, [
                    'first_name' => $userData['first_name'] ?? $user->first_name,
                    'last_name' => $userData['last_name'] ?? $user->last_name,
                    'contact' => $userData['email'] ?? $user->email,
                    'phone' => $userData['phone'] ?? $user->phone,
                ]);
            }

            // If user is a patient, create patient record and link
            if (in_array('patient', $roles)) {
                $this->createPatientRecord($user, $userData);
            }

            return $user;
        });
    }

    /**
     * Create a patient record and link it to the user
     */
    public function createPatientRecord(User $user, array $userData)
    {
        $patientData = [
            'user_id' => $user->id,
            'first_name' => $userData['first_name'] ?? $user->first_name,
            'last_name' => $userData['last_name'] ?? $user->last_name,
            'other_names' => $userData['other_names'] ?? $user->name,
            'email' => $user->email,
            'phone' => $userData['phone'] ?? $user->phone,
            'gender' => $userData['gender'] ?? 'Male',
            'date_of_birth' => $userData['date_of_birth'] ?? now()->subYears(25),
            'address' => $userData['address'] ?? '',
            'branch_id' => $userData['branch_id'] ?? app(BranchAssignmentService::class)->resolvePatientBranchId(),
            'created_by' => auth()->id(),
        ];

        $patient = Patient::create($patientData);
        try {
            app(RegistrationFeeService::class)->createInvoiceForPatient($patient, $patient->branch_id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Registration fee invoice creation failed for new patient', ['patient_id' => $patient->id, 'error' => $e->getMessage()]);
        }
        return $patient;
    }

    /**
     * Update user and sync patient record if needed
     */
    public function updateUser(User $user, array $userData, array $roles = [])
    {
        return DB::transaction(function () use ($user, $userData, $roles) {
            // Update user data
            if (isset($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            }

            $user->update($userData);

            // Update roles if provided
            if (!empty($roles)) {
                $user->syncRoles($roles);
                
                // CRITICAL: Clear permission cache immediately to ensure changes take effect
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
                
                // Refresh the user's permission cache
                $user->load('roles.permissions', 'permissions');
            }

            // Update patient record if user is a patient
            if ($user->isPatient() && $user->patient) {
                $this->updatePatientRecord($user, $userData);
            }

            return $user;
        });
    }

    /**
     * Update patient record
     */
    public function updatePatientRecord(User $user, array $userData)
    {
        if (!$user->patient) {
            return $this->createPatientRecord($user, $userData);
        }

        $patientData = [
            'first_name' => $userData['first_name'] ?? $user->first_name,
            'last_name' => $userData['last_name'] ?? $user->last_name,
            'other_names' => $userData['other_names'] ?? $user->name,
            'email' => $user->email,
            'phone' => $userData['phone'] ?? $user->phone,
            'gender' => $userData['gender'] ?? $user->patient->gender,
            'date_of_birth' => $userData['date_of_birth'] ?? $user->patient->date_of_birth,
            'address' => $userData['address'] ?? $user->patient->address,
            'updated_by' => auth()->id(),
        ];

        return $user->patient->update($patientData);
    }

    /**
     * Get user's reference ID based on their role
     */
    public function getUserReferenceId(User $user)
    {
        if ($user->isStaff()) {
            return $user->staff_id;
        } elseif ($user->isPatient()) {
            return $user->patient?->patient_number;
        }

        return null;
    }

    /**
     * Get user's display name with reference ID
     */
    public function getUserDisplayName(User $user)
    {
        $name = $user->first_name . ' ' . $user->last_name;
        $referenceId = $this->getUserReferenceId($user);
        
        if ($referenceId) {
            $name .= " ({$referenceId})";
        }

        return $name;
    }

    /**
     * Check if user can be assigned a specific role
     */
    public function canAssignRole(User $user, string $role)
    {
        // Prevent changing from staff to patient or vice versa if they have records
        if ($user->isStaff() && $role === 'patient') {
            return !$this->hasStaffRecords($user);
        }

        if ($user->isPatient() && in_array($role, ['admin', 'doctor', 'nurse', 'pharmacist', 'lab_technician', 'receptionist', 'accountant', 'super_admin'])) {
            return !$this->hasPatientRecords($user);
        }

        return true;
    }

    /**
     * Check if user has staff-related records
     */
    private function hasStaffRecords(User $user)
    {
        return $user->consultations()->exists() ||
               $user->appointments()->exists() ||
               $user->labRequests()->exists() ||
               $user->prescriptions()->exists();
    }

    /**
     * Check if user has patient-related records
     */
    private function hasPatientRecords(User $user)
    {
        return $user->patient && (
            $user->patient->appointments()->exists() ||
            $user->patient->consultations()->exists() ||
            $user->patient->labRequests()->exists() ||
            $user->patient->prescriptions()->exists()
        );
    }
}