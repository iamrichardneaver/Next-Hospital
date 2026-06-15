<?php

namespace Tests\Unit;

use App\Support\PermissionRegistry;
use App\Support\PermissionScanner;
use Tests\TestCase;

class PermissionScannerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        PermissionScanner::clearCache();
    }

    public function test_extracts_permission_from_middleware_and_can_directives(): void
    {
        $content = <<<'PHP'
        Route::middleware(['permission:record_vitals|view_vitals'])->group(function () {});
        @can('create_expenses')
        $user->hasPermissionTo('approve_expenses');
        PHP;

        $names = PermissionScanner::extractFromContent($content);

        $this->assertContains('record_vitals', $names);
        $this->assertContains('view_vitals', $names);
        $this->assertContains('create_expenses', $names);
        $this->assertContains('approve_expenses', $names);
    }

    public function test_rejects_invalid_and_excluded_names(): void
    {
        $this->assertNull(PermissionScanner::normalize('admin'));
        $this->assertNull(PermissionScanner::normalize('Created_At'));
        $this->assertNull(PermissionScanner::normalize('not-a-permission'));
        $this->assertSame('view_patients', PermissionScanner::normalize('view_patients'));
    }

    public function test_registry_includes_discovered_permissions_not_in_config(): void
    {
        $discovered = PermissionRegistry::discoveredNames();

        $this->assertContains('record_vitals', $discovered);
        $this->assertArrayHasKey('record_vitals', PermissionRegistry::definitions());
    }

    public function test_config_duplicate_map_is_empty_for_current_registry(): void
    {
        $this->assertSame([], PermissionRegistry::configDuplicateMap());
    }

    public function test_source_fingerprint_completes_without_error(): void
    {
        $fingerprint = PermissionScanner::sourceFingerprint();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $fingerprint);
    }

    public function test_full_scan_completes_without_error(): void
    {
        $names = PermissionScanner::scan();

        $this->assertIsArray($names);
        $this->assertNotEmpty($names);
    }
}
