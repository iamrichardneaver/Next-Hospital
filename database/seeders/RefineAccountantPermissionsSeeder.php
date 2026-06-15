<?php

namespace Database\Seeders;

use App\Services\PermissionSyncService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RefineAccountantPermissionsSeeder extends Seeder
{
    /**
     * Sync curated accountant permissions (replaces all prior accountant grants).
     *
     * Financial oversight scope: billing, payments (view/edit), debtors, pricing,
     * revenue analytics, insurance claims, regulatory reports, cashier report view.
     * Broader than cashier; no clinical admin or counter payment processing.
     */
    public function run(): void
    {
        $this->command?->info('Refining accountant permissions...');

        app(PermissionSyncService::class)->sync();

        $accountant = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);

        $permissions = Permission::whereIn('name', self::accountantPermissionNames())->get();
        $missing = array_diff(self::accountantPermissionNames(), $permissions->pluck('name')->all());
        if ($missing !== []) {
            $this->command?->warn('Missing permission records: ' . implode(', ', $missing));
        }

        $accountant->syncPermissions($permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('Accountant permissions refined: ' . $accountant->permissions->count() . ' total');
        foreach ($accountant->permissions->sortBy('name') as $perm) {
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
            'manage_billing',
            'manage_debtors',
            'manage_service_pricing',
            'manage_insurance_providers',
            'manage_insurance_policies',
            'manage_insurance_claims',
            'view_insurance_analytics',
            'view_financial_dashboard',
            'view_cashier_reports',
            'view_payment_methods',
            'view_financial_analytics',
            'view_department_revenue',
            'view_branch_revenue',
            'export_financial_reports',
            'export_revenue_reports',
            'manage_pricing_rules',
            'manage_debtor_payments',
            'view_payments',
            'create_payments',
            'edit_payments',
            'process_insurance_claims',
            'view_expenses',
            'manage_expenses',
            'approve_expenses',
            'view_balance_sheet',
            'view_cash_flow',
            'view_revenue_reports',
        ];
    }

    /**
     * Canonical accountant permission list.
     *
     * @return list<string>
     */
    public static function accountantPermissionNames(): array
    {
        return [
            'view_dashboard',
            'view_financial_dashboard',

            // Patients — billing context 360
            'view_patients',
            'search_patients',

            // Invoices / billing
            'view_invoices',
            'create_invoices',
            'edit_invoices',
            'delete_invoices',
            'manage_billing',

            // Payments — oversight (no process_payments; cashier handles counter)
            'view_payments',
            'create_payments',
            'edit_payments',

            // Debtors
            'view_debtors',
            'create_debtors',
            'edit_debtors',
            'manage_debtors',
            'manage_debtor_payments',

            // Financial reports & analytics
            'view_financial_reports',
            'view_revenue_analytics',
            'view_financial_analytics',
            'view_department_revenue',
            'view_branch_revenue',
            'export_revenue_analytics',
            'export_revenue_reports',
            'export_financial_reports',
            'view_reports',
            'generate_reports',
            'export_data',

            // Service pricing
            'view_service_pricing',
            'create_service_pricing',
            'edit_service_pricing',
            'delete_service_pricing',
            'export_service_pricing',
            'manage_service_pricing',
            'manage_pricing_rules',

            // Insurance
            'view_insurance',
            'view_insurance_providers',
            'view_insurance_policies',
            'view_insurance_claims',
            'view_insurance_analytics',
            'create_insurance_claims',
            'edit_insurance_claims',
            'manage_insurance_claims',
            'process_insurance_claims',
            'calculate_insurance_coverage',
            'manage_insurance_providers',
            'manage_insurance_policies',

            // Accounting module — expenses, balance sheet, cash flow, revenue reports
            'view_expenses',
            'manage_expenses',
            'approve_expenses',
            'view_balance_sheet',
            'view_cash_flow',
            'view_revenue_reports',

            // Cashier oversight (read-only collections reporting)
            'view_cashier_reports',
            'view_payment_methods',

            // E-commerce revenue cross-over
            'view_store_items',
            'view_store_orders',

            // Notifications
            'view_notifications',
            'mark_notification_read',
        ];
    }
}
