<?php

namespace App\Console\Commands;

use App\Services\LabTemplateSyncService;
use Illuminate\Console\Command;

class SyncLabRequestTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lab:sync-templates {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync templates from test types to lab requests that are missing templates';

    public function __construct(private LabTemplateSyncService $syncService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
            $this->newLine();
        }

        $stats = $this->syncService->syncAllLabRequests($dryRun);

        if ($stats['updated'] === 0 && $stats['skipped'] === 0 && $stats['errors'] === 0) {
            $this->info('✓ No lab requests need template synchronization');
            return 0;
        }

        $this->info('=== Summary ===');
        $this->table(
            ['Status', 'Count'],
            [
                ['Would be updated' . ($dryRun ? '' : ' (Updated)'), $stats['updated']],
                ['Skipped (no template on test type)', $stats['skipped']],
                ['Errors', $stats['errors']],
            ]
        );

        if ($dryRun && $stats['updated'] > 0) {
            $this->newLine();
            $this->warn('This was a DRY RUN. Run without --dry-run to apply changes.');
        }

        if (!$dryRun && $stats['updated'] > 0) {
            $this->newLine();
            $this->info("✓ Successfully synced {$stats['updated']} lab requests with templates");
        }

        return 0;
    }
}
