<?php

namespace App\Support;

use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class PermissionScanner
{
    /**
     * Permission name pattern (web guard).
     */
    public const NAME_PATTERN = '/^[a-z][a-z0-9_.]+$/';

    /**
     * @var list<string>
     */
    private const ACTION_PREFIXES = [
        'acknowledge_', 'approve_', 'calculate_', 'call_', 'cancel_', 'complete_', 'create_',
        'delete_', 'dispense_', 'download_', 'edit_', 'enter_', 'export_', 'generate_',
        'manage_', 'mark_', 'perform_', 'print_', 'process_', 'receive_', 'record_', 'resolve_',
        'search_', 'send_', 'serve_', 'sign_', 'start_', 'triage_', 'upload_', 'verify_', 'view_',
        'amend_',
    ];

    /**
     * @var list<string>
     */
    private const EXCLUDED_NAMES = [
        'web',
        'sanctum',
        'api',
        'admin',
        'super_admin',
        'doctor',
        'nurse',
        'pharmacist',
        'receptionist',
        'cashier',
        'accountant',
        'patient',
        'created_at',
        'updated_at',
        'guard_name',
        'model_type',
    ];

    /**
     * Single-word resources that are model columns, not permission targets.
     *
     * @var list<string>
     */
    private const EXCLUDED_RESOURCES = [
        'time', 'date', 'status', 'amount', 'count', 'index',
        'management', 'performance', 'processing', 'recording', 'records', 'server', 'sender',
        'viewer', 'uploads', 'uploader', 'dispenser', 'performer', 'processor', 'approver',
        'info', 'url', 'path', 'name', 'type', 'code', 'level', 'notes', 'reason',
        'policy', 'term', 'items', 'email', 'quantity', 'week', 'month', 'today', 'history',
        'position', 'logs', 'files', 'slots', 'fee', 'roles', 'theatres', 'store', 'show',
        'private', 'sync', 'consent', 'debug', 'test', 'start', 'end', 'destroy', 'update',
    ];

    /**
     * Resource suffixes that indicate DB columns, not permission names.
     *
     * @var list<string>
     */
    private const METADATA_SUFFIXES = [
        '_at', '_by', '_id', '_url', '_count', '_date', '_time', '_enabled', '_notes',
        '_reason', '_policy', '_name', '_type', '_code', '_number', '_level', '_path',
        '_settings', '_info', '_term', '_amount', '_percentage', '_items', '_email',
        '_status', '_quantity', '_week', '_month', '_today', '_times', '_confirmation',
        '_logs', '_position', '_history', '_fee', '_slots', '_reminders', '_receipts',
        '_timestamp', '_via', '_store', '_index', '_show', '_destroy', '_debug', '_test',
        '_consent', '_edit', '_cancel', '_create', '_update', '_start', '_end',
    ];

    /**
     * @var list<string>|null
     */
    private static ?array $cachedNames = null;

    /**
     * Scan the codebase and return a deduplicated, sorted list of permission names.
     *
     * @return list<string>
     */
    public static function scan(): array
    {
        if (self::$cachedNames !== null) {
            return self::$cachedNames;
        }

        $names = [];

        foreach (self::scanPaths() as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $isSeederPath = str_contains(str_replace('\\', '/', $path), '/database/seeders');

            foreach (self::filesIn($path) as $file) {
                $content = file_get_contents($file->getPathname());
                if (!is_string($content) || $content === '') {
                    continue;
                }

                foreach (self::extractFromContent($content, $isSeederPath) as $name) {
                    $names[$name] = true;
                }
            }
        }

        $result = array_keys($names);
        sort($result);

        self::$cachedNames = $result;

        return $result;
    }

    /**
     * Clear the in-memory scan cache (useful in tests).
     */
    public static function clearCache(): void
    {
        self::$cachedNames = null;
    }

    /**
     * Fingerprint of config + scanned source files for boot-time change detection.
     */
    public static function sourceFingerprint(): string
    {
        $parts = [];

        $configPath = config_path('permissions.php');
        if (is_file($configPath)) {
            $parts[] = 'config:' . md5_file($configPath) . ':' . filemtime($configPath);
        }

        foreach (self::scanPaths() as $path) {
            if (!is_dir($path)) {
                continue;
            }

            foreach (self::filesIn($path) as $file) {
                $stat = self::safeFileStat($file);
                if ($stat === null) {
                    continue;
                }

                $relative = str_replace('\\', '/', Str::after($file->getPathname(), base_path() . DIRECTORY_SEPARATOR));
                $parts[] = $relative . ':' . $stat['mtime'] . ':' . $stat['size'];
            }
        }

        sort($parts);

        return hash('sha256', implode('|', $parts));
    }

    /**
     * @return list<string>
     */
    public static function scanPaths(): array
    {
        $paths = config('permissions.scan_paths', [
            base_path('routes'),
            base_path('app'),
            base_path('resources/views'),
            base_path('database/seeders'),
        ]);

        return array_values(array_filter($paths, 'is_dir'));
    }

    /**
     * @return list<SplFileInfo>
     */
    private static function filesIn(string $directory): array
    {
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
            );
        } catch (\UnexpectedValueException) {
            return [];
        }

        $files = [];
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }

            if (self::isScannablePhpFile($file->getPathname())) {
                $files[] = $file;
            }
        }

        return $files;
    }

    private static function isScannablePhpFile(string $pathname): bool
    {
        if (!is_file($pathname) || !is_readable($pathname)) {
            return false;
        }

        $basename = basename(str_replace('\\', '/', $pathname));
        if ($basename === '' || $basename === '.' || $basename === '.php' || $basename === '.blade.php') {
            return false;
        }

        if (str_ends_with($basename, '.blade.php')) {
            return true;
        }

        return str_ends_with($basename, '.php');
    }

    /**
     * @return array{mtime: int, size: int}|null
     */
    private static function safeFileStat(SplFileInfo $file): ?array
    {
        $pathname = $file->getPathname();
        if (!self::isScannablePhpFile($pathname)) {
            return null;
        }

        try {
            return [
                'mtime' => $file->getMTime(),
                'size' => $file->getSize(),
            ];
        } catch (\RuntimeException) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    public static function extractFromContent(string $content, bool $includeSeederArrays = false): array
    {
        $names = [];

        $patterns = [
            '/permission:([a-z][a-z0-9_.|]+)/i',
            '/@can(?:any|not)?\(\s*[\'"]([a-z][a-z0-9_.]+)[\'"]/i',
            '/@can(?:any|not)?\(\s*\[\s*[\'"]([a-z][a-z0-9_.]+)[\'"]/i',
            '/(?:->can|hasPermissionTo|givePermissionTo|syncPermissions|revokePermissionTo)\(\s*[\'"]([a-z][a-z0-9_.]+)[\'"]/i',
            '/(?:->can|hasPermissionTo|givePermissionTo|syncPermissions|revokePermissionTo)\(\s*\[\s*[\'"]([a-z][a-z0-9_.]+)[\'"]/i',
            '/Permission::firstOrCreate\s*\(\s*\[\s*[\'"]name[\'"]\s*=>\s*[\'"]([a-z][a-z0-9_.]+)[\'"]/i',
            '/Permission::findOrCreate\s*\(\s*[\'"]([a-z][a-z0-9_.]+)[\'"]/i',
            '/Permission::create\s*\(\s*\[\s*[\'"]name[\'"]\s*=>\s*[\'"]([a-z][a-z0-9_.]+)[\'"]/i',
            '/[\'"]required_permission[\'"]\s*=>\s*[\'"]([a-z][a-z0-9_.]+)[\'"]/i',
            '/authorize\s*\(\s*[\'"]([a-z][a-z0-9_.]+)[\'"]/i',
            '/CheckPermission:([a-z][a-z0-9_.|]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (!preg_match_all($pattern, $content, $matches)) {
                continue;
            }

            foreach ($matches[1] as $raw) {
                foreach (explode('|', $raw) as $candidate) {
                    $normalized = self::normalize($candidate);
                    if ($normalized !== null) {
                        $names[] = $normalized;
                    }
                }
            }
        }

        if ($includeSeederArrays) {
            if (preg_match_all("/^\s*'([a-z][a-z0-9_.]+)'\s*,?\s*$/m", $content, $matches)) {
                foreach ($matches[1] as $candidate) {
                    $normalized = self::normalize($candidate);
                    if ($normalized !== null) {
                        $names[] = $normalized;
                    }
                }
            }

            if (preg_match_all("/^\s*'([a-z][a-z0-9_.]+)'\s*=>\s*(?:'[^']*'|null)\s*,?\s*$/m", $content, $matches)) {
                foreach ($matches[1] as $candidate) {
                    $normalized = self::normalize($candidate);
                    if ($normalized !== null) {
                        $names[] = $normalized;
                    }
                }
            }
        }

        return array_values(array_unique($names));
    }

    public static function normalize(string $name): ?string
    {
        $name = strtolower(trim($name));

        if ($name === '' || !preg_match(self::NAME_PATTERN, $name)) {
            return null;
        }

        if (in_array($name, self::EXCLUDED_NAMES, true)) {
            return null;
        }

        if (str_starts_with($name, 'teleconsultation.')) {
            $rest = substr($name, strlen('teleconsultation.'));

            return $rest !== '' && preg_match('/^[a-z][a-z0-9_.]+$/', $rest) ? $name : null;
        }

        foreach (self::ACTION_PREFIXES as $prefix) {
            if (!str_starts_with($name, $prefix)) {
                continue;
            }

            $resource = substr($name, strlen($prefix));
            if ($resource === '' || strlen($resource) < 2 || self::looksLikeMetadata($resource)) {
                return null;
            }

            if (!str_contains($resource, '_') && in_array($resource, self::EXCLUDED_RESOURCES, true)) {
                return null;
            }

            return $name;
        }

        return null;
    }

    private static function looksLikeMetadata(string $resource): bool
    {
        foreach (self::METADATA_SUFFIXES as $suffix) {
            if (str_ends_with($resource, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
