<?php

namespace Database\Seeders;

use App\Services\PermissionSyncService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RefineRadiologistPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Refining radiologist inventory permissions...');

        app(PermissionSyncService::class)->sync();

        $inventoryPermissions = [
            'view_radiology_inventory',
            'view_radiology_purchases',
            'create_radiology_purchases',
            'receive_radiology_purchases',
            'view_radiology_suppliers',
            'manage_radiology_suppliers',
        ];

        foreach ($inventoryPermissions as $name) {
            $this->command?->line("  Permission: {$name}");
        }

        foreach (['radiologist', 'radiology_technician'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($inventoryPermissions);
                $this->command?->info("  {$roleName}: radiology inventory permissions granted (additive)");
            }
        }

        foreach (['admin', 'super_admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($inventoryPermissions);
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('Radiologist inventory permissions seeded.');
    }
}
