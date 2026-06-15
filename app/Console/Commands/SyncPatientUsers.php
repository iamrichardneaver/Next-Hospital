<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Patient;
use App\Models\Branch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncPatientUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patients:sync-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create patient records for users with patient role who do not have patient records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Syncing users with patient role to patient records...');
        
        // Get default branch
        $defaultBranch = Branch::where('is_active', true)->first();
        if (!$defaultBranch) {
            $this->error('❌ No active branch found. Please create a branch first.');
            return 1;
        }
        
        // Get users with patient role who don't have patient records
        $users = User::whereHas('roles', function ($query) {
                $query->where('name', 'patient');
            })
            ->whereDoesntHave('patient')
            ->get();
        
        if ($users->isEmpty()) {
            $this->info('✅ All users with patient role already have patient records.');
            return 0;
        }
        
        $this->info("Found {$users->count()} user(s) with patient role missing patient records.");
        
        $created = 0;
        $failed = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($users as $user) {
                // Check if patient record already exists (double-check)
                $existingPatient = Patient::where('user_id', $user->id)->first();
                if ($existingPatient) {
                    $this->warn("⚠️  User {$user->email} already has a patient record. Skipping...");
                    continue;
                }
                
                // Extract first and last name from user's name
                $nameParts = explode(' ', $user->name, 2);
                $firstName = $user->first_name ?? $nameParts[0] ?? 'Unknown';
                $lastName = $user->last_name ?? ($nameParts[1] ?? 'User');
                
                // Create patient record
                $patient = Patient::create([
                    'user_id' => $user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'other_names' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? null,
                    'gender' => 'Male', // Default, can be updated later
                    'date_of_birth' => now()->subYears(25), // Default age 25
                    'address' => null,
                    'branch_id' => $defaultBranch->id,
                    'created_by' => 1, // System
                    'account_status' => 'active', // Assume active if they have user account
                ]);

                try {
                    app(\App\Services\RegistrationFeeService::class)->createInvoiceForPatient($patient, $patient->branch_id);
                } catch (\Throwable $e) {
                    $this->warn("Registration fee invoice skip: " . $e->getMessage());
                }

                $this->info("✅ Created patient record for {$user->email} (Patient #: {$patient->patient_number})");
                $created++;
            }
            
            DB::commit();
            
            $this->info("\n✅ Successfully created {$created} patient record(s).");
            if ($failed > 0) {
                $this->warn("⚠️  {$failed} record(s) failed to create.");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error syncing patient records: " . $e->getMessage());
            return 1;
        }
    }
}

