<?php

namespace App\Support;

class PermissionRegistry
{
    /**
     * Default guard for web admin permissions.
     */
    public static function guard(): string
    {
        return (string) config('permissions.guard', 'web');
    }

    /**
     * All permission modules from config (explicit definitions only).
     *
     * @return array<string, array<string, string|null>>
     */
    public static function configModules(): array
    {
        return config('permissions.modules', []);
    }

    /**
     * All permission modules including auto-discovered permissions from the codebase.
     *
     * @return array<string, array<string, string|null>>
     */
    public static function modules(): array
    {
        $modules = self::configModules();
        $configNames = array_keys(self::configDefinitions());

        foreach (PermissionScanner::scan() as $name) {
            if (isset($configNames[$name])) {
                continue;
            }

            $module = PermissionModuleGuesser::guess($name);
            $modules[$module][$name] = null;
        }

        ksort($modules);
        foreach ($modules as &$permissions) {
            ksort($permissions);
        }
        unset($permissions);

        return $modules;
    }

    /**
     * Flat map of permission name => optional description (config overrides discovered).
     *
     * @return array<string, string|null>
     */
    public static function definitions(): array
    {
        $definitions = self::configDefinitions();

        foreach (PermissionScanner::scan() as $name) {
            if (!array_key_exists($name, $definitions)) {
                $definitions[$name] = null;
            }
        }

        ksort($definitions);

        return $definitions;
    }

    /**
     * Definitions declared only in config/permissions.php.
     *
     * @return array<string, string|null>
     */
    public static function configDefinitions(): array
    {
        $definitions = [];

        foreach (self::configModules() as $permissions) {
            foreach ($permissions as $name => $description) {
                $definitions[$name] = is_string($description) ? $description : null;
            }
        }

        return $definitions;
    }

    /**
     * Permission names discovered in code but not listed in config.
     *
     * @return list<string>
     */
    public static function discoveredNames(): array
    {
        $configNames = array_flip(array_keys(self::configDefinitions()));

        return array_values(array_filter(
            PermissionScanner::scan(),
            fn (string $name) => !isset($configNames[$name])
        ));
    }

    /**
     * Detect duplicate permission names across config modules.
     *
     * @return array<string, list<string>> permission name => module keys where it appears
     */
    public static function configDuplicateMap(): array
    {
        $seen = [];
        $duplicates = [];

        foreach (self::configModules() as $module => $permissions) {
            foreach (array_keys($permissions) as $name) {
                $seen[$name][] = $module;
            }
        }

        foreach ($seen as $name => $modules) {
            if (count($modules) > 1) {
                $duplicates[$name] = $modules;
            }
        }

        ksort($duplicates);

        return $duplicates;
    }

    /**
     * Map permission name to module label for UI grouping.
     *
     * @return array<string, string>
     */
    public static function nameToModuleLabel(): array
    {
        $map = [];

        foreach (self::modules() as $module => $permissions) {
            $label = ucwords(str_replace('_', ' ', $module));
            foreach (array_keys($permissions) as $name) {
                $map[$name] = $label;
            }
        }

        return $map;
    }

    /**
     * Permission names for a single module.
     *
     * @return list<string>
     */
    public static function moduleNames(string $module): array
    {
        $permissions = self::modules()[$module] ?? [];

        return array_keys($permissions);
    }

    /**
     * All registered permission names (config + discovered).
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * Registry fingerprint for auto-sync change detection.
     */
    public static function registryHash(): string
    {
        return hash('sha256', json_encode(self::definitions(), JSON_THROW_ON_ERROR));
    }
}
