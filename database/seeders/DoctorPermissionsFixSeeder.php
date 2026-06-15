<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\LabRequest;
use App\Models\Patient;
use App\Models\User;
use App\Services\BranchAssignmentService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DoctorPermissionsFixSeeder extends Seeder
{
    /**
     * Idempotent fix for doctor (and clinical staff) permissions and branch assignments.
     */
    public function run(): void
    {
        $this->command?->info('Fixing doctor permissions and branch assignments...');

        $this->syncStaffBranchAssignments();
        $this->syncDemoClinicalBranch();
        $this->syncDoctorPermissions();
        $this->ensureDemoDoctorRole();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('Doctor permissions fix completed.');
    }

    private function syncStaffBranchAssignments(): void
    {
        $branchService = app(BranchAssignmentService::class);
        $clinicalRoles = BranchAssignmentService::CLINICAL_ROLES;

        $users = User::whereHas('roles', fn ($q) => $q->whereIn('name', $clinicalRoles))
            ->with(['staffProfile', 'branches', 'roles'])
            ->get();

        foreach ($users as $user) {
            if ($user->branches()->exists() && $user->staffProfile?->branch_id) {
                continue;
            }

            $branchId = $branchService->syncStaffBranch($user);
            $this->command?->info("  Synced branch {$branchId} for {$user->email}");
        }
    }

    private function syncDoctorPermissions(): void
    {
        $doctor = Role::where('name', 'doctor')->first();
        if (!$doctor) {
            $this->command?->error('Doctor role not found.');
            return;
        }

        $doctorPermissions = RefineDoctorPermissionsSeeder::doctorPermissionNames();

        $existing = Permission::whereIn('name', $doctorPermissions)->pluck('name')->all();
        $missing = array_diff($doctorPermissions, $existing);
        if (!empty($missing)) {
            $this->command?->warn('  Missing permission records (skipped): ' . implode(', ', $missing));
            $doctorPermissions = array_intersect($doctorPermissions, $existing);
        }

        $permissions = Permission::whereIn('name', $doctorPermissions)->get();
        $doctor->syncPermissions($permissions);

        $this->command?->info('  Doctor role synced with ' . $permissions->count() . ' permissions');
    }

    /**
     * Align demo clinical staff and eligible doctors with the branch that holds active patient/lab data.
     */
    private function syncDemoClinicalBranch(): void
    {
        $primaryBranchId = $this->resolvePrimaryClinicalBranchId();
        if (!$primaryBranchId) {
            return;
        }

        $demoEmails = [
            'doctor@nexthospital.com',
            'nurse@nexthospital.com',
            'pharmacist@nexthospital.com',
            'receptionist@nexthospital.com',
            'lab@nexthospital.com',
        ];

        foreach ($demoEmails as $email) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                continue;
            }

            $this->syncUserToPrimaryBranch($user, $primaryBranchId, "demo clinical ({$email})");
        }

        $doctors = User::role('doctor', 'web')->with(['staffProfile', 'branches'])->get();

        foreach ($doctors as $user) {
            if (in_array($user->email, $demoEmails, true)) {
                continue;
            }

            if ($user->branches()->count() > 1) {
                $this->command?->line("  Skipped multi-branch doctor {$user->email}");
                continue;
            }

            $currentBranchId = (int) ($user->staffProfile?->branch_id ?? $user->branches()->first()?->id ?? 0);
            if ($currentBranchId === (int) $primaryBranchId) {
                continue;
            }

            $hasNoFacilityUser = !$user->branches()->exists();
            $isDemoClinical = $this->isDemoClinicalEmail($user->email);

            if (!$hasNoFacilityUser && !$isDemoClinical) {
                $this->command?->line(
                    "  Skipped doctor {$user->email} (branch {$currentBranchId}, not demo/unassigned)"
                );
                continue;
            }

            $this->syncUserToPrimaryBranch($user, $primaryBranchId, 'doctor clinical branch alignment');
        }
    }

    private function resolvePrimaryClinicalBranchId(): int|string|null
    {
        $primaryBranchId = Branch::getPrimaryClinicalBranchId();

        if (!$primaryBranchId) {
            $primaryBranchId = LabRequest::query()
                ->selectRaw('branch_id, COUNT(*) as total')
                ->groupBy('branch_id')
                ->orderByDesc('total')
                ->value('branch_id');
        }

        return $primaryBranchId;
    }

    private function isDemoClinicalEmail(string $email): bool
    {
        return str_ends_with(strtolower($email), '@nexthospital.com');
    }

    private function syncUserToPrimaryBranch(User $user, int|string $primaryBranchId, string $context): void
    {
        $currentBranchId = $user->staffProfile?->branch_id ?? $user->branches()->first()?->id;
        if ((string) $currentBranchId === (string) $primaryBranchId && $user->branches()->count() === 1) {
            return;
        }

        app(BranchAssignmentService::class)->syncStaffBranch($user, $primaryBranchId);

        $this->command?->info("  Synced {$user->email} to primary clinical branch {$primaryBranchId} ({$context})");
    }

    private function ensureDemoDoctorRole(): void
    {
        $user = User::where('email', 'doctor@nexthospital.com')->first();
        if (!$user) {
            return;
        }

        if (!$user->hasRole('doctor')) {
            $user->assignRole('doctor');
            $this->command?->info('  Assigned doctor role to doctor@nexthospital.com');
        }

        if (!$user->branches()->exists() || !$user->staffProfile?->branch_id) {
            $branchId = app(BranchAssignmentService::class)->syncStaffBranch($user);
            $this->command?->info('  Attached demo doctor to branch ' . $branchId);
        }
    }
}
