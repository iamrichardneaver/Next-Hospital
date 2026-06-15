<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Visit;
use App\Models\Queue;
use App\Models\Consultation;
use App\Models\EmergencyVisit;
use Illuminate\Support\Facades\DB;

class CleanupOrphanedVisits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visits:cleanup-orphaned {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned visits that have no corresponding patient records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('Scanning for orphaned visits...');
        
        // Find orphaned visits
        $orphanedVisits = Visit::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('patients')
                  ->whereRaw('patients.id = visits.patient_id');
        })->get();

        if ($orphanedVisits->isEmpty()) {
            $this->info('No orphaned visits found.');
            return 0;
        }

        $this->warn("Found {$orphanedVisits->count()} orphaned visits:");
        
        foreach ($orphanedVisits as $visit) {
            $this->line("  - Visit ID: {$visit->id}, Patient ID: {$visit->patient_id}, Token: {$visit->visit_token}, Date: {$visit->check_in_time}");
        }

        if ($isDryRun) {
            $this->info('Dry run completed. No data was deleted.');
            return 0;
        }

        if (!$this->confirm('Do you want to delete these orphaned visits and their related data?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Cleaning up orphaned visits...');
        
        DB::transaction(function () use ($orphanedVisits) {
            foreach ($orphanedVisits as $visit) {
                // Delete related data first
                Queue::where('visit_id', $visit->id)->delete();
                Consultation::where('visit_id', $visit->id)->delete();
                EmergencyVisit::where('visit_id', $visit->id)->delete();
                
                // Delete the visit
                $visit->delete();
                
                $this->line("Deleted visit ID: {$visit->id}");
            }
        });

        $this->info('Cleanup completed successfully!');
        return 0;
    }
}
