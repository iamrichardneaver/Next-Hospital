<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class DemoReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:reset
                            {--force : Allow running even when DEMO_MODE is disabled}
                            {--seed-only : Backwards compatible flag; ignored in SQL baseline mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset database and reseed a realistic Codecanyon demo dataset';

    public function handle(): int
    {
        $demoModeEnabled = (bool) config('app.demo_mode');
        $force = (bool) $this->option('force');

        if (!$demoModeEnabled && !$force) {
            $this->error('DEMO_MODE is disabled. Refusing to reset the database.');
            $this->line('Enable it in backend/.env with DEMO_MODE=true, or rerun with --force.');
            return 1;
        }

        $mysqlUser = (string) config('database.connections.mysql.username');
        $mysqlPassword = (string) (config('database.connections.mysql.password') ?? '');
        $mysqlDb = (string) config('database.connections.mysql.database');
        $mysqlHost = (string) (config('database.connections.mysql.host') ?? '127.0.0.1');

        $demoDir = storage_path('app/demo');
        $baselinePath = $demoDir . DIRECTORY_SEPARATOR . 'demo_baseline.sql';

        if (!File::exists($demoDir)) {
            File::makeDirectory($demoDir, 0755, true);
        }

        $baselineExists = File::exists($baselinePath);
        $baselineSize = $baselineExists ? (int) @filesize($baselinePath) : 0;

        if (!$baselineExists || $baselineSize <= 0) {
            $this->info('🧩 Creating SQL baseline snapshot...');

            $mysqlBin = '/Applications/XAMPP/xamppfiles/bin/mysql';
            $mysqldumpBin = '/Applications/XAMPP/xamppfiles/bin/mysqldump';

            $baseDumpCmd = $mysqldumpBin
                . ' -h ' . escapeshellarg($mysqlHost)
                . ' -u ' . escapeshellarg($mysqlUser);
            if ($mysqlPassword !== '') {
                $baseDumpCmd .= ' -p' . escapeshellarg($mysqlPassword);
            }
            $baseDumpCmd .= ' --databases ' . escapeshellarg($mysqlDb)
                . ' --add-drop-table'
                . ' --single-transaction'
                . ' --no-tablespaces'
                . ' --routines'
                . ' > ' . escapeshellarg($baselinePath);

            $dump = Process::fromShellCommandline($baseDumpCmd);
            $dump->setTimeout(null);
            $dump->run(function () {});

            if (!$dump->isSuccessful()) {
                $this->error('Failed to create SQL baseline snapshot.');
                $this->line($dump->getErrorOutput());
                return 1;
            }

            $baselineSize = (int) @filesize($baselinePath);
            if ($baselineSize <= 0) {
                $this->error('SQL baseline snapshot was created but is empty.');
                return 1;
            }
        }

        $this->info('♻️ Restoring demo baseline from SQL snapshot...');

        $mysqlBin = '/Applications/XAMPP/xamppfiles/bin/mysql';
        $restoreCmd = $mysqlBin
            . ' -h ' . escapeshellarg($mysqlHost)
            . ' -u ' . escapeshellarg($mysqlUser);
        if ($mysqlPassword !== '') {
            $restoreCmd .= ' -p' . escapeshellarg($mysqlPassword);
        }
        $restoreCmd .= ' ' . escapeshellarg($mysqlDb)
            . ' < ' . escapeshellarg($baselinePath);

        $restore = Process::fromShellCommandline($restoreCmd);
        $restore->setTimeout(null);
        $restore->run(function () {});

        if (!$restore->isSuccessful()) {
            $this->error('Failed to restore demo baseline SQL snapshot.');
            $this->line($restore->getErrorOutput());
            return 1;
        }

        // Best-effort: reseed + generate slots. If the schema is incomplete, this may fail,
        // but the baseline restore will still unblock the demo URL.
        try {
            $this->info('🌱 Best-effort seeding demo database...');
            $seedCode = Artisan::call('db:seed', [
                '--class' => \Database\Seeders\DemoDatabaseSeeder::class,
                '--force' => true,
            ]);
            $this->output->write(Artisan::output());
            if ($seedCode !== 0) {
                $this->warn('Demo seeding returned a non-zero status.');
            }
        } catch (\Throwable $e) {
            $this->warn('Best-effort demo seeding skipped: ' . $e->getMessage());
        }

        try {
            $this->info('📅 (Best-effort) Generating appointment slots...');
            $slotCode = Artisan::call('appointments:generate-slots', [
                '--days' => 30,
                '--type' => 'both',
            ]);
            $this->output->write(Artisan::output());
            if ($slotCode !== 0) {
                $this->warn('Appointment slot generation returned a non-zero status.');
            }
        } catch (\Throwable $e) {
            $this->warn('Appointment slot generation skipped: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('✅ Demo reset complete.');
        return 0;
    }
}

