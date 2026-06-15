<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Drug;
use App\Models\DrugInteraction;

class DrugInteractionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some common drugs for interaction examples
        $warfarin = Drug::where('name', 'LIKE', '%warfarin%')->first();
        $aspirin = Drug::where('name', 'LIKE', '%aspirin%')->first();
        $digoxin = Drug::where('name', 'LIKE', '%digoxin%')->first();
        $furosemide = Drug::where('name', 'LIKE', '%furosemide%')->first();
        $lithium = Drug::where('name', 'LIKE', '%lithium%')->first();
        $enalapril = Drug::where('name', 'LIKE', '%enalapril%')->first();
        $potassium = Drug::where('name', 'LIKE', '%potassium%')->first();
        $metformin = Drug::where('name', 'LIKE', '%metformin%')->first();
        $insulin = Drug::where('name', 'LIKE', '%insulin%')->first();
        $simvastatin = Drug::where('name', 'LIKE', '%simvastatin%')->first();
        $gemfibrozil = Drug::where('name', 'LIKE', '%gemfibrozil%')->first();

        $interactions = [];

        // Warfarin + Aspirin interaction
        if ($warfarin && $aspirin) {
            $interactions[] = [
                'drug1_id' => $warfarin->id,
                'drug2_id' => $aspirin->id,
                'severity' => 'major',
                'description' => 'Increased bleeding risk',
                'clinical_effect' => 'Enhanced anticoagulant effect leading to increased bleeding risk',
                'management' => 'Monitor INR closely, consider dose adjustment, educate patient about bleeding signs',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Digoxin + Furosemide interaction
        if ($digoxin && $furosemide) {
            $interactions[] = [
                'drug1_id' => $digoxin->id,
                'drug2_id' => $furosemide->id,
                'severity' => 'moderate',
                'description' => 'Increased digoxin toxicity',
                'clinical_effect' => 'Hypokalemia from furosemide increases digoxin sensitivity',
                'management' => 'Monitor potassium levels, consider potassium supplementation, monitor digoxin levels',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Lithium + Furosemide interaction
        if ($lithium && $furosemide) {
            $interactions[] = [
                'drug1_id' => $lithium->id,
                'drug2_id' => $furosemide->id,
                'severity' => 'major',
                'description' => 'Increased lithium toxicity',
                'clinical_effect' => 'Reduced lithium excretion leading to increased serum lithium levels',
                'management' => 'Monitor lithium levels closely, consider dose reduction, educate patient about toxicity signs',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // ACE Inhibitor + Potassium interaction
        if ($enalapril && $potassium) {
            $interactions[] = [
                'drug1_id' => $enalapril->id,
                'drug2_id' => $potassium->id,
                'severity' => 'moderate',
                'description' => 'Hyperkalemia risk',
                'clinical_effect' => 'ACE inhibitors reduce potassium excretion, increasing hyperkalemia risk',
                'management' => 'Monitor potassium levels regularly, avoid potassium-rich foods, consider dose adjustment',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Metformin + Insulin interaction
        if ($metformin && $insulin) {
            $interactions[] = [
                'drug1_id' => $metformin->id,
                'drug2_id' => $insulin->id,
                'severity' => 'minor',
                'description' => 'Enhanced hypoglycemic effect',
                'clinical_effect' => 'Combined hypoglycemic effect may increase risk of hypoglycemia',
                'management' => 'Monitor blood glucose closely, educate patient about hypoglycemia symptoms',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Simvastatin + Gemfibrozil interaction
        if ($simvastatin && $gemfibrozil) {
            $interactions[] = [
                'drug1_id' => $simvastatin->id,
                'drug2_id' => $gemfibrozil->id,
                'severity' => 'major',
                'description' => 'Increased myopathy risk',
                'clinical_effect' => 'Gemfibrozil increases simvastatin levels, increasing myopathy risk',
                'management' => 'Avoid combination if possible, if necessary use lowest effective doses, monitor for muscle symptoms',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Insert interactions if drugs exist
        if (!empty($interactions)) {
            DrugInteraction::insert($interactions);
            $this->command->info('Drug interactions seeded successfully: ' . count($interactions) . ' interactions created.');
        } else {
            $this->command->warn('No drug interactions seeded. Please ensure drugs exist in the database first.');
        }
    }
}