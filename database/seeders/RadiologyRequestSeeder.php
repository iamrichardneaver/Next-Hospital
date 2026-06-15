<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RadiologyRequest;
use App\Models\RadiologyStudy;
use App\Models\RadiologyReport;
use App\Models\Patient;
use App\Models\User;
use App\Models\RadiologyDepartment;
use App\Models\ImagingModality;
use App\Models\RadiologyEquipment;
use App\Models\RadiologyTechnician;
use Illuminate\Support\Str;

class RadiologyRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get required data
        $patients = Patient::all();
        $doctors = User::all();
        $departments = RadiologyDepartment::all();
        $modalities = ImagingModality::all();
        $equipment = RadiologyEquipment::all();
        
        if ($patients->isEmpty() || $doctors->isEmpty() || $departments->isEmpty() || $modalities->isEmpty()) {
            $this->command->error('Required data not found. Please run other seeders first.');
            return;
        }

        // Create radiology requests
        $requests = [
            [
                'request_number' => 'RAD-' . date('Y') . '-' . str_pad(1, 4, '0', STR_PAD_LEFT),
                'patient_id' => $patients[0 % $patients->count()]->id,
                'doctor_id' => $doctors[0 % $doctors->count()]->id,
                'modality_id' => $modalities[0 % $modalities->count()]->id,
                'department_id' => $departments[0 % $departments->count()]->id,
                'clinical_history' => 'Patient presents with chest pain and shortness of breath. History of hypertension and diabetes.',
                'clinical_question' => 'Rule out pneumonia or cardiac complications',
                'indication' => 'Chest pain, dyspnea',
                'priority' => 'urgent',
                'status' => 'requested',
                'requested_date' => now()->subDays(2),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2)
            ],
            [
                'request_number' => 'RAD-' . date('Y') . '-' . str_pad(2, 4, '0', STR_PAD_LEFT),
                'patient_id' => $patients[1 % $patients->count()]->id,
                'doctor_id' => $doctors[1 % $doctors->count()]->id,
                'modality_id' => $modalities[1 % $modalities->count()]->id,
                'department_id' => $departments[1 % $departments->count()]->id,
                'clinical_history' => 'Motor vehicle accident with head trauma. Patient is conscious but confused.',
                'clinical_question' => 'Assess for intracranial bleeding or skull fractures',
                'indication' => 'Head trauma, altered mental status',
                'priority' => 'stat',
                'status' => 'scheduled',
                'requested_date' => now()->subDays(1),
                'scheduled_date' => now()->addHours(2),
                'scheduled_time' => '14:00:00',
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1)
            ],
            [
                'request_number' => 'RAD-' . date('Y') . '-' . str_pad(3, 4, '0', STR_PAD_LEFT),
                'patient_id' => $patients[2 % $patients->count()]->id,
                'doctor_id' => $doctors[2 % $doctors->count()]->id,
                'modality_id' => $modalities[2 % $modalities->count()]->id,
                'department_id' => $departments[2 % $departments->count()]->id,
                'clinical_history' => 'Routine follow-up for known liver lesion. Previous CT showed 2cm hepatic mass.',
                'clinical_question' => 'Monitor lesion size and characteristics',
                'indication' => 'Follow-up imaging',
                'priority' => 'routine',
                'status' => 'in_progress',
                'requested_date' => now()->subHours(4),
                'scheduled_date' => now(),
                'scheduled_time' => '10:00:00',
                'created_at' => now()->subHours(4),
                'updated_at' => now()->subHours(1)
            ],
            [
                'request_number' => 'RAD-' . date('Y') . '-' . str_pad(4, 4, '0', STR_PAD_LEFT),
                'patient_id' => $patients[3 % $patients->count()]->id,
                'doctor_id' => $doctors[0 % $doctors->count()]->id,
                'modality_id' => $modalities[3 % $modalities->count()]->id,
                'department_id' => $departments[0 % $departments->count()]->id,
                'clinical_history' => 'Pregnant patient at 32 weeks gestation with abdominal pain.',
                'clinical_question' => 'Assess fetal well-being and rule out placental complications',
                'indication' => 'Abdominal pain in pregnancy',
                'priority' => 'urgent',
                'status' => 'completed',
                'requested_date' => now()->subDays(3),
                'scheduled_date' => now()->subDays(2),
                'scheduled_time' => '09:00:00',
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(1)
            ],
            [
                'request_number' => 'RAD-' . date('Y') . '-' . str_pad(5, 4, '0', STR_PAD_LEFT),
                'patient_id' => $patients[0 % $patients->count()]->id,
                'doctor_id' => $doctors[1 % $doctors->count()]->id,
                'modality_id' => $modalities[4 % $modalities->count()]->id,
                'department_id' => $departments[1 % $departments->count()]->id,
                'clinical_history' => 'Chronic knee pain with suspected meniscal tear. Patient reports clicking and locking sensation.',
                'clinical_question' => 'Evaluate meniscal integrity and joint space',
                'indication' => 'Knee pain, suspected meniscal injury',
                'priority' => 'routine',
                'status' => 'requested',
                'requested_date' => now()->subHours(2),
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2)
            ],
            [
                'request_number' => 'RAD-' . date('Y') . '-' . str_pad(6, 4, '0', STR_PAD_LEFT),
                'patient_id' => $patients[1 % $patients->count()]->id,
                'doctor_id' => $doctors[2 % $doctors->count()]->id,
                'modality_id' => $modalities[5 % $modalities->count()]->id,
                'department_id' => $departments[2 % $departments->count()]->id,
                'clinical_history' => 'Suspected stroke with left-sided weakness and speech difficulties.',
                'clinical_question' => 'Rule out acute ischemic stroke or hemorrhage',
                'indication' => 'Acute stroke symptoms',
                'priority' => 'emergency',
                'status' => 'scheduled',
                'requested_date' => now()->subMinutes(30),
                'scheduled_date' => now()->addMinutes(30),
                'scheduled_time' => '15:30:00',
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30)
            ],
            [
                'request_number' => 'RAD-' . date('Y') . '-' . str_pad(7, 4, '0', STR_PAD_LEFT),
                'patient_id' => $patients[2 % $patients->count()]->id,
                'doctor_id' => $doctors[0 % $doctors->count()]->id,
                'modality_id' => $modalities[6 % $modalities->count()]->id,
                'department_id' => $departments[0 % $departments->count()]->id,
                'clinical_history' => 'Routine mammography screening for 45-year-old female with family history of breast cancer.',
                'clinical_question' => 'Screening mammography for breast cancer detection',
                'indication' => 'Routine screening',
                'priority' => 'routine',
                'status' => 'completed',
                'requested_date' => now()->subDays(5),
                'scheduled_date' => now()->subDays(4),
                'scheduled_time' => '11:00:00',
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(3)
            ],
            [
                'request_number' => 'RAD-' . date('Y') . '-' . str_pad(8, 4, '0', STR_PAD_LEFT),
                'patient_id' => $patients[3 % $patients->count()]->id,
                'doctor_id' => $doctors[1 % $doctors->count()]->id,
                'modality_id' => $modalities[0 % $modalities->count()]->id,
                'department_id' => $departments[1 % $departments->count()]->id,
                'clinical_history' => 'Post-operative follow-up for hip replacement surgery. Patient reports good recovery.',
                'clinical_question' => 'Assess implant position and healing progress',
                'indication' => 'Post-operative follow-up',
                'priority' => 'routine',
                'status' => 'cancelled',
                'requested_date' => now()->subDays(7),
                'scheduled_date' => now()->subDays(6),
                'scheduled_time' => '14:30:00',
                'rejection_reason' => 'Patient requested cancellation',
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(6)
            ]
        ];

        foreach ($requests as $requestData) {
            $request = RadiologyRequest::create($requestData);

            // Create studies for completed and in-progress requests
            if (in_array($request->status, ['in_progress', 'completed'])) {
                $study = RadiologyStudy::create([
                    'study_uid' => '1.2.3.4.5.' . $request->id,
                    'request_id' => $request->id,
                    'patient_id' => $request->patient_id,
                    'modality_id' => $request->modality_id,
                    'equipment_id' => $equipment->isNotEmpty() ? $equipment->random()->id : null,
                    'study_description' => $request->clinical_question,
                    'study_notes' => 'Study performed as requested',
                    'status' => $request->status === 'completed' ? 'completed' : 'in_progress',
                    'study_date' => $request->scheduled_date,
                    'completed_date' => $request->status === 'completed' ? $request->scheduled_date : null,
                    'created_at' => $request->created_at,
                    'updated_at' => $request->updated_at
                ]);

                // Create reports for completed studies
                if ($request->status === 'completed') {
                    RadiologyReport::create([
                        'study_id' => $study->id,
                        'radiologist_id' => $doctors->random()->id,
                        'findings' => 'No acute abnormalities detected. Normal study.',
                        'impression' => 'Normal study. No acute findings.',
                        'recommendations' => 'Routine follow-up as clinically indicated',
                        'status' => 'preliminary',
                        'created_at' => $request->updated_at,
                        'updated_at' => $request->updated_at
                    ]);
                }
            }
        }

        $this->command->info('Radiology requests seeded successfully!');
    }
}