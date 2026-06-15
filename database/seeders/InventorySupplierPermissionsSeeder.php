<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InventorySupplierPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view_pharmacy_suppliers' => 'View pharmacy suppliers',
            'manage_pharmacy_suppliers' => 'Create and manage pharmacy suppliers',
            'view_lab_suppliers' => 'View laboratory suppliers',
            'manage_lab_suppliers' => 'Create and manage laboratory suppliers',
            'view_radiology_suppliers' => 'View radiology suppliers',
            'manage_radiology_suppliers' => 'Create and manage radiology suppliers',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $this->command?->line("  Permission: {$name}");
        }

        $pharmacist = Role::where('name', 'pharmacist')->first();
        if ($pharmacist) {
            $pharmacist->givePermissionTo(['view_pharmacy_suppliers', 'manage_pharmacy_suppliers']);
        }

        foreach (['lab_scientist', 'lab_manager'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo(['view_lab_suppliers', 'manage_lab_suppliers']);
            }
        }

        foreach (['radiologist', 'radiology_technician'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo(['view_radiology_suppliers', 'manage_radiology_suppliers']);
            }
        }

        $adminPermissions = array_keys($permissions);
        foreach (['admin', 'super_admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($adminPermissions);
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('Inventory supplier permissions seeded.');
    }
}
