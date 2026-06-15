<?php

namespace Tests\Feature;

use App\Services\PermissionAutoSync;
use App\Services\PermissionSyncService;
use App\Support\PermissionRegistry;
use App\Support\PermissionScanner;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        PermissionScanner::clearCache();
        PermissionAutoSync::forgetFingerprint();
    }

    public function test_sync_is_idempotent(): void
    {
        $service = app(PermissionSyncService::class);

        $first = $service->sync();
        $countAfterFirst = Permission::where('guard_name', PermissionRegistry::guard())->count();

        $second = $service->sync();
        $countAfterSecond = Permission::where('guard_name', PermissionRegistry::guard())->count();

        $this->assertGreaterThan(0, $first['total']);
        $this->assertSame(0, $second['created']);
        $this->assertSame($countAfterFirst, $countAfterSecond);
        $this->assertSame($first['total'], $second['total']);
    }

    public function test_sync_creates_discovered_permission_record(): void
    {
        Permission::where('name', 'record_vitals')->where('guard_name', 'web')->delete();

        $result = app(PermissionSyncService::class)->sync();

        $this->assertTrue(
            Permission::where('name', 'record_vitals')->where('guard_name', 'web')->exists()
        );
        $this->assertGreaterThan(0, $result['discovered']);
    }

    public function test_no_duplicate_permission_names_in_database(): void
    {
        app(PermissionSyncService::class)->sync();

        $duplicates = DB::table('permissions')
            ->select('name', 'guard_name', DB::raw('COUNT(*) as count'))
            ->groupBy('name', 'guard_name')
            ->having('count', '>', 1)
            ->get();

        $this->assertTrue($duplicates->isEmpty(), 'Duplicate permissions found: ' . $duplicates->pluck('name')->join(', '));
    }
}
