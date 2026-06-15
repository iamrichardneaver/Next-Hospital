<?php

namespace App\Console\Commands;

use App\Services\PermissionAutoSync;
use App\Services\PermissionSyncService;
use App\Support\PermissionRegistry;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync
                            {--admin : Also grant all web permissions to admin and super_admin roles}
                            {--show-discovered : List permissions found in code but not in config}';

    protected $description = 'Sync permission records from config + codebase discovery to the database';

    public function handle(PermissionSyncService $syncService): int
    {
        $this->info('Syncing permissions (config + codebase discovery)...');

        $result = $syncService->sync();

        $this->line("  Total in registry: {$result['total']}");
        $this->line("  From config: {$result['from_config']}");
        $this->line("  Auto-discovered in code: {$result['discovered']}");
        $this->line("  Created: {$result['created']}");
        $this->line("  Already existed: {$result['existing']}");

        if ($result['config_duplicates'] !== []) {
            $this->warn('  Config duplicate names (deduped by name):');
            foreach ($result['config_duplicates'] as $name => $modules) {
                $this->line("    - {$name} in modules: " . implode(', ', $modules));
            }
        }

        if ($this->option('show-discovered')) {
            $discovered = PermissionRegistry::discoveredNames();
            if ($discovered === []) {
                $this->line('  All scanned permissions are listed in config/permissions.php.');
            } else {
                $this->line('  Discovered-only permissions:');
                foreach ($discovered as $name) {
                    $this->line("    - {$name}");
                }
            }
        }

        PermissionAutoSync::forgetFingerprint();

        if ($this->option('admin')) {
            $this->syncAdminRoles();
        }

        $this->info('Permission sync complete.');

        return self::SUCCESS;
    }

    private function syncAdminRoles(): void
    {
        $allPermissions = Permission::where('guard_name', config('permissions.guard', 'web'))->get();

        if ($allPermissions->isEmpty()) {
            $this->warn('No web permissions found after sync.');

            return;
        }

        foreach (['admin', 'super_admin'] as $roleName) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => config('permissions.guard', 'web'),
            ]);
            $role->syncPermissions($allPermissions);
            $this->line("  {$roleName}: synced {$allPermissions->count()} permissions");
        }
    }
}
