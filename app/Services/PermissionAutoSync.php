<?php

namespace App\Services;

use App\Support\PermissionRegistry;
use App\Support\PermissionScanner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Boot-time permission upsert only (Permission::firstOrCreate on the permissions table).
 *
 * SAFETY: This class never runs Schema::drop, migrate:fresh, db:wipe, TRUNCATE, or DELETE
 * outside the permissions table. Table loss is caused elsewhere (e.g. migrate:fresh, PHPUnit
 * RefreshDatabase against production DB, demo:reset, or manual SQL).
 */
class PermissionAutoSync
{
    private const CACHE_KEY = 'permissions_last_sync_fingerprint';

    private const MANUAL_SYNC_META_KEY = 'permissions_manual_sync_meta';

    /**
     * Sync permissions when the codebase or config registry has changed.
     */
    public function syncIfNeeded(): void
    {
        if (!config('permissions.auto_sync_on_boot', true)) {
            return;
        }

        if (!Schema::hasTable('permissions')) {
            return;
        }

        try {
            $fingerprint = PermissionScanner::sourceFingerprint();
        } catch (\Throwable $e) {
            Log::warning('Permission auto-sync skipped: unable to compute source fingerprint.', [
                'exception' => $e->getMessage(),
            ]);

            return;
        }

        $cached = Cache::get(self::CACHE_KEY);

        if ($cached === $fingerprint) {
            return;
        }

        try {
            $result = app(PermissionSyncService::class)->sync();
        } catch (\Throwable $e) {
            Log::warning('Permission auto-sync failed during sync.', [
                'exception' => $e->getMessage(),
            ]);

            return;
        }

        Cache::forever(self::CACHE_KEY, $fingerprint);

        if ($result['created'] > 0 || $result['discovered'] > 0) {
            Log::info('Permissions auto-synced from codebase registry.', [
                'created' => $result['created'],
                'discovered' => $result['discovered'],
                'total' => $result['total'],
            ]);
        }
    }

    /**
     * Force the next boot to re-evaluate the registry.
     */
    public static function forgetFingerprint(): void
    {
        Cache::forget(self::CACHE_KEY);
        PermissionScanner::clearCache();
    }

    /**
     * Mark the registry as synced (updates boot-time fingerprint).
     */
    public static function markSynced(): void
    {
        try {
            Cache::forever(self::CACHE_KEY, PermissionScanner::sourceFingerprint());
        } catch (\Throwable $e) {
            Log::warning('Permission sync fingerprint could not be cached.', [
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record metadata from a manual sync (admin UI or artisan).
     *
     * @param  array{created: int, existing: int, total: int, from_config?: int, discovered?: int, config_duplicates?: array<string, list<string>>}  $result
     */
    public static function recordManualSync(array $result, int $userId, string $userName): void
    {
        Cache::forever(self::MANUAL_SYNC_META_KEY, [
            'synced_at' => now()->toIso8601String(),
            'user_id' => $userId,
            'user_name' => $userName,
            'created' => $result['created'],
            'existing' => $result['existing'],
            'total' => $result['total'],
            'from_config' => $result['from_config'] ?? null,
            'discovered' => $result['discovered'] ?? null,
            'config_duplicates' => $result['config_duplicates'] ?? [],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function lastManualSyncMeta(): ?array
    {
        $meta = Cache::get(self::MANUAL_SYNC_META_KEY);

        return is_array($meta) ? $meta : null;
    }
}
