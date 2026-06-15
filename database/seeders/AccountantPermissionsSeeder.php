<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccountantPermissionsSeeder extends Seeder
{
    /**
     * @deprecated Use RefineAccountantPermissionsSeeder as the canonical accountant sync.
     *             This seeder delegates to Refine* and optionally grants the same
     *             permission records to super_admin and admin.
     */
    public function run(): void
    {
        $this->call(RefineAccountantPermissionsSeeder::class);

        $permissions = Permission::whereIn('name', RefineAccountantPermissionsSeeder::accountantPermissionNames())->get();

        foreach (['super_admin', 'admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
                $this->command?->info("✓ Accountant financial permissions also granted to {$roleName}");
            }
        }

        $this->command?->info('✅ Accountant permissions seeded (via RefineAccountantPermissionsSeeder)');
    }
}
