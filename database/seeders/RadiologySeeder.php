<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RadiologyDepartment;
use App\Models\ImagingModality;
use App\Models\RadiologyEquipment;
use App\Models\RadiologyProtocol;
use App\Models\ContrastAgent;

class RadiologySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Radiology Departments
        $departments = [
            [
                'name' => 'Main Radiology Department',
                'code' => 'RAD-MAIN',
                'description' => 'Primary radiology department for general imaging services',
                'location' => 'Ground Floor, Building A',
                'contact_phone' => '+233-24-123-4567',
                'contact_email' => 'radiology@hospital.com',
                'is_active' => true
            ],
            [
                'name' => 'Emergency Radiology',
                'code' => 'RAD-ER',
                'description' => 'Emergency radiology services for urgent cases',
                'location' => 'Emergency Department, Ground Floor',
                'contact_phone' => '+233-24-123-4568',
                'contact_email' => 'emergency.radiology@hospital.com',
                'is_active' => true
            ],
            [
                'name' => 'Pediatric Radiology',
                'code' => 'RAD-PED',
                'description' => 'Specialized radiology services for pediatric patients',
                'location' => 'Pediatric Wing, 2nd Floor',
                'contact_phone' => '+233-24-123-4569',
                'contact_email' => 'pediatric.radiology@hospital.com',
                'is_active' => true
            ]
        ];

        foreach ($departments as $department) {
            RadiologyDepartment::create($department);
        }

        // Create Imaging Modalities
        $modalities = [
            [
                'name' => 'X-Ray',
                'code' => 'XR',
                'description' => 'Conventional radiography using X-rays',
                'category' => 'Diagnostic',
                'requires_contrast' => false,
                'requires_sedation' => false,
                'preparation_time_minutes' => 5,
                'procedure_time_minutes' => 15,
                'base_cost' => 25.00,
                'is_active' => true
            ],
            [
                'name' => 'Computed Tomography (CT)',
                'code' => 'CT',
                'description' => 'Cross-sectional imaging using X-rays and computer processing',
                'category' => 'Diagnostic',
                'requires_contrast' => true,
                'requires_sedation' => false,
                'preparation_time_minutes' => 30,
                'procedure_time_minutes' => 45,
                'base_cost' => 150.00,
                'is_active' => true
            ],
            [
                'name' => 'Magnetic Resonance Imaging (MRI)',
                'code' => 'MR',
                'description' => 'Imaging using magnetic fields and radio waves',
                'category' => 'Diagnostic',
                'requires_contrast' => true,
                'requires_sedation' => false,
                'preparation_time_minutes' => 45,
                'procedure_time_minutes' => 60,
                'base_cost' => 300.00,
                'is_active' => true
            ],
            [
                'name' => 'Ultrasound',
                'code' => 'US',
                'description' => 'Imaging using high-frequency sound waves',
                'category' => 'Diagnostic',
                'requires_contrast' => false,
                'requires_sedation' => false,
                'preparation_time_minutes' => 10,
                'procedure_time_minutes' => 30,
                'base_cost' => 80.00,
                'is_active' => true
            ],
            [
                'name' => 'Mammography',
                'code' => 'MG',
                'description' => 'X-ray imaging of the breast for screening and diagnosis',
                'category' => 'Diagnostic',
                'requires_contrast' => false,
                'requires_sedation' => false,
                'preparation_time_minutes' => 15,
                'procedure_time_minutes' => 20,
                'base_cost' => 60.00,
                'is_active' => true
            ],
            [
                'name' => 'Nuclear Medicine',
                'code' => 'NM',
                'description' => 'Imaging using radioactive tracers',
                'category' => 'Nuclear Medicine',
                'requires_contrast' => true,
                'requires_sedation' => false,
                'preparation_time_minutes' => 60,
                'procedure_time_minutes' => 90,
                'base_cost' => 200.00,
                'is_active' => true
            ],
            [
                'name' => 'Fluoroscopy',
                'code' => 'FL',
                'description' => 'Real-time X-ray imaging',
                'category' => 'Diagnostic',
                'requires_contrast' => true,
                'requires_sedation' => false,
                'preparation_time_minutes' => 20,
                'procedure_time_minutes' => 30,
                'base_cost' => 100.00,
                'is_active' => true
            ]
        ];

        foreach ($modalities as $modality) {
            ImagingModality::create($modality);
        }

        // Create Radiology Equipment
        $equipment = [
            [
                'name' => 'Digital X-Ray System',
                'model' => 'DRX-1',
                'manufacturer' => 'Siemens',
                'serial_number' => 'SIE-DRX-001',
                'modality_id' => 1, // X-Ray
                'department_id' => 1, // Main Radiology
                'installation_date' => '2023-01-15',
                'last_maintenance_date' => '2024-01-01',
                'next_maintenance_date' => '2024-07-01',
                'status' => 'operational',
                'specifications' => [
                    'detector_type' => 'Flat Panel',
                    'resolution' => '2048x2048',
                    'power_rating' => '50kW'
                ],
                'is_active' => true
            ],
            [
                'name' => 'CT Scanner',
                'model' => 'SOMATOM Force',
                'manufacturer' => 'Siemens',
                'serial_number' => 'SIE-CT-001',
                'modality_id' => 2, // CT
                'department_id' => 1, // Main Radiology
                'installation_date' => '2023-03-20',
                'last_maintenance_date' => '2024-01-15',
                'next_maintenance_date' => '2024-07-15',
                'status' => 'operational',
                'specifications' => [
                    'slices' => 128,
                    'gantry_aperture' => '78cm',
                    'table_weight_limit' => '250kg'
                ],
                'is_active' => true
            ],
            [
                'name' => 'MRI Scanner',
                'model' => 'MAGNETOM Aera',
                'manufacturer' => 'Siemens',
                'serial_number' => 'SIE-MR-001',
                'modality_id' => 3, // MRI
                'department_id' => 1, // Main Radiology
                'installation_date' => '2023-06-10',
                'last_maintenance_date' => '2024-02-01',
                'next_maintenance_date' => '2024-08-01',
                'status' => 'operational',
                'specifications' => [
                    'field_strength' => '1.5T',
                    'bore_diameter' => '70cm',
                    'gradient_strength' => '45mT/m'
                ],
                'is_active' => true
            ],
            [
                'name' => 'Ultrasound System',
                'model' => 'ACUSON Sequoia',
                'manufacturer' => 'Siemens',
                'serial_number' => 'SIE-US-001',
                'modality_id' => 4, // Ultrasound
                'department_id' => 1, // Main Radiology
                'installation_date' => '2023-02-28',
                'last_maintenance_date' => '2024-01-10',
                'next_maintenance_date' => '2024-07-10',
                'status' => 'operational',
                'specifications' => [
                    'transducers' => 'Linear, Convex, Micro-convex',
                    'frequency_range' => '1-15 MHz',
                    'imaging_modes' => 'B-mode, Color Doppler, Power Doppler'
                ],
                'is_active' => true
            ],
            [
                'name' => 'Emergency X-Ray',
                'model' => 'Mobile DR',
                'manufacturer' => 'GE Healthcare',
                'serial_number' => 'GE-MOB-001',
                'modality_id' => 1, // X-Ray
                'department_id' => 2, // Emergency Radiology
                'installation_date' => '2023-04-15',
                'last_maintenance_date' => '2024-01-20',
                'next_maintenance_date' => '2024-07-20',
                'status' => 'operational',
                'specifications' => [
                    'type' => 'Mobile',
                    'battery_life' => '8 hours',
                    'weight' => '180kg'
                ],
                'is_active' => true
            ]
        ];

        foreach ($equipment as $eq) {
            RadiologyEquipment::create($eq);
        }

        // Create Radiology Protocols
        $protocols = [
            [
                'name' => 'Chest X-Ray (PA & Lateral)',
                'modality_id' => 1, // X-Ray
                'body_part' => 'Chest',
                'description' => 'Standard chest X-ray examination with PA and lateral views',
                'technical_parameters' => [
                    'kvp' => '120',
                    'mas' => '3.2',
                    'distance' => '180cm',
                    'grid' => 'Yes'
                ],
                'patient_preparation' => 'Remove all metallic objects, jewelry, and clothing from chest area',
                'contraindications' => 'Pregnancy (relative contraindication)',
                'requires_contrast' => false,
                'is_active' => true
            ],
            [
                'name' => 'Head CT without Contrast',
                'modality_id' => 2, // CT
                'body_part' => 'Head',
                'description' => 'Non-contrast CT examination of the head',
                'technical_parameters' => [
                    'slice_thickness' => '5mm',
                    'kvp' => '120',
                    'mas' => '300',
                    'reconstruction_algorithm' => 'Soft tissue'
                ],
                'patient_preparation' => 'Remove all metallic objects and jewelry from head and neck',
                'contraindications' => 'Pregnancy, claustrophobia',
                'requires_contrast' => false,
                'is_active' => true
            ],
            [
                'name' => 'Head CT with Contrast',
                'modality_id' => 2, // CT
                'body_part' => 'Head',
                'description' => 'Contrast-enhanced CT examination of the head',
                'technical_parameters' => [
                    'slice_thickness' => '5mm',
                    'kvp' => '120',
                    'mas' => '300',
                    'contrast_volume' => '100ml',
                    'injection_rate' => '2ml/s'
                ],
                'patient_preparation' => 'NPO 4 hours, remove metallic objects, IV access required',
                'contraindications' => 'Pregnancy, contrast allergy, renal insufficiency',
                'requires_contrast' => true,
                'is_active' => true
            ],
            [
                'name' => 'Knee MRI',
                'modality_id' => 3, // MRI
                'body_part' => 'Knee',
                'description' => 'MRI examination of the knee joint',
                'technical_parameters' => [
                    'field_strength' => '1.5T',
                    'coil' => 'Knee coil',
                    'sequences' => 'T1, T2, PD, STIR',
                    'slice_thickness' => '3mm'
                ],
                'patient_preparation' => 'Remove all metallic objects, complete MRI safety screening',
                'contraindications' => 'Pacemaker, metallic implants, claustrophobia',
                'requires_contrast' => false,
                'is_active' => true
            ],
            [
                'name' => 'Abdominal Ultrasound',
                'modality_id' => 4, // Ultrasound
                'body_part' => 'Abdomen',
                'description' => 'Ultrasound examination of the abdomen',
                'technical_parameters' => [
                    'transducer' => 'Convex 3-5MHz',
                    'depth' => '15-20cm',
                    'gain' => 'Auto',
                    'focus' => 'Mid-abdomen'
                ],
                'patient_preparation' => 'NPO 6-8 hours for upper abdomen, full bladder for pelvis',
                'contraindications' => 'None',
                'requires_contrast' => false,
                'is_active' => true
            ]
        ];

        foreach ($protocols as $protocol) {
            RadiologyProtocol::create($protocol);
        }

        // Create Contrast Agents
        $contrastAgents = [
            [
                'name' => 'Iopamidol',
                'generic_name' => 'Iopamidol',
                'manufacturer' => 'Bracco',
                'indications' => 'CT angiography, urography, myelography',
                'contraindications' => 'Severe renal insufficiency, contrast allergy, pregnancy',
                'side_effects' => 'Nausea, vomiting, allergic reactions, nephrotoxicity',
                'dose_ml' => 100.0,
                'route_of_administration' => 'Intravenous',
                'requires_consent' => true,
                'is_active' => true
            ],
            [
                'name' => 'Gadolinium',
                'generic_name' => 'Gadolinium-based contrast agent',
                'manufacturer' => 'Bayer',
                'indications' => 'MRI contrast enhancement',
                'contraindications' => 'Severe renal insufficiency, pregnancy, NSF risk',
                'side_effects' => 'Allergic reactions, nephrogenic systemic fibrosis',
                'dose_ml' => 20.0,
                'route_of_administration' => 'Intravenous',
                'requires_consent' => true,
                'is_active' => true
            ],
            [
                'name' => 'Barium Sulfate',
                'generic_name' => 'Barium Sulfate',
                'manufacturer' => 'Various',
                'indications' => 'Gastrointestinal imaging',
                'contraindications' => 'Perforation, obstruction, aspiration risk',
                'side_effects' => 'Constipation, aspiration pneumonia',
                'dose_ml' => 500.0,
                'route_of_administration' => 'Oral/Rectal',
                'requires_consent' => true,
                'is_active' => true
            ]
        ];

        foreach ($contrastAgents as $agent) {
            ContrastAgent::create($agent);
        }
    }
}
