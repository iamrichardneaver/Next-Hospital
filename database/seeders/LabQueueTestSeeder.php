<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;
use App\Models\LabRequest;
use App\Models\Queue;
use App\Models\Branch;
use App\Models\User;
use App\Services\LabQueueService;
use Carbon\Carbon;

class LabQueueTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first branch
        $branch = Branch::first();
        if (!$branch) {
            $this->command->error('No branches found. Please run BranchSeeder first.');
            return;
        }

        // Get a lab technician
        $labTech = User::whereHas('roles', function($q) {
            $q->where('name', 'lab_technician');
        })->first();

        if (!$labTech) {
            $this->command->error('No lab technician found. Please run RolePermissionSeeder first.');
            return;
        }

        // Get some patients
        $patients = Patient::where('branch_id', $branch->id)->take(5)->get();
        
        if ($patients->isEmpty()) {
            $this->command->error('No patients found. Please run PatientSeeder first.');
            return;
        }

        $labQueueService = new LabQueueService();

        foreach ($patients as $index => $patient) {
            // Create lab requests
            $labRequest = LabRequest::create([
                'lab_request_number' => 'LR' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
                'patient_id' => $patient->id,
                'consultation_id' => 1, // Assuming consultation exists
                'doctor_id' => 1, // Assuming doctor exists
                'branch_id' => $branch->id,
                'test_type' => $this->getRandomTestType(),
                'test_description' => $this->getRandomTestDescription(),
                'specimen_type' => $this->getRandomSpecimenType(),
                'priority' => $this->getRandomPriority(),
                'status' => 'pending',
                'created_by' => $labTech->id,
            ]);

            // Create queue for this lab request
            $queue = $labQueueService->createQueueForLabRequest($labRequest);
            
            if ($queue) {
                $this->command->info("Created lab queue for patient: {$patient->first_name} {$patient->last_name}");
            }
        }

        $this->command->info('Lab queue test data created successfully!');
    }

    private function getRandomTestType(): string
    {
        $testTypes = [
            'Complete Blood Count (CBC)',
            'Blood Glucose',
            'Lipid Profile',
            'Liver Function Test (LFT)',
            'Kidney Function Test (KFT)',
            'Thyroid Function Test (TFT)',
            'Urinalysis',
            'Stool Analysis',
            'Blood Group & Rh',
            'Malaria Test',
        ];
        
        return $testTypes[array_rand($testTypes)];
    }

    private function getRandomTestDescription(): string
    {
        $descriptions = [
            'Routine blood test to check overall health',
            'Test to monitor diabetes control',
            'Comprehensive lipid panel for heart health',
            'Assessment of liver function and enzymes',
            'Evaluation of kidney function and electrolytes',
            'Thyroid hormone levels assessment',
            'Analysis of urine composition and properties',
            'Examination of stool for parasites and bacteria',
            'Determination of blood type and Rh factor',
            'Rapid diagnostic test for malaria',
        ];
        
        return $descriptions[array_rand($descriptions)];
    }

    private function getRandomSpecimenType(): string
    {
        $specimens = [
            'Blood',
            'Urine',
            'Stool',
            'Sputum',
            'Serum',
            'Plasma',
        ];
        
        return $specimens[array_rand($specimens)];
    }

    private function getRandomPriority(): string
    {
        $priorities = ['routine', 'urgent', 'stat'];
        $weights = [70, 25, 5]; // 70% routine, 25% urgent, 5% stat
        
        $random = mt_rand(1, 100);
        $cumulative = 0;
        
        for ($i = 0; $i < count($priorities); $i++) {
            $cumulative += $weights[$i];
            if ($random <= $cumulative) {
                return $priorities[$i];
            }
        }
        
        return 'routine';
    }
}
