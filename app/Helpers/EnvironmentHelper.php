<?php

namespace App\Helpers;

use App\Services\CrossPlatformService;

class EnvironmentHelper
{
    /**
     * Get environment-specific configuration
     */
    public static function getConfig(string $key, $default = null)
    {
        $os = CrossPlatformService::getOS();
        $environment = CrossPlatformService::isCloud() ? 'cloud' : 'local';
        
        // Try OS-specific config first
        $osKey = "{$key}.{$os}";
        if (config()->has($osKey)) {
            return config($osKey);
        }
        
        // Try environment-specific config
        $envKey = "{$key}.{$environment}";
        if (config()->has($envKey)) {
            return config($envKey);
        }
        
        // Fall back to default
        return config($key, $default);
    }

    /**
     * Get Windows-specific configuration
     */
    public static function getWindowsConfig(): array
    {
        return [
            'files' => [
                'upload_path' => 'C:\\nexthospital\\uploads',
                'temp_path' => 'C:\\nexthospital\\temp',
                'log_path' => 'C:\\nexthospital\\logs',
                'backup_path' => 'C:\\nexthospital\\backups',
                'public_path' => 'C:\\nexthospital\\public',
            ],
            'database' => [
                'path' => 'C:\\nexthospital\\database',
                'backup_path' => 'C:\\nexthospital\\backups\\database',
            ],
            'permissions' => [
                'file' => 0644,
                'directory' => 0755,
            ],
            'urls' => [
                'base_url' => config('app.url'),
                'storage_url' => '/storage',
                'public_url' => '/',
            ],
        ];
    }

    /**
     * Get macOS-specific configuration
     */
    public static function getMacOSConfig(): array
    {
        return [
            'files' => [
                'upload_path' => '/Users/Shared/nexthospital/uploads',
                'temp_path' => '/tmp/nexthospital',
                'log_path' => '/Users/Shared/nexthospital/logs',
                'backup_path' => '/Users/Shared/nexthospital/backups',
                'public_path' => '/Users/Shared/nexthospital/public',
            ],
            'database' => [
                'path' => '/Users/Shared/nexthospital/database',
                'backup_path' => '/Users/Shared/nexthospital/backups/database',
            ],
            'permissions' => [
                'file' => 0644,
                'directory' => 0755,
            ],
            'urls' => [
                'base_url' => config('app.url'),
                'storage_url' => '/storage',
                'public_url' => '/',
            ],
        ];
    }

    /**
     * Get Linux-specific configuration
     */
    public static function getLinuxConfig(): array
    {
        return [
            'files' => [
                'upload_path' => '/var/www/nexthospital/uploads',
                'temp_path' => '/tmp/nexthospital',
                'log_path' => '/var/log/nexthospital',
                'backup_path' => '/var/backups/nexthospital',
                'public_path' => '/var/www/nexthospital/public',
            ],
            'database' => [
                'path' => '/var/lib/nexthospital/database',
                'backup_path' => '/var/backups/nexthospital/database',
            ],
            'permissions' => [
                'file' => 0644,
                'directory' => 0755,
            ],
            'urls' => [
                'base_url' => config('app.url'),
                'storage_url' => '/storage',
                'public_url' => '/',
            ],
        ];
    }

    /**
     * Get cloud-specific configuration
     */
    public static function getCloudConfig(): array
    {
        return [
            'files' => [
                'upload_path' => 'uploads',
                'temp_path' => 'temp',
                'log_path' => 'logs',
                'backup_path' => 'backups',
                'public_path' => 'public',
            ],
            'database' => [
                'path' => 'database',
                'backup_path' => 'backups/database',
            ],
            'permissions' => [
                'file' => 0644,
                'directory' => 0755,
            ],
            'storage' => [
                'disk' => 's3',
                'public_disk' => 's3',
            ],
            'urls' => [
                'base_url' => config('app.url'),
                'storage_url' => env('STORAGE_URL', '/storage'),
                'public_url' => env('PUBLIC_URL', '/'),
            ],
        ];
    }

    /**
     * Get current environment configuration
     */
    public static function getCurrentConfig(): array
    {
        $os = CrossPlatformService::getOS();
        $environment = CrossPlatformService::isCloud() ? 'cloud' : 'local';
        
        switch ($os) {
            case 'windows':
                return self::getWindowsConfig();
            case 'macos':
                return self::getMacOSConfig();
            case 'linux':
                return self::getLinuxConfig();
            default:
                return self::getCloudConfig();
        }
    }

    /**
     * Setup environment-specific configuration
     */
    public static function setupEnvironment(): void
    {
        $config = self::getCurrentConfig();
        
        // Set file paths
        if (isset($config['files'])) {
            foreach ($config['files'] as $key => $value) {
                config(["cross_platform.files.{$key}" => $value]);
            }
        }
        
        // Set database paths
        if (isset($config['database'])) {
            foreach ($config['database'] as $key => $value) {
                config(["cross_platform.database.{$key}" => $value]);
            }
        }
        
        // Set permissions
        if (isset($config['permissions'])) {
            foreach ($config['permissions'] as $key => $value) {
                config(["cross_platform.permissions.{$key}" => $value]);
            }
        }
        
        // Set URLs
        if (isset($config['urls'])) {
            foreach ($config['urls'] as $key => $value) {
                config(["cross_platform.urls.{$key}" => $value]);
            }
        }
        
        // Set storage configuration
        if (isset($config['storage'])) {
            foreach ($config['storage'] as $key => $value) {
                config(["cross_platform.storage.{$key}" => $value]);
            }
        }
    }

    /**
     * Get environment-specific .env recommendations
     */
    public static function getEnvRecommendations(): array
    {
        $os = CrossPlatformService::getOS();
        $config = self::getCurrentConfig();
        
        $recommendations = [
            'APP_NAME="NextHospital"',
            'APP_ENV=local',
            'APP_DEBUG=true',
            'APP_URL=' . ($config['urls']['base_url'] ?? config('app.url')),
            '',
            '# Database Configuration',
            'DB_CONNECTION=mysql',
            'DB_HOST=127.0.0.1',
            'DB_PORT=3306',
            'DB_DATABASE=nexthospital',
            'DB_USERNAME=root',
            'DB_PASSWORD=Sparkles@@55??',
            '',
            '# File Storage Configuration',
            'FILESYSTEM_DISK=local',
            'FILESYSTEM_PUBLIC_DISK=public',
            'FILESYSTEM_CLOUD_DISK=s3',
            '',
            '# ' . ucfirst($os) . '-specific paths',
        ];
        
        if (isset($config['files'])) {
            foreach ($config['files'] as $key => $value) {
                $envKey = 'FILES_' . strtoupper($key);
                $recommendations[] = "{$envKey}={$value}";
            }
        }
        
        if (isset($config['database'])) {
            $recommendations[] = '';
            $recommendations[] = '# Database paths';
            foreach ($config['database'] as $key => $value) {
                $envKey = 'DB_' . strtoupper($key);
                $recommendations[] = "{$envKey}={$value}";
            }
        }
        
        $recommendations[] = '';
        $recommendations[] = '# URL Configuration';
        $recommendations[] = 'STORAGE_URL=' . ($config['urls']['storage_url'] ?? '/storage');
        $recommendations[] = 'PUBLIC_URL=' . ($config['urls']['public_url'] ?? '/');
        $recommendations[] = 'FORCE_HTTPS=false';
        
        return $recommendations;
    }
}
