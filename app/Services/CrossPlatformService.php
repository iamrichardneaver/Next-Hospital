<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class CrossPlatformService
{
    /**
     * Get the current operating system
     */
    public static function getOS(): string
    {
        $os = strtolower(PHP_OS_FAMILY);
        
        switch ($os) {
            case 'windows':
                return 'windows';
            case 'darwin':
                return 'macos';
            case 'linux':
                return 'linux';
            default:
                return 'unknown';
        }
    }

    /**
     * Check if running on Windows
     */
    public static function isWindows(): bool
    {
        return self::getOS() === 'windows';
    }

    /**
     * Check if running on macOS
     */
    public static function isMacOS(): bool
    {
        return self::getOS() === 'macos';
    }

    /**
     * Check if running on Linux
     */
    public static function isLinux(): bool
    {
        return self::getOS() === 'linux';
    }

    /**
     * Check if running in cloud environment
     */
    public static function isCloud(): bool
    {
        return !empty($_ENV['APP_ENV']) && in_array($_ENV['APP_ENV'], ['production', 'staging', 'cloud']);
    }

    /**
     * Check if running locally
     */
    public static function isLocal(): bool
    {
        return !self::isCloud() && in_array(env('APP_ENV', 'local'), ['local', 'development']);
    }

    /**
     * Normalize file path for current OS
     */
    public static function normalizePath(string $path): string
    {
        if (self::isWindows()) {
            // Convert forward slashes to backslashes on Windows
            $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        } else {
            // Ensure forward slashes on Unix-like systems
            $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
        }

        // Remove duplicate separators
        $separator = preg_quote(DIRECTORY_SEPARATOR, '/');
        $path = preg_replace('/' . $separator . '+/', DIRECTORY_SEPARATOR, $path);
        
        return $path;
    }

    /**
     * Get storage path with proper OS handling
     */
    public static function getStoragePath(string $path = ''): string
    {
        $storagePath = storage_path('app');
        
        if (!empty($path)) {
            $storagePath = self::normalizePath($storagePath . DIRECTORY_SEPARATOR . $path);
        }

        return $storagePath;
    }

    /**
     * Get public storage path with proper OS handling
     */
    public static function getPublicStoragePath(string $path = ''): string
    {
        $publicPath = storage_path('app/public');
        
        if (!empty($path)) {
            $publicPath = self::normalizePath($publicPath . DIRECTORY_SEPARATOR . $path);
        }

        return $publicPath;
    }

    /**
     * Application base URL (APP_URL or ASSET_URL). Used for CLI/queue/API defaults.
     */
    public static function getApplicationBaseUrl(): string
    {
        $base = config('app.asset_url') ?: config('app.url', 'http://localhost');

        return rtrim((string) $base, '/');
    }

    /**
     * Whether the PHP GD extension is available (required by DomPDF for embedded images).
     */
    public static function isGdAvailable(): bool
    {
        return extension_loaded('gd');
    }

    /**
     * Dompdf-safe logo source: base64 data URI preferred, then absolute filesystem path.
     */
    public static function resolvePdfLogoSrc(array $branding, ?string $logoFullPath = null): ?string
    {
        if (!empty($branding['logo_base64'])) {
            return $branding['logo_base64'];
        }

        if (!empty($branding['logo_absolute_path']) && file_exists($branding['logo_absolute_path'])) {
            return $branding['logo_absolute_path'];
        }

        if (!empty($logoFullPath) && file_exists($logoFullPath)) {
            return $logoFullPath;
        }

        return null;
    }

    /**
     * Get public URL for storage file
     */
    public static function getStorageUrl(string $path): string
    {
        // Normalize to forward slashes for URLs (Windows paths break image src)
        $path = str_replace('\\', '/', ltrim($path, '/\\'));

        if (str_starts_with($path, 'branding/')) {
            return self::getBrandingFileUrl($path);
        }

        // Prefer request-derived URL in HTTP context (subdirectory-safe).
        if (!app()->runningInConsole() && request()->hasHeader('Host')) {
            $url = self::buildRequestStorageUrl($path);
        } else {
            $url = self::buildConfiguredStorageUrl($path);
        }

        return str_replace('\\', '/', $url);
    }

    /**
     * Public URL for branding assets — uses Laravel route fallback (Plesk-safe).
     */
    public static function getBrandingFileUrl(string $path): string
    {
        $path = str_replace('\\', '/', ltrim($path, '/\\'));
        $filename = basename($path);

        if (!preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
            return self::buildConfiguredStorageUrl($path);
        }

        if (!app()->runningInConsole() && request()->hasHeader('Host')) {
            $scriptName = request()->server('SCRIPT_NAME', '');

            if ($scriptName && str_ends_with($scriptName, '/index.php')) {
                $basePath = rtrim(str_replace('/index.php', '', $scriptName), '/');

                return request()->getSchemeAndHttpHost() . $basePath . '/storage/branding/' . $filename;
            }
        }

        if (\Illuminate\Support\Facades\Route::has('branding.file')) {
            return route('branding.file', ['filename' => $filename], absolute: true);
        }

        return self::getApplicationBaseUrl() . '/storage/branding/' . $filename;
    }

    /**
     * Build storage URL from APP_URL / ASSET_URL (CLI, queue, API serialization).
     */
    protected static function buildConfiguredStorageUrl(string $path): string
    {
        return self::getApplicationBaseUrl() . '/storage/' . $path;
    }

    /**
     * Build a storage URL from the current HTTP request (subdirectory-safe).
     *
     * API routes strip the deploy prefix from REQUEST_URI in public/index.php,
     * so url()/asset() alone can omit /nexthospital/backend/public. SCRIPT_NAME
     * still reflects the real public path.
     */
    protected static function buildRequestStorageUrl(string $path): string
    {
        $scriptName = request()->server('SCRIPT_NAME', '');

        if ($scriptName && str_ends_with($scriptName, '/index.php')) {
            $basePath = rtrim(str_replace('/index.php', '', $scriptName), '/');

            return request()->getSchemeAndHttpHost() . $basePath . '/storage/' . $path;
        }

        return url('storage/' . $path);
    }

    /**
     * Get asset URL with proper handling
     */
    public static function getAssetUrl(string $path): string
    {
        $path = ltrim($path, '/\\');

        if (!app()->runningInConsole() && request()->hasHeader('Host')) {
            $scriptName = request()->server('SCRIPT_NAME', '');

            if ($scriptName && str_ends_with($scriptName, '/index.php')) {
                $basePath = rtrim(str_replace('/index.php', '', $scriptName), '/');

                return request()->getSchemeAndHttpHost() . $basePath . '/' . $path;
            }

            return url($path);
        }

        return self::getApplicationBaseUrl() . '/' . $path;
    }

    /**
     * Create directory with proper permissions
     */
    public static function createDirectory(string $path): bool
    {
        $path = self::normalizePath($path);
        
        if (!is_dir($path)) {
            $permissions = self::isWindows() ? 0755 : 0755;
            return mkdir($path, $permissions, true);
        }
        
        return true;
    }

    /**
     * Get file with proper path handling
     */
    public static function getFile(string $path): ?string
    {
        $path = self::normalizePath($path);
        
        if (file_exists($path)) {
            return $path;
        }
        
        return null;
    }

    /**
     * Save file with proper path handling
     */
    public static function saveFile(string $path, $content): bool
    {
        $path = self::normalizePath($path);
        
        // Create directory if it doesn't exist
        $directory = dirname($path);
        if (!self::createDirectory($directory)) {
            return false;
        }
        
        return file_put_contents($path, $content) !== false;
    }

    /**
     * Normalize a stored file path to a public-disk relative path (e.g. branding/logo.png).
     * Handles legacy absolute paths and accidental public/ prefixes.
     */
    public static function normalizeStorageRelativePath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', trim($path));

        // Absolute filesystem path (Windows drive or Unix root)
        if (preg_match('#^[A-Za-z]:/#', $normalized) || str_starts_with($normalized, '/')) {
            $markers = [
                '/storage/app/public/',
                'storage/app/public/',
            ];

            foreach ($markers as $marker) {
                $pos = stripos($normalized, $marker);
                if ($pos !== false) {
                    $normalized = substr($normalized, $pos + strlen($marker));
                    break;
                }
            }
        }

        $normalized = ltrim($normalized, '/');

        // Paths derived from storage/app via getRelativePath() may include public/
        if (str_starts_with($normalized, 'public/')) {
            $normalized = substr($normalized, 7);
        }

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * Get relative path from storage
     */
    public static function getRelativePath(string $fullPath): string
    {
        $normalized = self::normalizeStorageRelativePath($fullPath);
        if ($normalized !== null && !preg_match('#^[A-Za-z]:/#', $normalized) && !str_starts_with($normalized, '/')) {
            return $normalized;
        }

        $storagePath = self::getStoragePath();
        $fullPath = self::normalizePath($fullPath);

        if (Str::startsWith($fullPath, $storagePath)) {
            $relative = ltrim(str_replace($storagePath, '', $fullPath), DIRECTORY_SEPARATOR);
            return self::normalizeStorageRelativePath(str_replace('\\', '/', $relative)) ?? '';
        }

        $uploadsPath = self::getUploadPath();
        if (Str::startsWith($fullPath, $uploadsPath)) {
            $relative = ltrim(str_replace($uploadsPath, '', $fullPath), DIRECTORY_SEPARATOR);
            $prefix = str_replace('\\', '/', ltrim(str_replace(storage_path('app/public'), '', $uploadsPath), DIRECTORY_SEPARATOR));
            $relative = trim($prefix . '/' . str_replace('\\', '/', $relative), '/');

            return self::normalizeStorageRelativePath($relative) ?? $relative;
        }

        return self::normalizeStorageRelativePath(str_replace('\\', '/', $fullPath)) ?? str_replace('\\', '/', $fullPath);
    }

    /**
     * Get environment-specific configuration
     */
    public static function getEnvironmentConfig(string $key, $default = null)
    {
        $os = self::getOS();
        $environment = self::isCloud() ? 'cloud' : 'local';
        
        // Try OS-specific config first
        $osKey = "{$key}.{$os}";
        if (Config::has($osKey)) {
            return Config::get($osKey);
        }
        
        // Try environment-specific config
        $envKey = "{$key}.{$environment}";
        if (Config::has($envKey)) {
            return Config::get($envKey);
        }
        
        // Fall back to default
        return Config::get($key, $default);
    }

    /**
     * Get file upload path based on environment
     */
    public static function getUploadPath(string $type = 'general'): string
    {
        $basePath = self::getEnvironmentConfig('files.upload_path', 'uploads');
        
        // If it's a relative path, make it absolute from storage/app/public
        if (!self::isAbsolutePath($basePath)) {
            $basePath = storage_path('app/public/' . $basePath);
        }
        
        $path = self::normalizePath($basePath . DIRECTORY_SEPARATOR . $type);
        
        // Ensure directory exists
        self::createDirectory($path);
        
        return $path;
    }
    
    /**
     * Check if path is absolute
     */
    private static function isAbsolutePath(string $path): bool
    {
        if (self::isWindows()) {
            // On Windows, check for drive letter or UNC path
            return preg_match('/^[A-Za-z]:\\\\/', $path) || strpos($path, '\\\\') === 0;
        } else {
            // On Unix-like systems, check if path starts with /
            return strpos($path, '/') === 0;
        }
    }

    /**
     * Get temporary file path
     */
    public static function getTempPath(string $filename = ''): string
    {
        $tempPath = self::getEnvironmentConfig('files.temp_path', sys_get_temp_dir());
        
        if (!empty($filename)) {
            $tempPath = self::normalizePath($tempPath . DIRECTORY_SEPARATOR . $filename);
        }
        
        return $tempPath;
    }

    /**
     * Get log file path
     */
    public static function getLogPath(string $filename = ''): string
    {
        $logPath = self::getEnvironmentConfig('files.log_path', storage_path('logs'));
        
        if (!empty($filename)) {
            $logPath = self::normalizePath($logPath . DIRECTORY_SEPARATOR . $filename);
        }
        
        return $logPath;
    }

    /**
     * Get cache path
     */
    public static function getCachePath(string $filename = ''): string
    {
        $cachePath = self::getEnvironmentConfig('files.cache_path', storage_path('framework/cache'));
        
        if (!empty($filename)) {
            $cachePath = self::normalizePath($cachePath . DIRECTORY_SEPARATOR . $filename);
        }
        
        return $cachePath;
    }

    /**
     * Get database path (for SQLite)
     */
    public static function getDatabasePath(string $filename = ''): string
    {
        $dbPath = self::getEnvironmentConfig('database.path', database_path());
        
        if (!empty($filename)) {
            $dbPath = self::normalizePath($dbPath . DIRECTORY_SEPARATOR . $filename);
        }
        
        return $dbPath;
    }

    /**
     * Get backup path
     */
    public static function getBackupPath(string $filename = ''): string
    {
        $backupPath = self::getEnvironmentConfig('files.backup_path', storage_path('backups'));
        
        if (!empty($filename)) {
            $backupPath = self::normalizePath($backupPath . DIRECTORY_SEPARATOR . $filename);
        }
        
        // Ensure directory exists
        self::createDirectory($backupPath);
        
        return $backupPath;
    }

    /**
     * Get system information
     */
    public static function getSystemInfo(): array
    {
        return [
            'os' => self::getOS(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'is_windows' => self::isWindows(),
            'is_macos' => self::isMacOS(),
            'is_linux' => self::isLinux(),
            'is_cloud' => self::isCloud(),
            'is_local' => self::isLocal(),
            'separator' => DIRECTORY_SEPARATOR,
            'temp_dir' => sys_get_temp_dir(),
            'storage_path' => storage_path(),
            'public_path' => public_path(),
        ];
    }

    /**
     * Validate file path for current OS
     */
    public static function validatePath(string $path): bool
    {
        $path = self::normalizePath($path);
        
        // Check for invalid characters based on OS
        if (self::isWindows()) {
            // Windows invalid characters
            $invalidChars = ['<', '>', ':', '"', '|', '?', '*'];
            foreach ($invalidChars as $char) {
                if (strpos($path, $char) !== false) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Get file extension
     */
    public static function getFileExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Generate unique filename
     */
    public static function generateUniqueFilename(string $originalName, string $directory = ''): string
    {
        $extension = self::getFileExtension($originalName);
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $name = Str::slug($name);
        
        $filename = $name . '_' . time() . '_' . Str::random(8);
        if (!empty($extension)) {
            $filename .= '.' . $extension;
        }
        
        // Ensure filename is unique in directory
        if (!empty($directory)) {
            $directory = self::normalizePath($directory);
            $counter = 1;
            $originalFilename = $filename;
            
            while (file_exists($directory . DIRECTORY_SEPARATOR . $filename)) {
                $filename = pathinfo($originalFilename, PATHINFO_FILENAME) . '_' . $counter . '.' . $extension;
                $counter++;
            }
        }
        
        return $filename;
    }

    /**
     * Get file size in human readable format
     */
    public static function getHumanReadableSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file is image
     */
    public static function isImage(string $filename): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        $extension = self::getFileExtension($filename);
        
        return in_array($extension, $imageExtensions);
    }

    /**
     * Check if file is document
     */
    public static function isDocument(string $filename): bool
    {
        $documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
        $extension = self::getFileExtension($filename);
        
        return in_array($extension, $documentExtensions);
    }

    /**
     * Get XAMPP-specific paths for Windows
     */
    public static function getXamppPath(string $type = 'htdocs'): string
    {
        if (!self::isWindows()) {
            return '';
        }

        $xamppPaths = [
            'htdocs' => 'C:\\xampp\\htdocs',
            'apache' => 'C:\\xampp\\apache',
            'mysql' => 'C:\\xampp\\mysql',
            'php' => 'C:\\xampp\\php',
        ];

        return $xamppPaths[$type] ?? '';
    }

    /**
     * Check if running in XAMPP environment
     */
    public static function isXampp(): bool
    {
        if (!self::isWindows()) {
            return false;
        }

        // Check if XAMPP paths exist
        $xamppHtdocs = self::getXamppPath('htdocs');
        return !empty($xamppHtdocs) && is_dir($xamppHtdocs);
    }

    /**
     * Get Windows-compatible storage URL
     */
    public static function getWindowsStorageUrl(string $path): string
    {
        if (!self::isWindows()) {
            return self::getStorageUrl($path);
        }

        return self::getStorageUrl($path);
    }
}
