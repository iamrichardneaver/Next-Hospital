<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SamplePrescriptionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get required IDs
        $patients = DB::table('patients')->pluck('id')->toArray();
        $consultations = DB::table('consultations')->pluck('id')->toArray();
        $doctors = DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'Doctor')
            ->pluck('users.id')
            ->toArray();
        $branches = DB::table('branches')->pluck('id')->toArray();
        $drugs = DB::table('drugs')->pluck('id')->toArray();

        if (empty($patients) || empty($consultations) || empty($doctors) || empty($branches) || empty($drugs)) {
            $this->command->warn('Required data missing. Please ensure patients, consultations, doctors, branches, and drugs exist.');
            return;
        }

        $prescriptionStatuses = ['active', 'completed', 'cancelled'];
        $drugOrderStatuses = ['pending', 'processing', 'ready', 'dispensed', 'cancelled'];
        $frequencies = ['Once daily', 'Twice daily', 'Three times daily', 'Four times daily', 'As needed'];
        $durations = ['3 days', '5 days', '7 days', '14 days', '30 days'];

        $prescriptions = [];
        $drugOrders = [];
        $prescriptionId = 1;
        $drugOrderId = 1;

        // Generate prescriptions for the last 30 days
        for ($i = 0; $i < 50; $i++) {
            $prescriptionDate = Carbon::now()->subDays(rand(0, 30));
            $status = $prescriptionStatuses[array_rand($prescriptionStatuses)];
            
            $prescription = [
                'id' => $prescriptionId,
                'patient_id' => $patients[array_rand($patients)],
                'consultation_id' => $consultations[array_rand($consultations)],
                'doctor_id' => $doctors[array_rand($doctors)],
                'branch_id' => $branches[array_rand($branches)],
                'prescription_number' => 'RX-' . str_pad($prescriptionId, 6, '0', STR_PAD_LEFT),
                'prescription_date' => $prescriptionDate->format('Y-m-d'),
                'status' => $status,
                'notes' => 'Sample prescription for testing',
                'created_by' => $doctors[array_rand($doctors)],
                'billing_status' => 'pending',
                'created_at' => $prescriptionDate,
                'updated_at' => $prescriptionDate,
            ];

            $prescriptions[] = $prescription;

            // Create 1-5 drug orders per prescription
            $numOrders = rand(1, 5);
            for ($j = 0; $j < $numOrders; $j++) {
                $drugOrderStatus = $status === 'completed' ? 'dispensed' : $drugOrderStatuses[array_rand($drugOrderStatuses)];
                
                $drugOrder = [
                    'id' => $drugOrderId,
                    'prescription_id' => $prescriptionId,
                    'drug_id' => $drugs[array_rand($drugs)],
                    'quantity' => rand(1, 10),
                    'quantity_dispensed' => $drugOrderStatus === 'dispensed' ? rand(1, 10) : 0,
                    'dosage_instructions' => 'Take ' . rand(1, 3) . ' tablet(s)',
                    'instructions' => 'Take with food',
                    'frequency' => $frequencies[array_rand($frequencies)],
                    'duration' => $durations[array_rand($durations)],
                    'status' => $drugOrderStatus,
                    'dispensed_by' => $drugOrderStatus === 'dispensed' ? $doctors[array_rand($doctors)] : null,
                    'dispensed_at' => $drugOrderStatus === 'dispensed' ? $prescriptionDate->copy()->addHours(rand(1, 24)) : null,
                    'notes' => null,
                    'created_at' => $prescriptionDate,
                    'updated_at' => $prescriptionDate,
                ];

                $drugOrders[] = $drugOrder;
                $drugOrderId++;
            }

            $prescriptionId++;
        }

        // Insert prescriptions
        DB::table('prescriptions')->insert($prescriptions);
        $this->command->info('Inserted ' . count($prescriptions) . ' prescriptions');

        // Insert drug orders
        DB::table('drug_orders')->insert($drugOrders);
        $this->command->info('Inserted ' . count($drugOrders) . ' drug orders');

        $this->command->info('Sample prescriptions seeded successfully!');
    }
}

