<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SuperAdminPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create super admin role
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);

        // Get all existing permissions
        $allPermissions = Permission::all();

        // Assign all permissions to super admin role
        $superAdminRole->syncPermissions($allPermissions);

        // Get the super admin user
        $superAdmin = User::where('email', 'admin@nexthospital.com')->first();

        if ($superAdmin) {
            // Ensure the user has the super_admin role
            if (!$superAdmin->hasRole('super_admin')) {
                $superAdmin->assignRole('super_admin');
            }

            // Sync all permissions directly to the user (this ensures they have all permissions)
            $superAdmin->syncPermissions($allPermissions);

            // Also ensure they have the super_admin role permissions
            $superAdmin->syncRoles(['super_admin']);

            $this->command->info('Super Admin permissions updated successfully!');
            $this->command->info('Total permissions assigned: ' . $allPermissions->count());
        } else {
            $this->command->error('Super Admin user not found!');
        }
    }
}
