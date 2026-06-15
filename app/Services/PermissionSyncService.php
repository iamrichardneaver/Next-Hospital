<?php

namespace App\Services;

use App\Support\PermissionRegistry;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSyncService
{
    /**
     * Upsert all permissions from config + codebase discovery into the database.
     * Does not remove or reassign existing role/user grants.
     *
     * @return array{
     *     created: int,
     *     existing: int,
     *     total: int,
     *     from_config: int,
     *     discovered: int,
     *     config_duplicates: array<string, list<string>>
     * }
     */
    public function sync(): array
    {
        $guard = PermissionRegistry::guard();
        $definitions = PermissionRegistry::definitions();
        $configDuplicates = PermissionRegistry::configDuplicateMap();

        if ($configDuplicates !== []) {
            Log::warning('Duplicate permission names in config/permissions.php (using first module entry).', [
                'duplicates' => $configDuplicates,
            ]);
        }

        $created = 0;
        $existing = 0;

        foreach ($definitions as $name => $description) {
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => $guard]
            );

            if ($permission->wasRecentlyCreated) {
                $created++;
            } else {
                $existing++;
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return [
            'created' => $created,
            'existing' => $existing,
            'total' => count($definitions),
            'from_config' => count(PermissionRegistry::configDefinitions()),
            'discovered' => count(PermissionRegistry::discoveredNames()),
            'config_duplicates' => $configDuplicates,
        ];
    }
}
