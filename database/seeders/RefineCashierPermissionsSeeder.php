<?php

namespace Database\Seeders;

use App\Services\PermissionSyncService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RefineCashierPermissionsSeeder extends Seeder
{
    /**
     * Sync curated cashier permissions (replaces all prior cashier grants).
     *
     * Financial ops scope: patient 360 (read-only), billing/invoices, payments,
     * debtors, cashier queue. No clinical admin, pharmacy stock, lab setup, or staff admin.
     */
    public function run(): void
    {
        $this->command?->info('Refining cashier permissions...');

        app(PermissionSyncService::class)->sync();

        $cashier = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);

        $permissions = Permission::whereIn('name', self::cashierPermissionNames())->get();
        $missing = array_diff(self::cashierPermissionNames(), $permissions->pluck('name')->all());
        if ($missing !== []) {
            $this->command?->warn('Missing permission records: ' . implode(', ', $missing));
        }

        $cashier->syncPermissions($permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('Cashier permissions refined: ' . $cashier->permissions->count() . ' total');
        foreach ($cashier->permissions->sortBy('name') as $perm) {
            $this->command?->line("  • {$perm->name}");
        }
    }

    /**
     * Permissions that may not exist in RolePermissionSeeder — ensure records exist.
     *
     * @return list<string>
     */
    public static function ensurePermissionNames(): array
    {
        return [
            'view_payments',
            'create_payments',
            'manage_debtor_payments',
            'manage_billing',
        ];
    }

    /**
     * Canonical cashier permission list.
     *
     * @return list<string>
     */
    public static function cashierPermissionNames(): array
    {
        return [
            'view_dashboard',

            // Patients — 360 view (read-only)
            'view_patients',
            'search_patients',

            // Clinical context on patient profile tabs (read-only; sidebar hidden for cashier role)
            'view_visits',
            'view_appointments',
            'view_consultations',
            'view_vitals',
            'view_prescriptions',
            'view_lab_results',
            'view_radiology_results',

            // Billing & invoices
            'view_invoices',
            'create_invoices',

            // Payments — core cashier workflow
            'view_payments',
            'create_payments',
            'process_payments',

            // Debtors — outstanding balances + record payments on debt
            'view_debtors',
            'manage_debtor_payments',

            // Communications
            'view_notifications',
            'mark_notification_read',

            // Department operational expenses
            'create_expenses',
            'view_own_expenses',
        ];
    }
}
