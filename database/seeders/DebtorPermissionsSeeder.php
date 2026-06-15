<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DebtorPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create debtors permissions
        $permissions = [
            'view_debtors',
            'create_debtors',
            'edit_debtors',
            'delete_debtors',
            'manage_debtors',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles (accountant grants: RefineAccountantPermissionsSeeder)
        $roles = [
            'super_admin' => $permissions,
            'admin' => $permissions,
            'receptionist' => ['view_debtors'],
            'doctor' => ['view_debtors'],
            'nurse' => ['view_debtors'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                foreach ($rolePermissions as $permission) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }
}
