<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RefineAdminPermissionsSeeder extends Seeder
{
    /**
     * Sync admin and super_admin roles with every web-guard permission.
     * Run after permissions:sync / PermissionsSyncSeeder so new permissions are included.
     *
     * php artisan permissions:sync --admin
     * php artisan db:seed --class=RefineAdminPermissionsSeeder
     */
    public function run(): void
    {
        $this->command?->info('Syncing admin and super_admin with all web permissions...');

        $allPermissions = Permission::where('guard_name', 'web')->get();

        if ($allPermissions->isEmpty()) {
            $this->command?->warn('No web permissions found. Run RolePermissionSeeder and module seeders first.');
            return;
        }

        foreach (['admin', 'super_admin'] as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($allPermissions);
            $this->command?->info("  {$roleName}: {$allPermissions->count()} permissions");
        }

        foreach (User::role(['admin', 'super_admin'])->get() as $user) {
            $user->syncPermissions($allPermissions);
            $this->command?->info("  User {$user->email}: synced direct permissions");
        }

        $this->command?->info('Admin permission sync complete.');
    }
}
