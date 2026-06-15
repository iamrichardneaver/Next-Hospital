<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EnsureSuperAdminPermissions extends Seeder
{
    /**
     * @deprecated Use RefineAdminPermissionsSeeder (syncs admin + super_admin).
     *
     * Run: php artisan db:seed --class=RefineAdminPermissionsSeeder
     */
    public function run(): void
    {
        $this->command?->warn('EnsureSuperAdminPermissions is deprecated. Delegating to RefineAdminPermissionsSeeder.');
        $this->call(RefineAdminPermissionsSeeder::class);
    }
    
    /**
     * Display a summary of all permissions by module
     */
    private function displayPermissionSummary($permissions): void
    {
        $this->command->info('');
        $this->command->info('📊 Permission Summary by Module:');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        // Group permissions by module
        $groupedPermissions = $permissions->groupBy(function($permission) {
            $parts = explode('_', $permission->name);
            $module = implode('_', array_slice($parts, 1));
            return ucwords(str_replace('_', ' ', $module));
        });
        
        foreach ($groupedPermissions as $module => $perms) {
            $this->command->info('📁 ' . $module . ': ' . $perms->count() . ' permissions');
        }
        
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('✅ Total: ' . $permissions->count() . ' permissions');
        $this->command->info('');
        $this->command->info('🎉 Super Admin is now guaranteed to have ALL permissions!');
    }
}
