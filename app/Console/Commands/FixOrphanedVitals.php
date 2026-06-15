<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vital;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\Consultation;
use App\Models\User;
use App\Services\ConsultationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixOrphanedVitals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:orphaned-vitals {--patient-id= : Fix vitals for specific patient ID} {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix orphaned vitals (vitals without consultation_id) by creating visits and consultations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $patientId = $this->option('patient-id');
        
        $this->info('Searching for orphaned vitals (vitals without consultation_id)...');
        
        // Query orphaned vitals
        $query = Vital::whereNull('consultation_id')
            ->whereNotNull('recorded_at')
            ->orderBy('recorded_at', 'desc');
        
        if ($patientId) {
            // For specific patient, we need to find vitals that might be for them
            // Since vitals don't have patient_id, we'll need to check by recorded_by and timing
            $this->warn('Note: Patient-specific fix is limited since vitals table has no patient_id column.');
            $this->warn('Will attempt to link based on recorded_by user and timing.');
        }
        
        $orphanedVitals = $query->get();
        
        if ($orphanedVitals->isEmpty()) {
            $this->info('No orphaned vitals found.');
            return 0;
        }
        
        $this->info("Found {$orphanedVitals->count()} orphaned vital(s).");
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        $fixed = 0;
        $failed = 0;
        
        foreach ($orphanedVitals as $vital) {
            try {
                // Try to find patient through the user who recorded the vital
                $recorder = User::find($vital->recorded_by);
                if (!$recorder) {
                    $this->error("Vital {$vital->id}: Cannot find user who recorded it (user_id: {$vital->recorded_by})");
                    $failed++;
                    continue;
                }
                
                // Get branch from recorder
                $branchId = $recorder->staffProfile->branch_id ?? $recorder->branches()->first()->id ?? 1;
                
                // Try to find patient by looking for recent patients created around the time vitals were recorded
                // This is a heuristic - we'll look for patients created within 1 hour of vital recording
                $vitalRecordedAt = $vital->recorded_at;
                $patient = null;
                
                if ($patientId) {
                    $patient = Patient::find($patientId);
                } else {
                    // Find patients created around the time of vital recording
                    $patient = Patient::whereBetween('created_at', [
                        $vitalRecordedAt->copy()->subHours(1),
                        $vitalRecordedAt->copy()->addHours(1)
                    ])
                    ->where('branch_id', $branchId)
                    ->orderBy('created_at', 'desc')
                    ->first();
                }
                
                if (!$patient) {
                    $this->warn("Vital {$vital->id}: Cannot determine patient. Recorded at: {$vitalRecordedAt->format('Y-m-d H:i:s')} by user {$recorder->email}");
                    $failed++;
                    continue;
                }
                
                $this->info("Vital {$vital->id}: Found patient {$patient->id} ({$patient->full_name})");
                
                if ($dryRun) {
                    $this->line("  Would create visit and consultation for patient {$patient->id}");
                    $fixed++;
                    continue;
                }
                
                // Check if patient already has an active visit
                $activeVisit = Visit::where('patient_id', $patient->id)
                    ->where('status', 'active')
                    ->whereIn('visit_type', ['OPD', 'IPD', 'Emergency'])
                    ->latest()
                    ->first();
                
                // If no active visit, create one
                if (!$activeVisit) {
                    // Find available doctor
                    $availableDoctor = User::role('doctor')
                        ->whereHas('staffProfile', function($q) use ($branchId) {
                            $q->where('branch_id', $branchId);
                        })
                        ->where('is_active', true)
                        ->first();
                    
                    $activeVisit = Visit::create([
                        'patient_id' => $patient->id,
                        'branch_id' => $branchId,
                        'visit_type' => 'OPD',
                        'status' => 'active',
                        'assigned_doctor_id' => $availableDoctor ? $availableDoctor->id : null,
                        'check_in_time' => $vitalRecordedAt,
                        'created_by' => $vital->recorded_by,
                    ]);
                    
                    $this->line("  Created visit {$activeVisit->id} for patient {$patient->id}");
                }
                
                // Create consultation if visit has doctor
                if ($activeVisit->assigned_doctor_id) {
                    $consultationService = app(ConsultationService::class);
                    $consultation = $consultationService->getOrCreateConsultationForVitals($activeVisit, []);
                    
                    if ($consultation) {
                        // Link vital to consultation
                        $vital->update(['consultation_id' => $consultation->id]);
                        $this->info("  ✓ Linked vital {$vital->id} to consultation {$consultation->id}");
                        $fixed++;
                    } else {
                        $this->error("  ✗ Failed to create consultation for visit {$activeVisit->id}");
                        $failed++;
                    }
                } else {
                    $this->warn("  Visit {$activeVisit->id} has no assigned doctor - cannot create consultation");
                    $failed++;
                }
                
            } catch (\Exception $e) {
                $this->error("Vital {$vital->id}: Error - " . $e->getMessage());
                Log::error('Failed to fix orphaned vital', [
                    'vital_id' => $vital->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $failed++;
            }
        }
        
        $this->newLine();
        $this->info("Summary:");
        $this->info("  Fixed: {$fixed}");
        $this->info("  Failed: {$failed}");
        
        return 0;
    }
}
