<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\FacilityUser;
use App\Models\Patient;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class BranchAssignmentService
{
    public const STAFF_ROLES = [
        'admin',
        'doctor',
        'nurse',
        'pharmacist',
        'lab_technician',
        'lab_scientist',
        'lab_manager',
        'receptionist',
        'accountant',
        'super_admin',
        'radiologist',
        'cashier',
    ];

    public const CLINICAL_ROLES = [
        'doctor',
        'nurse',
        'receptionist',
        'pharmacist',
        'lab_technician',
        'radiologist',
    ];

    public function isStaffRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return !empty(array_intersect($roles, self::STAFF_ROLES));
    }

    public function isClinicalRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return !empty(array_intersect($roles, self::CLINICAL_ROLES));
    }

    /**
     * Resolve branch ID using fallback order:
     * 1. Explicit branchId argument
     * 2. Session selected branch (user_branch_id)
     * 3. Context user's staff profile / facility_users branch
     * 4. Default active branch (MAIN code or first active)
     * 5. Primary clinical branch (branch with most patients) for clinical roles
     */
    public function resolveBranchId(
        int|string|null $branchId = null,
        ?User $contextUser = null,
        string|array|null $roles = null
    ): int|string {
        if ($branchId && Branch::where('id', $branchId)->where('is_active', true)->exists()) {
            return $branchId;
        }

        $sessionBranchId = session('user_branch_id');
        if ($sessionBranchId && Branch::where('id', $sessionBranchId)->where('is_active', true)->exists()) {
            return $sessionBranchId;
        }

        $contextUser ??= auth()->user();
        if ($contextUser) {
            $fromProfile = $contextUser->staffProfile?->branch_id;
            if ($fromProfile && Branch::where('id', $fromProfile)->where('is_active', true)->exists()) {
                return $fromProfile;
            }

            $fromFacility = $contextUser->branches()->where('branches.is_active', true)->value('branches.id');
            if ($fromFacility) {
                return $fromFacility;
            }
        }

        if ($roles && $this->isClinicalRole($roles)) {
            $clinicalBranchId = Branch::getPrimaryClinicalBranchId();
            if ($clinicalBranchId) {
                return $clinicalBranchId;
            }
        }

        $defaultBranch = Branch::getDefault();
        if ($defaultBranch) {
            return $defaultBranch->id;
        }

        $anyActive = Branch::where('is_active', true)->orderBy('id')->value('id');
        if ($anyActive) {
            return $anyActive;
        }

        throw new \RuntimeException('No active branch found. Cannot assign user to a branch.');
    }

    /**
     * Assign a staff user to a branch: upsert facility_users, staff profile, and current_branch_id.
     *
     * @return int The branch ID that was assigned
     */
    public function assignUserToBranch(User $user, int|string|null $branchId = null, array $staffProfileData = []): int|string
    {
        $roles = $user->roles->pluck('name')->all();
        if (!$this->isStaffRole($roles)) {
            throw new \InvalidArgumentException('Branch assignment applies to staff users only.');
        }

        $resolvedBranchId = $this->resolveBranchId(
            $branchId ?? $staffProfileData['branch_id'] ?? null,
            auth()->user(),
            $roles
        );

        $this->upsertFacilityUser($user->id, $resolvedBranchId);
        $this->ensureStaffProfile($user, $resolvedBranchId, $staffProfileData);
        $this->setCurrentBranchId($user, $resolvedBranchId);

        return $resolvedBranchId;
    }

    /**
     * Ensure staff profile exists and facility_users row matches for existing users (seeders/fixes).
     */
    public function syncStaffBranch(User $user, int|string|null $branchId = null): int|string
    {
        $roles = $user->roles->pluck('name')->all();
        if (!$this->isStaffRole($roles)) {
            throw new \InvalidArgumentException('Branch sync applies to staff users only.');
        }

        $resolvedBranchId = $branchId
            ?? $user->staffProfile?->branch_id
            ?? $this->resolveBranchId(null, null, $roles);

        $this->upsertFacilityUser($user->id, $resolvedBranchId);

        if ($user->staffProfile) {
            if ((int) $user->staffProfile->branch_id !== $resolvedBranchId) {
                $user->staffProfile->update(['branch_id' => $resolvedBranchId]);
            }
        } else {
            $this->ensureStaffProfile($user, $resolvedBranchId);
        }

        $this->setCurrentBranchId($user, $resolvedBranchId);

        return $resolvedBranchId;
    }

    /**
     * Resolve patient branch from registration context (patient record, session, or default).
     */
    public function resolvePatientBranchId(int|string|null $branchId = null, ?Patient $patient = null): int|string
    {
        if ($branchId && Branch::where('id', $branchId)->where('is_active', true)->exists()) {
            return $branchId;
        }

        if ($patient?->branch_id) {
            return $patient->branch_id;
        }

        return $this->resolveBranchId($branchId);
    }

    private function upsertFacilityUser(int $userId, int|string $branchId): void
    {
        FacilityUser::updateOrCreate(
            ['user_id' => $userId, 'branch_id' => $branchId],
            ['is_active' => true]
        );
    }

    private function ensureStaffProfile(User $user, int|string $branchId, array $extra = []): StaffProfile
    {
        $profile = $user->staffProfile;

        $defaults = [
            'employee_id' => 'EMP' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
            'first_name' => $user->first_name ?? explode(' ', $user->name)[0] ?? '',
            'last_name' => $user->last_name ?? explode(' ', $user->name)[1] ?? '',
            'contact' => $user->email,
            'phone' => $user->phone,
            'branch_id' => $branchId,
            'is_active' => true,
        ];

        $data = array_merge($defaults, $extra, ['branch_id' => $branchId]);

        if ($profile) {
            $profile->fill($data);
            $profile->save();

            return $profile;
        }

        return $user->staffProfile()->create($data);
    }

    private function setCurrentBranchId(User $user, int|string $branchId): void
    {
        if (!Schema::hasColumn('users', 'current_branch_id')) {
            return;
        }

        if ((string) ($user->current_branch_id ?? '') !== (string) $branchId) {
            $user->update(['current_branch_id' => $branchId]);
        }
    }
}
