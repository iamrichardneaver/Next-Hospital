<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ComplaintsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions for complaints management
        $permissions = [
            'view_complaints',
            'create_complaints',
            'edit_complaints',
            'delete_complaints',
            'manage_complaints',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        // Assign all complaint permissions to super admin
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // Assign permissions to admin
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        // Assign view and create permissions to receptionist
        $receptionist = Role::where('name', 'receptionist')->first();
        if ($receptionist) {
            $receptionist->givePermissionTo([
                'view_complaints',
                'create_complaints',
            ]);
        }

        // Assign view and edit permissions to customer service roles
        $customerServiceRoles = ['customer_service', 'front_desk'];
        foreach ($customerServiceRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo([
                    'view_complaints',
                    'create_complaints',
                    'edit_complaints',
                ]);
            }
        }

        $this->command->info('Complaints permissions created and assigned successfully!');
    }
}

