<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DataCleanupService
{
    /**
     * Tables that must never be cleaned, regardless of module selection.
     */
    public function getProtectedTables(): array
    {
        return config('data_cleanup.protected_tables', []);
    }

    /**
     * Module definitions with live record counts (cleanable tables only).
     */
    public function getCleanableModules(): array
    {
        $modules = config('data_cleanup.modules', []);
        $protected = $this->getProtectedTables();

        foreach ($modules as $key => $module) {
            $tableCounts = [];
            $totalRecords = 0;

            foreach ($module['tables'] as $table) {
                if (in_array($table, $protected, true)) {
                    continue;
                }

                $count = $this->countTableRecords($table);
                $tableCounts[$table] = $count;
                $totalRecords += $count;
            }

            $modules[$key]['table_counts'] = $tableCounts;
            $modules[$key]['estimated_records'] = $totalRecords;
            $modules[$key]['cleanable_tables'] = array_keys($tableCounts);
        }

        return $modules;
    }

    /**
     * Preview what would be deleted for the given module keys.
     */
    public function getCleanupPreview(array $moduleKeys): array
    {
        $modules = $this->getCleanableModules();
        $protected = $this->getProtectedTables();
        $preview = [
            'modules' => [],
            'tables' => [],
            'total_records' => 0,
            'protected_skipped' => [],
        ];

        $tablesToClean = $this->resolveTablesForModules($moduleKeys, $modules, $protected);

        foreach ($tablesToClean as $table) {
            $count = $this->countTableRecords($table);
            $preview['tables'][$table] = $count;
            $preview['total_records'] += $count;
        }

        foreach ($moduleKeys as $moduleKey) {
            if (!isset($modules[$moduleKey])) {
                continue;
            }

            $module = $modules[$moduleKey];
            $moduleRecordCount = 0;
            $moduleTables = [];

            foreach ($module['tables'] as $table) {
                if (in_array($table, $protected, true)) {
                    $preview['protected_skipped'][] = $table;
                    continue;
                }

                $count = $preview['tables'][$table] ?? 0;
                $moduleTables[$table] = $count;
                $moduleRecordCount += $count;
            }

            $preview['modules'][$moduleKey] = [
                'name' => $module['name'],
                'description' => $module['description'],
                'tables' => $moduleTables,
                'total_records' => $moduleRecordCount,
            ];
        }

        $preview['protected_skipped'] = array_values(array_unique($preview['protected_skipped']));

        return $preview;
    }

    /**
     * Clean operational data for specific modules inside a single transaction.
     */
    public function cleanModuleData(array $moduleKeys, $userId = null): array
    {
        $this->assertSystemSafeguards();

        $modules = $this->getCleanableModules();
        $protected = $this->getProtectedTables();
        $tablesToClean = $this->resolveTablesForModules($moduleKeys, $modules, $protected);

        if (empty($tablesToClean)) {
            throw new \RuntimeException('No cleanable tables found for the selected modules.');
        }

        $preview = $this->getCleanupPreview($moduleKeys);
        $results = [];
        $cleanedByModule = [];

        foreach ($moduleKeys as $moduleKey) {
            if (!isset($modules[$moduleKey])) {
                continue;
            }

            $cleanedByModule[$moduleKey] = [
                'name' => $modules[$moduleKey]['name'],
                'cleaned_tables' => [],
                'records_cleaned' => 0,
                'success' => true,
            ];
        }

        // MySQL TRUNCATE/DDL implicitly commits — use ordered deletes with FK checks disabled.
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($tablesToClean as $table) {
                if (!$this->isTableCleanable($table)) {
                    Log::warning('Data cleanup skipped non-cleanable table', ['table' => $table]);
                    continue;
                }

                $recordCount = $this->countTableRecords($table);

                if ($recordCount === 0) {
                    continue;
                }

                $this->deleteAllFromTable($table);

                foreach ($moduleKeys as $moduleKey) {
                    if (!isset($modules[$moduleKey])) {
                        continue;
                    }

                    if (in_array($table, $modules[$moduleKey]['tables'], true)) {
                        $cleanedByModule[$moduleKey]['cleaned_tables'][] = $table;
                        $cleanedByModule[$moduleKey]['records_cleaned'] += $recordCount;
                    }
                }

                Log::info('Data cleanup table cleared', [
                    'table' => $table,
                    'records' => $recordCount,
                    'user_id' => $userId,
                ]);
            }
        } catch (\Throwable $e) {
            throw new \Exception('Failed to clean data: ' . $e->getMessage(), 0, $e);
        } finally {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } catch (\Throwable $fkError) {
                Log::error('Failed to re-enable foreign key checks after cleanup', [
                    'error' => $fkError->getMessage(),
                ]);
            }
        }

        foreach ($cleanedByModule as $moduleKey => $result) {
            $results[$moduleKey] = $result;

            if ($userId) {
                Log::info('Data cleanup module completed', [
                    'user_id' => $userId,
                    'module' => $moduleKey,
                    'module_name' => $result['name'],
                    'cleaned_tables' => $result['cleaned_tables'],
                    'records_cleaned' => $result['records_cleaned'],
                    'preview_total' => $preview['total_records'],
                ]);
            }
        }

        $this->assertSystemSafeguards();

        return $results;
    }

    public function cleanAllData($userId = null): array
    {
        return $this->cleanModuleData(array_keys(config('data_cleanup.modules', [])), $userId);
    }

    public function getSystemStats(): array
    {
        $modules = $this->getCleanableModules();
        $totalRecords = 0;
        $totalTables = 0;

        foreach ($modules as $module) {
            $totalRecords += $module['estimated_records'];
            $totalTables += count($module['cleanable_tables'] ?? []);
        }

        return [
            'total_modules' => count($modules),
            'total_tables' => $totalTables,
            'total_records' => $totalRecords,
            'modules_with_data' => collect($modules)->filter(fn ($module) => $module['estimated_records'] > 0)->count(),
            'protected_tables' => count($this->getProtectedTables()),
        ];
    }

    /**
     * Ensure at least one branch and one admin-level user remain after cleanup.
     */
    public function assertSystemSafeguards(): void
    {
        if (Schema::hasTable('branches')) {
            $branchCount = DB::table('branches')->count();
            if ($branchCount < 1) {
                throw new \RuntimeException('System safeguard failed: at least one branch must remain.');
            }
        }

        if (Schema::hasTable('users') && Schema::hasTable('model_has_roles') && Schema::hasTable('roles')) {
            $adminCount = DB::table('users')
                ->join('model_has_roles', function ($join) {
                    $join->on('users.id', '=', 'model_has_roles.model_id')
                        ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
                })
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->whereIn('roles.name', ['super_admin', 'admin'])
                ->distinct('users.id')
                ->count('users.id');

            if ($adminCount < 1) {
                throw new \RuntimeException('System safeguard failed: at least one admin user must remain.');
            }
        }
    }

    private function resolveTablesForModules(array $moduleKeys, array $modules, array $protected): array
    {
        $tables = [];

        foreach ($moduleKeys as $moduleKey) {
            if (!isset($modules[$moduleKey])) {
                continue;
            }

            foreach ($modules[$moduleKey]['tables'] as $table) {
                if (!in_array($table, $protected, true)) {
                    $tables[] = $table;
                }
            }
        }

        $tables = array_values(array_unique($tables));

        return $this->sortTablesForDeletion($tables);
    }

    private function sortTablesForDeletion(array $tables): array
    {
        $order = config('data_cleanup.deletion_order', []);
        $positions = array_flip($order);

        usort($tables, function ($a, $b) use ($positions) {
            $posA = $positions[$a] ?? PHP_INT_MAX;
            $posB = $positions[$b] ?? PHP_INT_MAX;

            if ($posA === $posB) {
                return strcmp($a, $b);
            }

            return $posA <=> $posB;
        });

        return $tables;
    }

    private function isTableCleanable(string $table): bool
    {
        if (in_array($table, $this->getProtectedTables(), true)) {
            return false;
        }

        $allowedTables = collect(config('data_cleanup.modules', []))
            ->pluck('tables')
            ->flatten()
            ->unique()
            ->values()
            ->all();

        return in_array($table, $allowedTables, true) && Schema::hasTable($table);
    }

    private function countTableRecords(string $table): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        try {
            return (int) DB::table($table)->count();
        } catch (\Throwable $e) {
            Log::warning("Could not count records in table: {$table}", ['error' => $e->getMessage()]);

            return 0;
        }
    }

    private function deleteAllFromTable(string $table): void
    {
        try {
            DB::table($table)->truncate();
        } catch (\Throwable $truncateError) {
            Log::warning("Truncate failed for table {$table}, falling back to delete", [
                'error' => $truncateError->getMessage(),
            ]);

            DB::table($table)->delete();
        }
    }
}
