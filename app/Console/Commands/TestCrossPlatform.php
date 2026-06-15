<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CrossPlatformService;
use App\Services\FileStorageService;
use App\Models\BrandingSetting;

class TestCrossPlatform extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:cross-platform';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test cross-platform functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Cross-Platform Functionality...');
        $this->newLine();

        // Test OS detection
        $this->info('1. Operating System Detection:');
        $this->line('   OS: ' . CrossPlatformService::getOS());
        $this->line('   Is Windows: ' . (CrossPlatformService::isWindows() ? 'Yes' : 'No'));
        $this->line('   Is macOS: ' . (CrossPlatformService::isMacOS() ? 'Yes' : 'No'));
        $this->line('   Is Linux: ' . (CrossPlatformService::isLinux() ? 'Yes' : 'No'));
        $this->line('   Is Cloud: ' . (CrossPlatformService::isCloud() ? 'Yes' : 'No'));
        $this->line('   Is Local: ' . (CrossPlatformService::isLocal() ? 'Yes' : 'No'));
        $this->newLine();

        // Test path normalization
        $this->info('2. Path Normalization:');
        $testPaths = [
            'uploads/images/logo.png',
            'uploads\\images\\logo.png',
            '/var/www/uploads/images/logo.png',
            'C:\\uploads\\images\\logo.png',
        ];

        foreach ($testPaths as $path) {
            $normalized = CrossPlatformService::normalizePath($path);
            $this->line("   Original: {$path}");
            $this->line("   Normalized: {$normalized}");
            $this->line("   Valid: " . (CrossPlatformService::validatePath($normalized) ? 'Yes' : 'No'));
            $this->newLine();
        }

        // Test directory creation
        $this->info('3. Directory Creation:');
        $testDir = CrossPlatformService::getUploadPath('test');
        $this->line("   Test Directory: {$testDir}");
        
        if (CrossPlatformService::createDirectory($testDir)) {
            $this->line("   ✓ Directory created successfully");
        } else {
            $this->line("   ✗ Failed to create directory");
        }
        $this->newLine();

        // Test file operations
        $this->info('4. File Operations:');
        $testFile = CrossPlatformService::normalizePath($testDir . DIRECTORY_SEPARATOR . 'test.txt');
        $content = "Cross-platform test file created at " . now();
        
        if (CrossPlatformService::saveFile($testFile, $content)) {
            $this->line("   ✓ File created successfully");
            $this->line("   File path: {$testFile}");
            
            if (file_exists($testFile)) {
                $this->line("   ✓ File exists and is readable");
                $this->line("   File size: " . CrossPlatformService::getHumanReadableSize(filesize($testFile)));
            }
        } else {
            $this->line("   ✗ Failed to create file");
        }
        $this->newLine();

        // Test URL generation
        $this->info('5. URL Generation:');
        $relativePath = 'test/test.txt';
        $storageUrl = CrossPlatformService::getStorageUrl($relativePath);
        $assetUrl = CrossPlatformService::getAssetUrl('images/logo.png');
        
        $this->line("   Storage URL: {$storageUrl}");
        $this->line("   Asset URL: {$assetUrl}");
        $this->newLine();

        // Test system info
        $this->info('6. System Information:');
        $systemInfo = CrossPlatformService::getSystemInfo();
        foreach ($systemInfo as $key => $value) {
            $this->line("   {$key}: " . (is_bool($value) ? ($value ? 'Yes' : 'No') : $value));
        }
        $this->newLine();

        // Test branding settings
        $this->info('7. Branding Settings Test:');
        $branding = BrandingSetting::current();
        $this->line("   Platform Name: {$branding->platform_name}");
        $this->line("   Logo URL: " . ($branding->logo_url ?? 'Not set'));
        $this->line("   Logo Path: " . ($branding->logo_path ?? 'Not set'));
        $this->newLine();

        // Test file storage service
        $this->info('8. File Storage Service Test:');
        $fileStorage = app(FileStorageService::class);
        $stats = $fileStorage->getStorageStats();
        $this->line("   Total Size: {$stats['total_size_human']}");
        $this->line("   File Count: {$stats['file_count']}");
        $this->line("   Upload Path: {$stats['upload_path']}");
        $this->newLine();

        // Cleanup
        $this->info('9. Cleanup:');
        if (file_exists($testFile)) {
            unlink($testFile);
            $this->line("   ✓ Test file deleted");
        }
        
        if (is_dir($testDir)) {
            rmdir($testDir);
            $this->line("   ✓ Test directory deleted");
        }

        $this->newLine();
        $this->info('✅ Cross-platform functionality test completed successfully!');
        
        return 0;
    }
}
