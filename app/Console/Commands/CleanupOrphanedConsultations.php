<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;

class CleanupOrphanedConsultations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'consultations:cleanup-orphaned';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up consultation records that reference non-existent patients or doctors';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of orphaned consultations...');

        // Find consultations with missing patients
        $orphanedByPatient = Consultation::whereNotExists(function ($query) {
            $query->select(\DB::raw(1))
                  ->from('patients')
                  ->whereRaw('patients.id = consultations.patient_id');
        })->get();

        // Find consultations with missing doctors
        $orphanedByDoctor = Consultation::whereNotExists(function ($query) {
            $query->select(\DB::raw(1))
                  ->from('users')
                  ->whereRaw('users.id = consultations.doctor_id');
        })->get();

        $totalOrphaned = $orphanedByPatient->count() + $orphanedByDoctor->count();

        if ($totalOrphaned === 0) {
            $this->info('No orphaned consultations found.');
            return 0;
        }

        $this->warn("Found {$totalOrphaned} orphaned consultations:");
        $this->warn("- {$orphanedByPatient->count()} with missing patients");
        $this->warn("- {$orphanedByDoctor->count()} with missing doctors");

        if ($this->confirm('Do you want to delete these orphaned consultations?')) {
            $deletedCount = 0;

            // Delete consultations with missing patients
            foreach ($orphanedByPatient as $consultation) {
                $this->line("Deleting consultation ID {$consultation->id} (missing patient ID {$consultation->patient_id})");
                $consultation->delete();
                $deletedCount++;
            }

            // Delete consultations with missing doctors
            foreach ($orphanedByDoctor as $consultation) {
                $this->line("Deleting consultation ID {$consultation->id} (missing doctor ID {$consultation->doctor_id})");
                $consultation->delete();
                $deletedCount++;
            }

            $this->info("Successfully deleted {$deletedCount} orphaned consultations.");
        } else {
            $this->info('Cleanup cancelled.');
        }

        return 0;
    }
}