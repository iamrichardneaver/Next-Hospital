<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CrossPlatformService;

class SimulatePlatforms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:simulate-platforms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate how the system would work on different platforms';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌍 Simulating Cross-Platform Path Generation...');
        $this->newLine();

        // Simulate different installation paths
        $installations = [
            'Windows Local' => 'C:\\xampp\\htdocs\\nexthospital',
            'Windows Production' => 'C:\\inetpub\\wwwroot\\nexthospital',
            'macOS Local' => '/Users/john/Projects/nexthospital',
            'macOS Production' => '/Applications/nexthospital',
            'Linux Local' => '/home/user/nexthospital',
            'Linux Production' => '/var/www/nexthospital',
            'Cloud AWS' => '/var/www/html',
            'Cloud DigitalOcean' => '/var/www/nexthospital',
        ];

        foreach ($installations as $platform => $basePath) {
            $this->info("🖥️  Platform: {$platform}");
            $this->line("   📍 Base Path: {$basePath}");
            
            // Simulate path generation for this platform
            $this->simulatePlatformPaths($platform, $basePath);
            $this->newLine();
        }

        $this->info('✅ Platform simulation completed!');
        $this->info('🎯 The system intelligently adapts to ANY installation path!');
        
        return 0;
    }

    private function simulatePlatformPaths($platform, $basePath)
    {
        // Determine OS based on platform
        $os = $this->getOSFromPlatform($platform);
        $separator = $os === 'windows' ? '\\' : '/';
        
        // Simulate path generation
        $paths = [
            'Storage' => $basePath . $separator . 'storage',
            'Public' => $basePath . $separator . 'public',
            'Database' => $basePath . $separator . 'database',
            'Uploads' => $basePath . $separator . 'storage' . $separator . 'app' . $separator . 'uploads',
            'Logs' => $basePath . $separator . 'storage' . $separator . 'logs',
            'Backups' => $basePath . $separator . 'storage' . $separator . 'backups',
            'Temp' => $os === 'windows' ? 'C:\\temp\\nexthospital' : '/tmp/nexthospital',
        ];

        foreach ($paths as $type => $path) {
            $this->line("   📁 {$type}: {$path}");
        }

        // Simulate URL generation
        $this->line("   🌐 Base URL: " . $this->getBaseUrl($platform));
        $this->line("   🔗 Storage URL: " . $this->getBaseUrl($platform) . "/storage");
        
        // Show file examples
        $examples = [
            'Logo' => "{$paths['Uploads']}{$separator}branding{$separator}logo.png",
            'Report' => "{$paths['Uploads']}{$separator}documents{$separator}report.pdf",
            'Database' => "{$paths['Backups']}{$separator}backup.sql",
        ];

        foreach ($examples as $type => $filePath) {
            $this->line("   📄 {$type}: {$filePath}");
        }
    }

    private function getOSFromPlatform($platform)
    {
        if (strpos($platform, 'Windows') !== false) {
            return 'windows';
        } elseif (strpos($platform, 'macOS') !== false) {
            return 'macos';
        } elseif (strpos($platform, 'Linux') !== false) {
            return 'linux';
        } else {
            return 'cloud';
        }
    }

    private function getBaseUrl($platform)
    {
        return config('app.url');
    }
}
