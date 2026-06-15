<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\BranchAssignmentService;
use App\Services\PermissionSyncService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RefineLabScientistPermissionsSeeder extends Seeder
{
    /**
     * Sync lab_scientist permissions to match lab_manager (full laboratory access).
     *
     * Does not modify lab_technician (lab tech) permissions.
     */
    public function run(): void
    {
        $this->command?->info('Refining lab scientist permissions (mirror lab_manager)...');

        app(PermissionSyncService::class)->sync();

        $labScientist = Role::firstOrCreate(['name' => 'lab_scientist', 'guard_name' => 'web']);
        $permissions = Permission::whereIn('name', self::labScientistPermissionNames())->get();
        $missing = array_diff(self::labScientistPermissionNames(), $permissions->pluck('name')->all());
        if ($missing !== []) {
            $this->command?->warn('Missing permission records: ' . implode(', ', $missing));
        }

        $labScientist->syncPermissions($permissions);

        $labManager = Role::where('name', 'lab_manager')->first();
        if ($labManager) {
            $labManager->syncPermissions($permissions);
            $this->command?->info('Lab Manager permissions synced to match lab_scientist');
        }

        $this->grantAdminLabInventoryPermissions();
        $this->syncLabScientistBranchAssignments();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $labManager = Role::where('name', 'lab_manager')->first();
        $managerCount = $labManager?->permissions->count() ?? 0;

        $this->command?->info('Lab Scientist permissions refined: ' . $labScientist->permissions->count() . ' total');
        $this->command?->info('Lab Manager permissions (unchanged): ' . $managerCount);
        foreach ($labScientist->permissions->sortBy('name') as $perm) {
            $this->command?->line("  • {$perm->name}");
        }
    }

    /**
     * Canonical lab scientist permission list — identical to lab_manager full access.
     *
     * @return list<string>
     */
    public static function labScientistPermissionNames(): array
    {
        return [
            'view_dashboard',

            // Patients & billing context for walk-in lab visits
            'view_patients',
            'search_patients',
            'view_invoices',

            // Queues — lab queue + general queue management
            'view_queues',
            'manage_queues',
            'view_lab_queue',
            'manage_lab_queue',
            'view_queue_statistics',
            'manage_queue_priorities',
            'call_patients',
            'serve_patients',
            'view_workflow_dashboard',

            // Lab requests
            'view_lab_requests',
            'create_lab_requests',
            'edit_lab_requests',
            'delete_lab_requests',

            // Lab results
            'enter_lab_results',
            'verify_lab_results',
            'approve_lab_results',
            'view_lab_results',
            'print_lab_results',

            // Lab setup — categories, test types, templates, catalog, QC, equipment
            'manage_lab_setup',
            'manage_lab_categories',
            'manage_lab_templates',
            'manage_lab_tests',
            'manage_lab_parameters',
            'manage_lab_reference_ranges',

            // Lab reports & exports
            'generate_lab_reports',

            // Lab inventory & supplies purchases (not pharmacy)
            'view_lab_inventory',
            'view_lab_purchases',
            'create_lab_purchases',
            'receive_lab_purchases',
            'view_lab_suppliers',
            'manage_lab_suppliers',
            'manage_lab_test_consumables',

            // Department operational expenses
            'create_expenses',
            'view_own_expenses',
        ];
    }

    /**
     * Admin/super_admin need explicit lab inventory/purchase grants for sidebar routes.
     */
    private function grantAdminLabInventoryPermissions(): void
    {
        $labInventoryPermissions = [
            'view_lab_inventory',
            'view_lab_purchases',
            'create_lab_purchases',
            'receive_lab_purchases',
            'view_lab_suppliers',
            'manage_lab_suppliers',
            'manage_lab_test_consumables',
        ];

        foreach (['admin', 'super_admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($labInventoryPermissions);
                $this->command?->info("  Granted lab inventory/purchase permissions to {$roleName}");
            }
        }
    }

    private function syncLabScientistBranchAssignments(): void
    {
        $branchService = app(BranchAssignmentService::class);

        $users = User::role('lab_scientist')
            ->with(['staffProfile', 'branches'])
            ->get();

        foreach ($users as $user) {
            if ($user->branches()->exists() && $user->staffProfile?->branch_id) {
                continue;
            }

            $branchId = $branchService->syncStaffBranch($user);
            $this->command?->info("  Synced branch {$branchId} for {$user->email}");
        }
    }
}
