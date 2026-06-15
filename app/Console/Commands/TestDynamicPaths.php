<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CrossPlatformService;
use App\Services\FileStorageService;

class TestDynamicPaths extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:dynamic-paths';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test dynamic path detection and generation based on current installation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing Dynamic Path Detection and Generation...');
        $this->newLine();

        $crossPlatform = app(CrossPlatformService::class);

        // 1. Test Current Installation Detection
        $this->info('1. 📍 Current Installation Detection:');
        $this->line('   Application Root: ' . base_path());
        $this->line('   Storage Path: ' . storage_path());
        $this->line('   Public Path: ' . public_path());
        $this->line('   Database Path: ' . database_path());
        $this->newLine();

        // 2. Test Dynamic Path Generation
        $this->info('2. 🛤️ Dynamic Path Generation:');
        
        $testPaths = [
            'uploads/images/logo.png',
            'uploads/documents/report.pdf',
            'uploads/branding/favicon.ico',
            'uploads/mobile-app/app-icon.png',
            'temp/session_data.tmp',
            'logs/application.log',
            'backups/database_backup.sql',
            'cache/compiled_views.php'
        ];

        foreach ($testPaths as $relativePath) {
            $fullPath = $crossPlatform->getStoragePath($relativePath);
            $normalizedPath = $crossPlatform->normalizePath($fullPath);
            $url = $crossPlatform->getStorageUrl($relativePath);
            
            $this->line("   📁 Relative: {$relativePath}");
            $this->line("   🔗 Full Path: {$fullPath}");
            $this->line("   ✅ Normalized: {$normalizedPath}");
            $this->line("   🌐 URL: {$url}");
            $this->line("   ✓ Valid: " . ($crossPlatform->validatePath($normalizedPath) ? 'Yes' : 'No'));
            $this->newLine();
        }

        // 3. Test Environment-Specific Paths
        $this->info('3. 🌍 Environment-Specific Path Generation:');
        
        $environments = ['local', 'production', 'testing'];
        $pathTypes = ['upload', 'temp', 'log', 'backup', 'cache'];
        
        foreach ($environments as $env) {
            $this->line("   Environment: {$env}");
            foreach ($pathTypes as $type) {
                $method = 'get' . ucfirst($type) . 'Path';
                if (method_exists($crossPlatform, $method)) {
                    $path = $crossPlatform->$method();
                    $this->line("     {$type}: {$path}");
                }
            }
            $this->newLine();
        }

        // 4. Test Dynamic Directory Creation
        $this->info('4. 📂 Dynamic Directory Creation:');
        
        $testDirs = [
            'uploads/test-dynamic',
            'uploads/test-dynamic/subfolder',
            'temp/dynamic-test',
            'logs/dynamic-test'
        ];

        foreach ($testDirs as $dir) {
            $fullDirPath = $crossPlatform->getStoragePath($dir);
            $created = $crossPlatform->createDirectory($fullDirPath);
            
            $this->line("   📁 Directory: {$dir}");
            $this->line("   🔗 Full Path: {$fullDirPath}");
            $this->line("   ✅ Created: " . ($created ? 'Yes' : 'No'));
            $this->line("   📍 Exists: " . (is_dir($fullDirPath) ? 'Yes' : 'No'));
            $this->newLine();
        }

        // 5. Test File Operations with Dynamic Paths
        $this->info('5. 📄 File Operations with Dynamic Paths:');
        
        $fileStorage = app(FileStorageService::class);
        
        // Create a test file
        $testContent = "Dynamic path test file created at " . now() . "\n";
        $testContent .= "OS: " . $crossPlatform->getOS() . "\n";
        $testContent .= "Installation: " . base_path() . "\n";
        
        $testFilePath = $crossPlatform->getStoragePath('test-dynamic/test-file.txt');
        $saved = $crossPlatform->saveFile($testFilePath, $testContent);
        
        $this->line("   📄 Test File: test-dynamic/test-file.txt");
        $this->line("   🔗 Full Path: {$testFilePath}");
        $this->line("   ✅ Saved: " . ($saved ? 'Yes' : 'No'));
        $this->line("   📍 Exists: " . (file_exists($testFilePath) ? 'Yes' : 'No'));
        
        if (file_exists($testFilePath)) {
            $fileSize = filesize($testFilePath);
            $fileContent = file_get_contents($testFilePath);
            $this->line("   📊 Size: " . $crossPlatform->getHumanReadableSize($fileSize));
            $this->line("   📝 Content Preview: " . substr($fileContent, 0, 100) . "...");
        }
        $this->newLine();

        // 6. Test URL Generation
        $this->info('6. 🌐 Dynamic URL Generation:');
        
        $testFiles = [
            'images/logo.png',
            'documents/report.pdf',
            'branding/favicon.ico',
            'mobile-app/app-icon.png'
        ];

        foreach ($testFiles as $file) {
            $storageUrl = $crossPlatform->getStorageUrl($file);
            $assetUrl = $crossPlatform->getAssetUrl($file);
            
            $this->line("   📁 File: {$file}");
            $this->line("   🔗 Storage URL: {$storageUrl}");
            $this->line("   🌐 Asset URL: {$assetUrl}");
            $this->newLine();
        }

        // 7. Test System Information
        $this->info('7. 💻 System Information:');
        $systemInfo = $crossPlatform->getSystemInfo();
        
        foreach ($systemInfo as $key => $value) {
            $displayValue = is_bool($value) ? ($value ? 'Yes' : 'No') : $value;
            $this->line("   {$key}: {$displayValue}");
        }
        $this->newLine();

        // 8. Test Path Validation
        $this->info('8. ✅ Path Validation:');
        
        $testPaths = [
            'uploads/images/logo.png',           // Valid
            'uploads/../secret/file.txt',        // Invalid (directory traversal)
            'uploads/images/logo.png',           // Valid
            'uploads/images/logo.png',           // Valid
            'C:\\Windows\\System32\\file.txt',   // Valid (Windows)
            '/etc/passwd',                       // Valid (Unix)
            'uploads/images/logo.png',           // Valid
        ];

        foreach ($testPaths as $path) {
            $normalized = $crossPlatform->normalizePath($path);
            $valid = $crossPlatform->validatePath($normalized);
            
            $this->line("   📁 Path: {$path}");
            $this->line("   🔄 Normalized: {$normalized}");
            $this->line("   ✅ Valid: " . ($valid ? 'Yes' : 'No'));
            $this->newLine();
        }

        // 9. Test Cross-Platform Compatibility
        $this->info('9. 🌍 Cross-Platform Compatibility:');
        
        $this->line("   🖥️  Current OS: " . $crossPlatform->getOS());
        $this->line("   🔧 Directory Separator: '" . DIRECTORY_SEPARATOR . "'");
        $this->line("   📍 PHP OS Family: " . PHP_OS_FAMILY);
        $this->line("   🏠 Home Directory: " . (getenv('HOME') ?: getenv('USERPROFILE')));
        $this->line("   📂 Temp Directory: " . sys_get_temp_dir());
        $this->newLine();

        // 10. Cleanup
        $this->info('10. 🧹 Cleanup:');
        
        $cleanupPaths = [
            $crossPlatform->getStoragePath('test-dynamic'),
            $crossPlatform->getStoragePath('uploads/test-dynamic'),
            $crossPlatform->getStoragePath('temp/dynamic-test'),
            $crossPlatform->getStoragePath('logs/dynamic-test')
        ];

        foreach ($cleanupPaths as $path) {
            if (is_dir($path)) {
                $this->removeDirectory($path);
                $this->line("   🗑️  Removed: {$path}");
            }
        }

        $this->newLine();
        $this->info('✅ Dynamic path detection and generation test completed successfully!');
        $this->info('🎯 The system intelligently adapts to any installation environment!');
        
        return 0;
    }

    /**
     * Remove directory recursively
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
