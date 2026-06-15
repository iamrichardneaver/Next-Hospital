<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class PatientPortalAccountService
{
    /**
     * Ensure the patient has a linked portal User account.
     * Returns credentials only when a new account is created.
     */
    public function ensurePortalUserForPatient(Patient $patient): array
    {
        return DB::transaction(function () use ($patient) {
            $patient->refresh();

            if ($patient->user_id) {
                $user = $patient->user;
                if ($user) {
                    $this->ensurePatientRoles($user);

                    return [
                        'created' => false,
                        'user' => $user,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'password' => null,
                        'message' => 'Patient already has portal access.',
                    ];
                }
            }

            $plainPassword = Str::password(12);
            $email = $this->resolveUniqueEmail($patient);

            $user = User::create([
                'name' => trim($patient->first_name . ' ' . $patient->last_name),
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'email' => $email,
                'phone' => $patient->phone,
                'password' => Hash::make($plainPassword),
                'is_active' => true,
            ]);

            $this->assignPatientRoles($user);

            $patient->update([
                'user_id' => $user->id,
                'email' => $patient->email ?: $email,
                'account_status' => $patient->account_status === 'pending' ? 'pending' : 'active',
                'account_activated_at' => $patient->account_activated_at ?? now(),
                'updated_by' => Auth::id(),
            ]);

            $this->logActivity($patient, 'created', 'Portal account created for patient ' . $patient->full_name, [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return [
                'created' => true,
                'user' => $user,
                'email' => $user->email,
                'phone' => $user->phone,
                'password' => $plainPassword,
                'message' => 'Portal account created successfully.',
            ];
        });
    }

    /**
     * Reset portal password for a linked patient user. Plaintext returned once.
     */
    public function resetPortalPassword(Patient $patient): array
    {
        return DB::transaction(function () use ($patient) {
            $patient->refresh();

            if (!$patient->user_id || !$patient->user) {
                $result = $this->ensurePortalUserForPatient($patient);
                if ($result['created']) {
                    return array_merge($result, ['reset' => true]);
                }

                throw new \RuntimeException('Patient has no linked portal account.');
            }

            $user = $patient->user;
            $plainPassword = Str::password(12);

            $user->update([
                'password' => Hash::make($plainPassword),
            ]);

            $this->logActivity($patient, 'updated', 'Portal password reset for patient ' . $patient->full_name, [
                'user_id' => $user->id,
            ]);

            return [
                'created' => false,
                'reset' => true,
                'user' => $user,
                'email' => $user->email,
                'phone' => $user->phone,
                'password' => $plainPassword,
                'message' => 'Portal password reset successfully.',
            ];
        });
    }

    private function resolveUniqueEmail(Patient $patient): string
    {
        if ($patient->email && !User::where('email', $patient->email)->exists()) {
            return $patient->email;
        }

        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '.', trim($patient->patient_number, './')));
        $base = $base ?: 'patient' . $patient->id;
        $email = $base . '@patient.nexthospital.local';

        $suffix = 0;
        while (User::where('email', $email)->exists()) {
            $suffix++;
            $email = $base . '.' . $suffix . '@patient.nexthospital.local';
        }

        return $email;
    }

    private function assignPatientRoles(User $user): void
    {
        $roleIds = Role::where('name', 'patient')->pluck('id');

        foreach ($roleIds as $roleId) {
            $exists = DB::table('model_has_roles')
                ->where('role_id', $roleId)
                ->where('model_type', User::class)
                ->where('model_id', $user->id)
                ->exists();

            if (!$exists) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $roleId,
                    'model_type' => User::class,
                    'model_id' => $user->id,
                ]);
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $user->load('roles.permissions', 'permissions');
    }

    private function ensurePatientRoles(User $user): void
    {
        $this->assignPatientRoles($user);
    }

    private function logActivity(Patient $patient, string $event, string $description, array $properties = []): void
    {
        ActivityLog::create([
            'log_name' => 'patient_portal',
            'description' => $description,
            'subject_type' => Patient::class,
            'subject_id' => $patient->id,
            'causer_type' => User::class,
            'causer_id' => Auth::id(),
            'event' => $event,
            'properties' => $properties,
        ]);
    }
}
