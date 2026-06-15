<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PricingAndRevenuePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Service Pricing Permissions
        $pricingPermissions = [
            'view_service_pricing',
            'create_service_pricing',
            'edit_service_pricing',
            'delete_service_pricing',
            'export_service_pricing',
            'manage_pricing_rules',
        ];

        // Revenue Analytics Permissions
        $revenuePermissions = [
            'view_revenue_analytics',
            'export_revenue_reports',
            'view_department_revenue',
            'view_branch_revenue',
            'view_financial_analytics',
        ];

        // Create all permissions
        $allPermissions = array_merge($pricingPermissions, $revenuePermissions);
        
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Assign permissions to Super Admin role
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($allPermissions);
            $this->command->info('✓ All pricing and revenue permissions assigned to Super Admin');
        }

        // Assign pricing permissions to Admin role
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($allPermissions);
            $this->command->info('✓ All pricing and revenue permissions assigned to Admin');
        }

        // Accountant permissions are managed by RefineAccountantPermissionsSeeder for consistency
        $this->command->info('✓ Accountant permissions managed by RefineAccountantPermissionsSeeder');

        // Assign view-only to Manager role
        $managerRole = Role::where('name', 'Manager')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo([
                'view_service_pricing',
                'view_revenue_analytics',
                'view_department_revenue',
                'view_branch_revenue',
            ]);
            $this->command->info('✓ View permissions assigned to Manager');
        }

        $this->command->info('✓ Pricing and Revenue permissions seeded successfully!');
    }
}

