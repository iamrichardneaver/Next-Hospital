<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Platform Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration handles file paths, URLs, and environment-specific
    | settings for Windows, macOS, Linux, and cloud environments.
    |
    */

    'files' => [
        'upload_path' => env('FILES_UPLOAD_PATH', 'uploads'),
        'temp_path' => env('FILES_TEMP_PATH', sys_get_temp_dir()),
        'log_path' => env('FILES_LOG_PATH', storage_path('logs')),
        'cache_path' => env('FILES_CACHE_PATH', storage_path('framework/cache')),
        'backup_path' => env('FILES_BACKUP_PATH', storage_path('backups')),
        'public_path' => env('FILES_PUBLIC_PATH', public_path()),
    ],

    'database' => [
        'path' => env('DB_PATH', database_path()),
        'backup_path' => env('DB_BACKUP_PATH', storage_path('backups/database')),
    ],

    'storage' => [
        'disk' => env('FILESYSTEM_DISK', 'local'),
        'public_disk' => env('FILESYSTEM_PUBLIC_DISK', 'public'),
        'cloud_disk' => env('FILESYSTEM_CLOUD_DISK', 's3'),
    ],

    'urls' => [
        'base_url' => env('APP_URL', 'http://localhost'),
        'storage_url' => env('STORAGE_URL', '/storage'),
        'public_url' => env('PUBLIC_URL', '/'),
        'force_https' => env('FORCE_HTTPS', false),
        'cdn_url' => env('CDN_URL'),
        'asset_url' => env('ASSET_URL'),
    ],

    // Windows-specific configuration
    'windows' => [
        'files' => [
            'upload_path' => 'uploads', // Use relative path for XAMPP compatibility
            'temp_path' => sys_get_temp_dir(),
            'log_path' => 'logs',
            'backup_path' => 'backups',
        ],
        'database' => [
            'path' => 'database',
            'backup_path' => 'backups/database',
        ],
        'permissions' => [
            'file' => 0644,
            'directory' => 0755,
        ],
        'xampp' => [
            'htdocs_path' => 'C:\\xampp\\htdocs',
            'apache_path' => 'C:\\xampp\\apache',
            'mysql_path' => 'C:\\xampp\\mysql',
            'php_path' => 'C:\\xampp\\php',
        ],
    ],

    // macOS-specific configuration
    'macos' => [
        'files' => [
            'upload_path' => '/Users/Shared/nexthospital/uploads',
            'temp_path' => '/tmp/nexthospital',
            'log_path' => '/Users/Shared/nexthospital/logs',
            'backup_path' => '/Users/Shared/nexthospital/backups',
        ],
        'database' => [
            'path' => '/Users/Shared/nexthospital/database',
            'backup_path' => '/Users/Shared/nexthospital/backups/database',
        ],
        'permissions' => [
            'file' => 0644,
            'directory' => 0755,
        ],
    ],

    // Linux-specific configuration
    'linux' => [
        'files' => [
            'upload_path' => '/var/www/nexthospital/uploads',
            'temp_path' => '/tmp/nexthospital',
            'log_path' => '/var/log/nexthospital',
            'backup_path' => '/var/backups/nexthospital',
        ],
        'database' => [
            'path' => '/var/lib/nexthospital/database',
            'backup_path' => '/var/backups/nexthospital/database',
        ],
        'permissions' => [
            'file' => 0644,
            'directory' => 0755,
        ],
    ],

    // Cloud-specific configuration
    'cloud' => [
        'files' => [
            'upload_path' => 'uploads',
            'temp_path' => 'temp',
            'log_path' => 'logs',
            'backup_path' => 'backups',
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
    ],

    // Local development configuration
    'local' => [
        'files' => [
            'upload_path' => 'uploads',
            'temp_path' => 'temp',
            'log_path' => 'logs',
            'backup_path' => 'backups',
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
            'disk' => 'local',
            'public_disk' => 'public',
        ],
    ],

    // File type configurations
    'file_types' => [
        'images' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'],
            'max_size' => 5 * 1024 * 1024, // 5MB
            'path' => 'images',
        ],
        'documents' => [
            'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
            'max_size' => 10 * 1024 * 1024, // 10MB
            'path' => 'documents',
        ],
        'videos' => [
            'extensions' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'],
            'max_size' => 100 * 1024 * 1024, // 100MB
            'path' => 'videos',
        ],
        'audio' => [
            'extensions' => ['mp3', 'wav', 'ogg', 'aac', 'flac'],
            'max_size' => 20 * 1024 * 1024, // 20MB
            'path' => 'audio',
        ],
    ],

    // Path validation rules
    'validation' => [
        'max_path_length' => 260, // Windows limit
        'forbidden_chars' => [
            'windows' => ['<', '>', ':', '"', '|', '?', '*'],
            'unix' => ['\0'],
        ],
        'forbidden_names' => [
            'windows' => ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'],
            'unix' => [],
        ],
    ],
];
