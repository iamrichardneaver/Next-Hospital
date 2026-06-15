<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\BranchAssignmentService;
use App\Services\PermissionSyncService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RefinePharmacistPermissionsSeeder extends Seeder
{
    /**
     * Sync curated pharmacist permissions (replaces all prior pharmacist grants).
     */
    public function run(): void
    {
        $this->command?->info('Refining pharmacist permissions...');

        app(PermissionSyncService::class)->sync();

        $pharmacist = Role::where('name', 'pharmacist')->first();
        if (!$pharmacist) {
            $this->command?->error('Pharmacist role not found.');
            return;
        }

        $permissions = Permission::whereIn('name', self::pharmacistPermissionNames())->get();
        $pharmacist->syncPermissions($permissions);

        $this->grantAdminPharmacyPurchasePermissions();
        $this->syncPharmacistBranchAssignments();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('Pharmacist permissions refined: ' . $pharmacist->permissions->count() . ' total');
        foreach ($pharmacist->permissions->sortBy('name') as $perm) {
            $this->command?->line("  • {$perm->name}");
        }
    }

    /**
     * @return list<string>
     */
    public static function pharmacistPermissionNames(): array
    {
        return [
            'view_dashboard',

            // Patients — search/view for dispensing context
            'view_patients',
            'search_patients',

            // Prescriptions — view and adjust before dispense; doctors create via consultation
            'view_prescriptions',
            'edit_prescriptions',

            // Drug formulary & inventory
            'view_drugs',
            'view_drug_formulary',
            'create_drugs',
            'edit_drugs',
            'manage_inventory',
            'manage_pharmacy_inventory',
            'view_pharmacy_purchases',
            'create_pharmacy_purchases',
            'receive_pharmacy_purchases',
            'view_pharmacy_suppliers',
            'manage_pharmacy_suppliers',
            'dispense_drugs',
            'view_pharmacy_analytics',

            // Pharmacy queue
            'view_queues',
            'view_pharmacy_queue',
            'manage_pharmacy_queue',
            'call_patients',
            'serve_patients',

            // Cashier integration — generate/process pharmacy charges at dispense
            'view_invoices',
            'create_invoices',
            'view_payments',
            'create_payments',
            'process_payments',

            // Communications
            'view_notifications',
            'mark_notification_read',
            'view_messages',
            'send_messages',

            // Department operational expenses
            'create_expenses',
            'view_own_expenses',
        ];
    }

    /**
     * Admin/super_admin need explicit purchase grants (not in pharmacist sync list scope).
     */
    private function grantAdminPharmacyPurchasePermissions(): void
    {
        $purchasePermissions = [
            'view_pharmacy_purchases',
            'create_pharmacy_purchases',
            'receive_pharmacy_purchases',
        ];

        foreach (['admin', 'super_admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($purchasePermissions);
                $this->command?->info("  Granted pharmacy purchase permissions to {$roleName}");
            }
        }
    }

    private function syncPharmacistBranchAssignments(): void
    {
        $branchService = app(BranchAssignmentService::class);

        $users = User::role('pharmacist')
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
