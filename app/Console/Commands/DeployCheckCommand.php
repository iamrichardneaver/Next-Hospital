<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DeployCheckCommand extends Command
{
    protected $signature = 'deploy:check';

    protected $description = 'Warn about Windows/XAMPP paths in cache or vendor, and production misconfiguration';

    /** @var list<string> */
    private array $issues = [];

    /** @var list<string> */
    private array $warnings = [];

    public function handle(): int
    {
        $this->info('Running deployment safety checks...');
        $this->newLine();

        $this->checkBootstrapCache();
        $this->checkCompiledViews();
        $this->checkVendorAutoload();
        $this->checkHtaccessOptions();
        $this->checkProductionEnv();

        foreach ($this->issues as $issue) {
            $this->error('ERROR: '.$issue);
        }

        foreach ($this->warnings as $warning) {
            $this->warn('WARN: '.$warning);
        }

        if ($this->issues !== []) {
            $this->newLine();
            $this->line('Fix: delete bootstrap/cache/*.php, rm -rf storage/framework/views/*, run composer install on the Linux server, then php artisan config:cache.');
            $this->line('See docs/DEPLOYMENT_CLOUD.md');

            return Command::FAILURE;
        }

        if ($this->warnings !== []) {
            $this->newLine();
            $this->comment('Deployment check passed with warnings.');

            return Command::SUCCESS;
        }

        $this->info('Deployment check passed.');

        return Command::SUCCESS;
    }

    private function checkBootstrapCache(): void
    {
        $cacheDir = base_path('bootstrap/cache');

        if (! is_dir($cacheDir)) {
            return;
        }

        $files = glob($cacheDir.'/*.php') ?: [];

        foreach ($files as $file) {
            $contents = @file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            if ($this->containsForeignPath($contents)) {
                $this->issues[] = sprintf(
                    'bootstrap/cache/%s contains Windows or XAMPP absolute paths. Regenerate on the server; do not upload from local.',
                    basename($file)
                );
            }
        }
    }

    private function checkCompiledViews(): void
    {
        $viewsDir = storage_path('framework/views');

        if (! is_dir($viewsDir)) {
            return;
        }

        $files = glob($viewsDir.'/*.php') ?: [];
        $checked = 0;

        foreach ($files as $file) {
            if ($checked >= 50) {
                break;
            }

            $contents = @file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            $checked++;

            if ($this->containsForeignPath($contents)) {
                $this->issues[] = 'storage/framework/views contains Windows or XAMPP paths. Run: rm -rf storage/framework/views/* then php artisan view:cache on the server.';

                return;
            }
        }
    }

    private function checkVendorAutoload(): void
    {
        $vendorDir = base_path('vendor');

        if (! is_dir($vendorDir)) {
            $this->warnings[] = 'vendor/ is missing. Run composer install on the server.';

            return;
        }

        $autoloadFiles = array_merge(
            glob($vendorDir.'/composer/autoload_*.php') ?: [],
            [ $vendorDir.'/autoload.php' ]
        );

        foreach ($autoloadFiles as $file) {
            if (! is_file($file)) {
                continue;
            }

            $contents = @file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            if ($this->containsForeignPath($contents)) {
                $this->issues[] = sprintf(
                    '%s contains Windows or XAMPP paths. Run composer install --no-dev on the Linux server; do not upload vendor/ from Windows.',
                    Str::after($file, base_path().DIRECTORY_SEPARATOR)
                );

                return;
            }
        }
    }

    private function checkHtaccessOptions(): void
    {
        $files = [
            base_path('.htaccess'),
            public_path('.htaccess'),
            storage_path('app/public/.htaccess'),
        ];

        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $contents = @file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            // Plesk's default AllowOverride excludes "Options"; a bare Options
            // directive in .htaccess then 500s every request.
            if (preg_match('/^\s*Options\s/im', $contents)) {
                $this->warnings[] = sprintf(
                    '%s contains an "Options" directive. Plesk/shared hosts often disallow Options in .htaccess (AllowOverride without Options), causing a 500 on every request. Remove the Options line.',
                    Str::after($file, base_path().DIRECTORY_SEPARATOR)
                );
            }
        }
    }

    private function checkProductionEnv(): void
    {
        if (config('app.env') !== 'production') {
            return;
        }

        if (config('app.debug')) {
            $this->warnings[] = 'APP_ENV=production but APP_DEBUG=true. Set APP_DEBUG=false on live servers.';
        }

        if (empty(config('app.key'))) {
            $this->warnings[] = 'APP_KEY is empty. Run php artisan key:generate on the server.';
        }

        $socket = env('DB_SOCKET');

        if (! empty($socket)) {
            $this->warnings[] = 'DB_SOCKET is set in production. Leave empty on Linux/Plesk unless you use a Unix socket intentionally.';
        }

        $appUrl = (string) config('app.url');

        if (Str::contains($appUrl, ['localhost', '127.0.0.1', 'xampp', 'htdocs'])) {
            $this->warnings[] = 'APP_URL looks like a local/XAMPP URL. Set it to your public HTTPS domain.';
        }
    }

    private function containsForeignPath(string $contents): bool
    {
        $patterns = [
            '/C:\\\\(?:xampp|inetpub|Users|Windows)/i',
            '/C:\/(?:xampp|inetpub|Users|Windows)/i',
            '/\/Applications\/XAMPP\//i',
            '/xamppfiles/i',
            '/\\\\xampp\\\\/i',
            '/\/xampp\/htdocs/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $contents)) {
                return true;
            }
        }

        return false;
    }
}
