<?php

namespace Database\Seeders;

use App\Services\PermissionSyncService;
use Illuminate\Database\Seeder;

/**
 * Upserts all permission records from config + codebase discovery.
 * Safe to run on every deploy and fresh install — does not change role assignments.
 *
 * php artisan db:seed --class=PermissionsSyncSeeder
 * php artisan permissions:sync
 */
class PermissionsSyncSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Syncing permission records (config + codebase discovery)...');

        $result = app(PermissionSyncService::class)->sync();

        $this->command?->info(
            "Permissions synced: {$result['total']} total, {$result['created']} created, {$result['existing']} already existed."
        );
    }
}
