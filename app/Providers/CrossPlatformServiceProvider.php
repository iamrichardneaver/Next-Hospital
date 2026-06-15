<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CrossPlatformService;
use App\Services\FileStorageService;
use App\Helpers\EnvironmentHelper;

class CrossPlatformServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CrossPlatformService::class, function ($app) {
            return new CrossPlatformService();
        });

        $this->app->singleton(FileStorageService::class, function ($app) {
            return new FileStorageService($app->make(CrossPlatformService::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Setup environment-specific configuration
        EnvironmentHelper::setupEnvironment();
        
        // Ensure required directories exist
        $this->ensureDirectoriesExist();
        
        // Set up storage link if needed
        $this->setupStorageLink();
    }

    /**
     * Ensure required directories exist
     */
    private function ensureDirectoriesExist(): void
    {
        // Only create directories in local environment
        if (!CrossPlatformService::isLocal()) {
            return;
        }

        $directories = [
            CrossPlatformService::getUploadPath(),
            CrossPlatformService::getUploadPath('images'),
            CrossPlatformService::getUploadPath('documents'),
            CrossPlatformService::getUploadPath('videos'),
            CrossPlatformService::getUploadPath('audio'),
            CrossPlatformService::getUploadPath('branding'),
            CrossPlatformService::getUploadPath('mobile-app'),
            CrossPlatformService::getTempPath(),
            CrossPlatformService::getLogPath(),
            CrossPlatformService::getBackupPath(),
            CrossPlatformService::getCachePath(),
        ];

        foreach ($directories as $directory) {
            try {
                if (!CrossPlatformService::createDirectory($directory)) {
                    \Log::warning("Failed to create directory: {$directory}");
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to create directory {$directory}: " . $e->getMessage());
            }
        }
    }

    /**
     * Setup storage link if needed
     */
    private function setupStorageLink(): void
    {
        // Skip storage link setup in production environment
        if (!CrossPlatformService::isLocal()) {
            return;
        }

        $publicPath = public_path('storage');
        $storagePath = storage_path('app/public');
        
        // Only create link if it doesn't exist and we're in local environment
        try {
            // Check if link exists without hitting open_basedir restrictions
            if (!is_link($publicPath) && !is_dir($publicPath)) {
                if (CrossPlatformService::isWindows()) {
                    // Use Windows junction
                    exec("mklink /J \"{$publicPath}\" \"{$storagePath}\"");
                } else {
                    // Use symbolic link for Unix-like systems
                    symlink($storagePath, $publicPath);
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Failed to create storage link: " . $e->getMessage());
        }
    }
}
