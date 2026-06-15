<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EyeService;
use App\Models\EyeTestTemplate;
use App\Models\EyeTestParameter;
use App\Models\User;
use App\Models\Branch;

class EyeServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user and branch for created_by
        $user = User::first();
        $branch = Branch::first();

        if (!$user || !$branch) {
            $this->command->error('No users or branches found. Please run other seeders first.');
            return;
        }

        // Create Eye Services
        $services = [
            [
                'service_code' => 'EYE_VIS_001',
                'service_name' => 'Comprehensive Eye Examination',
                'description' => 'Complete eye examination including visual acuity, refraction, and fundus examination',
                'category' => 'Vision Test',
                'subcategory' => 'Comprehensive',
                'service_type' => 'examination',
                'instructions' => 'Patient should remove contact lenses 2 hours before examination. Bring current glasses.',
                'duration_minutes' => 45,
                'requires_doctor' => true,
                'requires_equipment' => true,
                'equipment_required' => ['Snellen Chart', 'Phoropter', 'Retinoscope', 'Ophthalmoscope'],
                'preparation_instructions' => [
                    'Remove contact lenses 2 hours before',
                    'Bring current prescription glasses',
                    'No eye makeup on examination day'
                ],
                'post_service_instructions' => [
                    'Use prescribed eye drops as directed',
                    'Avoid rubbing eyes',
                    'Follow up as scheduled'
                ],
                'base_price' => 150.00,
                'nhis_price' => 120.00,
                'nhis_covered' => true,
                'currency' => 'GHS',
                'ghs_code' => 'EYE001',
                'ghs_mandatory' => false,
                'is_active' => true,
                'requires_approval' => false,
                'created_by' => $user->id,
            ],
            [
                'service_code' => 'EYE_REF_001',
                'service_name' => 'Refraction Test',
                'description' => 'Measurement of refractive error and prescription determination',
                'category' => 'Vision Test',
                'subcategory' => 'Refraction',
                'service_type' => 'test',
                'instructions' => 'Patient should be relaxed and comfortable during the test.',
                'duration_minutes' => 30,
                'requires_doctor' => true,
                'requires_equipment' => true,
                'equipment_required' => ['Phoropter', 'Retinoscope', 'Trial Lenses'],
                'preparation_instructions' => [
                    'Remove contact lenses 1 hour before',
                    'Bring current glasses'
                ],
                'post_service_instructions' => [
                    'New prescription will be provided',
                    'Schedule follow-up if needed'
                ],
                'base_price' => 80.00,
                'nhis_price' => 60.00,
                'nhis_covered' => true,
                'currency' => 'GHS',
                'ghs_code' => 'EYE002',
                'ghs_mandatory' => false,
                'is_active' => true,
                'requires_approval' => false,
                'created_by' => $user->id,
            ],
            [
                'service_code' => 'EYE_PRES_001',
                'service_name' => 'Intraocular Pressure Measurement',
                'description' => 'Measurement of eye pressure using tonometry',
                'category' => 'Diagnostic Test',
                'subcategory' => 'Pressure',
                'service_type' => 'test',
                'instructions' => 'Patient should avoid caffeine before the test.',
                'duration_minutes' => 15,
                'requires_doctor' => true,
                'requires_equipment' => true,
                'equipment_required' => ['Tonometer', 'Anesthetic Drops'],
                'preparation_instructions' => [
                    'Avoid caffeine 2 hours before',
                    'Remove contact lenses'
                ],
                'post_service_instructions' => [
                    'Avoid rubbing eyes for 30 minutes',
                    'Use prescribed drops if given'
                ],
                'base_price' => 50.00,
                'nhis_price' => 40.00,
                'nhis_covered' => true,
                'currency' => 'GHS',
                'ghs_code' => 'EYE003',
                'ghs_mandatory' => false,
                'is_active' => true,
                'requires_approval' => false,
                'created_by' => $user->id,
            ],
            [
                'service_code' => 'EYE_FUND_001',
                'service_name' => 'Fundus Examination',
                'description' => 'Examination of the retina and optic nerve using ophthalmoscope',
                'category' => 'Diagnostic Test',
                'subcategory' => 'Fundus',
                'service_type' => 'examination',
                'instructions' => 'Pupils will be dilated for better visualization.',
                'duration_minutes' => 25,
                'requires_doctor' => true,
                'requires_equipment' => true,
                'equipment_required' => ['Ophthalmoscope', 'Dilating Drops', 'Fundus Camera'],
                'preparation_instructions' => [
                    'Pupils will be dilated',
                    'Bring sunglasses for after examination',
                    'Arrange transportation if needed'
                ],
                'post_service_instructions' => [
                    'Wear sunglasses for 4-6 hours',
                    'Avoid driving until vision clears',
                    'Use prescribed eye drops'
                ],
                'base_price' => 100.00,
                'nhis_price' => 80.00,
                'nhis_covered' => true,
                'currency' => 'GHS',
                'ghs_code' => 'EYE004',
                'ghs_mandatory' => false,
                'is_active' => true,
                'requires_approval' => false,
                'created_by' => $user->id,
            ],
            [
                'service_code' => 'EYE_VF_001',
                'service_name' => 'Visual Field Test',
                'description' => 'Peripheral vision testing using automated perimetry',
                'category' => 'Diagnostic Test',
                'subcategory' => 'Visual Field',
                'service_type' => 'test',
                'instructions' => 'Patient must maintain fixation during the test.',
                'duration_minutes' => 20,
                'requires_doctor' => false,
                'requires_equipment' => true,
                'equipment_required' => ['Automated Perimeter', 'Fixation Target'],
                'preparation_instructions' => [
                    'Remove glasses if instructed',
                    'Keep eyes open and focused'
                ],
                'post_service_instructions' => [
                    'Results will be analyzed by doctor',
                    'Follow up as scheduled'
                ],
                'base_price' => 75.00,
                'nhis_price' => 60.00,
                'nhis_covered' => true,
                'currency' => 'GHS',
                'ghs_code' => 'EYE005',
                'ghs_mandatory' => false,
                'is_active' => true,
                'requires_approval' => false,
                'created_by' => $user->id,
            ],
        ];

        foreach ($services as $serviceData) {
            $service = EyeService::create($serviceData);
            $this->createTestTemplates($service, $user->id);
        }

        $this->command->info('Eye services and templates created successfully!');
    }

    /**
     * Create test templates for a service.
     */
    private function createTestTemplates(EyeService $service, int $userId): void
    {
        $templates = [];

        switch ($service->service_code) {
            case 'EYE_VIS_001':
                $templates = [
                    [
                        'template_code' => 'COMP_EYE_001',
                        'template_name' => 'Comprehensive Eye Examination Template',
                        'description' => 'Complete eye examination including all standard tests',
                        'test_type' => 'combined',
                        'test_parameters' => [
                            'visual_acuity_right',
                            'visual_acuity_left',
                            'refraction_right',
                            'refraction_left',
                            'intraocular_pressure_right',
                            'intraocular_pressure_left',
                            'fundus_findings'
                        ],
                        'reference_ranges' => [
                            'visual_acuity' => ['min' => '6/6', 'max' => '6/60'],
                            'intraocular_pressure' => ['min' => 10, 'max' => 21]
                        ],
                        'abnormal_criteria' => [
                            'visual_acuity' => ['operator' => '<', 'threshold' => '6/12'],
                            'intraocular_pressure' => ['operator' => '>', 'threshold' => 21]
                        ],
                        'equipment_config' => [
                            'snellen_chart' => ['distance' => '6m'],
                            'phoropter' => ['auto_refraction' => true]
                        ],
                        'test_sequence' => [
                            '1. Visual Acuity Test',
                            '2. Refraction Test',
                            '3. Intraocular Pressure',
                            '4. Fundus Examination'
                        ],
                        'estimated_duration_minutes' => 45,
                        'requires_dilation' => true,
                        'dilation_requirements' => [
                            'drops' => 'Tropicamide 1%',
                            'wait_time' => 20
                        ],
                        'requires_dark_room' => false,
                        'requires_bright_light' => false,
                        'environmental_requirements' => [
                            'lighting' => 'adjustable',
                            'privacy' => 'required'
                        ],
                        'is_active' => true,
                    ]
                ];
                break;

            case 'EYE_REF_001':
                $templates = [
                    [
                        'template_code' => 'REF_001',
                        'template_name' => 'Refraction Test Template',
                        'description' => 'Standard refraction test for prescription determination',
                        'test_type' => 'refraction',
                        'test_parameters' => [
                            'sphere_right',
                            'sphere_left',
                            'cylinder_right',
                            'cylinder_left',
                            'axis_right',
                            'axis_left',
                            'prism_right',
                            'prism_left'
                        ],
                        'reference_ranges' => [
                            'sphere' => ['min' => -20, 'max' => 20],
                            'cylinder' => ['min' => -6, 'max' => 6],
                            'axis' => ['min' => 0, 'max' => 180]
                        ],
                        'abnormal_criteria' => [
                            'high_prescription' => ['operator' => '>', 'threshold' => 6]
                        ],
                        'equipment_config' => [
                            'phoropter' => ['auto_refraction' => true],
                            'trial_lenses' => ['increments' => 0.25]
                        ],
                        'test_sequence' => [
                            '1. Auto Refraction',
                            '2. Subjective Refraction',
                            '3. Binocular Balance',
                            '4. Near Vision Test'
                        ],
                        'estimated_duration_minutes' => 30,
                        'requires_dilation' => false,
                        'requires_dark_room' => false,
                        'requires_bright_light' => false,
                        'is_active' => true,
                    ]
                ];
                break;

            case 'EYE_PRES_001':
                $templates = [
                    [
                        'template_code' => 'IOP_001',
                        'template_name' => 'Intraocular Pressure Template',
                        'description' => 'Measurement of eye pressure using tonometry',
                        'test_type' => 'pressure',
                        'test_parameters' => [
                            'iop_right',
                            'iop_left',
                            'method_used',
                            'corneal_thickness_right',
                            'corneal_thickness_left'
                        ],
                        'reference_ranges' => [
                            'iop' => ['min' => 10, 'max' => 21],
                            'corneal_thickness' => ['min' => 500, 'max' => 600]
                        ],
                        'abnormal_criteria' => [
                            'high_pressure' => ['operator' => '>', 'threshold' => 21],
                            'low_pressure' => ['operator' => '<', 'threshold' => 10]
                        ],
                        'equipment_config' => [
                            'tonometer' => ['type' => 'Goldmann'],
                            'anesthetic' => 'Proparacaine'
                        ],
                        'test_sequence' => [
                            '1. Anesthetic Drops',
                            '2. Tonometer Calibration',
                            '3. Right Eye Measurement',
                            '4. Left Eye Measurement'
                        ],
                        'estimated_duration_minutes' => 15,
                        'requires_dilation' => false,
                        'requires_dark_room' => false,
                        'requires_bright_light' => false,
                        'is_active' => true,
                    ]
                ];
                break;

            case 'EYE_FUND_001':
                $templates = [
                    [
                        'template_code' => 'FUND_001',
                        'template_name' => 'Fundus Examination Template',
                        'description' => 'Examination of retina and optic nerve',
                        'test_type' => 'fundus',
                        'test_parameters' => [
                            'optic_nerve_right',
                            'optic_nerve_left',
                            'macula_right',
                            'macula_left',
                            'retinal_vessels_right',
                            'retinal_vessels_left',
                            'peripheral_retina_right',
                            'peripheral_retina_left'
                        ],
                        'reference_ranges' => [
                            'cup_disc_ratio' => ['min' => 0.1, 'max' => 0.5]
                        ],
                        'abnormal_criteria' => [
                            'high_cup_disc' => ['operator' => '>', 'threshold' => 0.6],
                            'retinal_detachment' => ['operator' => '=', 'threshold' => 'present']
                        ],
                        'equipment_config' => [
                            'ophthalmoscope' => ['type' => 'indirect'],
                            'fundus_camera' => ['resolution' => 'high']
                        ],
                        'test_sequence' => [
                            '1. Pupil Dilation',
                            '2. Dark Room Preparation',
                            '3. Optic Nerve Examination',
                            '4. Macula Examination',
                            '5. Peripheral Retina'
                        ],
                        'estimated_duration_minutes' => 25,
                        'requires_dilation' => true,
                        'dilation_requirements' => [
                            'drops' => 'Tropicamide 1% + Phenylephrine 2.5%',
                            'wait_time' => 30
                        ],
                        'requires_dark_room' => true,
                        'requires_bright_light' => false,
                        'environmental_requirements' => [
                            'lighting' => 'dim',
                            'privacy' => 'required'
                        ],
                        'is_active' => true,
                    ]
                ];
                break;

            case 'EYE_VF_001':
                $templates = [
                    [
                        'template_code' => 'VF_001',
                        'template_name' => 'Visual Field Test Template',
                        'description' => 'Automated perimetry for peripheral vision',
                        'test_type' => 'visual_field',
                        'test_parameters' => [
                            'mean_deviation_right',
                            'mean_deviation_left',
                            'pattern_standard_deviation_right',
                            'pattern_standard_deviation_left',
                            'reliability_index_right',
                            'reliability_index_left'
                        ],
                        'reference_ranges' => [
                            'mean_deviation' => ['min' => -2, 'max' => 2],
                            'reliability_index' => ['min' => 80, 'max' => 100]
                        ],
                        'abnormal_criteria' => [
                            'field_defect' => ['operator' => '<', 'threshold' => -2],
                            'low_reliability' => ['operator' => '<', 'threshold' => 80]
                        ],
                        'equipment_config' => [
                            'perimeter' => ['type' => 'Humphrey'],
                            'strategy' => 'SITA Standard'
                        ],
                        'test_sequence' => [
                            '1. Patient Positioning',
                            '2. Fixation Training',
                            '3. Right Eye Test',
                            '4. Left Eye Test',
                            '5. Results Analysis'
                        ],
                        'estimated_duration_minutes' => 20,
                        'requires_dilation' => false,
                        'requires_dark_room' => true,
                        'requires_bright_light' => false,
                        'environmental_requirements' => [
                            'lighting' => 'dim',
                            'noise' => 'minimal'
                        ],
                        'is_active' => true,
                    ]
                ];
                break;
        }

        foreach ($templates as $templateData) {
            $template = EyeTestTemplate::create([
                'service_id' => $service->id,
                ...$templateData,
            ]);

            $this->createTestParameters($template, $userId);
        }
    }

    /**
     * Create test parameters for a template.
     */
    private function createTestParameters(EyeTestTemplate $template, int $userId): void
    {
        $parameters = [];

        switch ($template->template_code) {
            case 'COMP_EYE_001':
                $parameters = [
                    [
                        'parameter_code' => 'VA_R',
                        'parameter_name' => 'Visual Acuity - Right Eye',
                        'description' => 'Distance visual acuity of right eye',
                        'data_type' => 'text',
                        'input_type' => 'select',
                        'input_options' => ['6/6', '6/9', '6/12', '6/18', '6/24', '6/36', '6/60', 'CF', 'HM', 'PL', 'NPL'],
                        'unit' => null,
                        'decimal_places' => 0,
                        'is_required' => true,
                        'is_critical' => true,
                        'validation_rules' => ['required'],
                        'reference_ranges' => [
                            'normal' => ['min' => '6/6', 'max' => '6/12'],
                            'abnormal' => ['min' => '6/18', 'max' => 'NPL']
                        ],
                        'abnormal_criteria' => [
                            'severe_impairment' => ['operator' => '<', 'threshold' => '6/18']
                        ],
                        'sort_order' => 1,
                        'is_active' => true,
                    ],
                    [
                        'parameter_code' => 'VA_L',
                        'parameter_name' => 'Visual Acuity - Left Eye',
                        'description' => 'Distance visual acuity of left eye',
                        'data_type' => 'text',
                        'input_type' => 'select',
                        'input_options' => ['6/6', '6/9', '6/12', '6/18', '6/24', '6/36', '6/60', 'CF', 'HM', 'PL', 'NPL'],
                        'unit' => null,
                        'decimal_places' => 0,
                        'is_required' => true,
                        'is_critical' => true,
                        'validation_rules' => ['required'],
                        'reference_ranges' => [
                            'normal' => ['min' => '6/6', 'max' => '6/12'],
                            'abnormal' => ['min' => '6/18', 'max' => 'NPL']
                        ],
                        'abnormal_criteria' => [
                            'severe_impairment' => ['operator' => '<', 'threshold' => '6/18']
                        ],
                        'sort_order' => 2,
                        'is_active' => true,
                    ],
                    [
                        'parameter_code' => 'IOP_R',
                        'parameter_name' => 'Intraocular Pressure - Right Eye',
                        'description' => 'Eye pressure measurement of right eye',
                        'data_type' => 'numeric',
                        'input_type' => 'number',
                        'input_options' => null,
                        'unit' => 'mmHg',
                        'decimal_places' => 1,
                        'is_required' => true,
                        'is_critical' => true,
                        'validation_rules' => ['required', 'min:0', 'max:50'],
                        'reference_ranges' => [
                            'normal' => ['min' => 10, 'max' => 21],
                            'high' => ['min' => 22, 'max' => 30],
                            'very_high' => ['min' => 31, 'max' => 50]
                        ],
                        'abnormal_criteria' => [
                            'high_pressure' => ['operator' => '>', 'threshold' => 21],
                            'very_high_pressure' => ['operator' => '>', 'threshold' => 30]
                        ],
                        'sort_order' => 3,
                        'is_active' => true,
                    ],
                    [
                        'parameter_code' => 'IOP_L',
                        'parameter_name' => 'Intraocular Pressure - Left Eye',
                        'description' => 'Eye pressure measurement of left eye',
                        'data_type' => 'numeric',
                        'input_type' => 'number',
                        'input_options' => null,
                        'unit' => 'mmHg',
                        'decimal_places' => 1,
                        'is_required' => true,
                        'is_critical' => true,
                        'validation_rules' => ['required', 'min:0', 'max:50'],
                        'reference_ranges' => [
                            'normal' => ['min' => 10, 'max' => 21],
                            'high' => ['min' => 22, 'max' => 30],
                            'very_high' => ['min' => 31, 'max' => 50]
                        ],
                        'abnormal_criteria' => [
                            'high_pressure' => ['operator' => '>', 'threshold' => 21],
                            'very_high_pressure' => ['operator' => '>', 'threshold' => 30]
                        ],
                        'sort_order' => 4,
                        'is_active' => true,
                    ],
                ];
                break;

            case 'REF_001':
                $parameters = [
                    [
                        'parameter_code' => 'SPH_R',
                        'parameter_name' => 'Sphere - Right Eye',
                        'description' => 'Spherical power for right eye',
                        'data_type' => 'numeric',
                        'input_type' => 'number',
                        'input_options' => null,
                        'unit' => 'D',
                        'decimal_places' => 2,
                        'is_required' => true,
                        'is_critical' => false,
                        'validation_rules' => ['required', 'min:-20', 'max:20'],
                        'reference_ranges' => [
                            'normal' => ['min' => -2, 'max' => 2],
                            'mild' => ['min' => -4, 'max' => 4],
                            'moderate' => ['min' => -6, 'max' => 6],
                            'high' => ['min' => -20, 'max' => 20]
                        ],
                        'abnormal_criteria' => [
                            'high_prescription' => ['operator' => '>', 'threshold' => 6]
                        ],
                        'sort_order' => 1,
                        'is_active' => true,
                    ],
                    [
                        'parameter_code' => 'SPH_L',
                        'parameter_name' => 'Sphere - Left Eye',
                        'description' => 'Spherical power for left eye',
                        'data_type' => 'numeric',
                        'input_type' => 'number',
                        'input_options' => null,
                        'unit' => 'D',
                        'decimal_places' => 2,
                        'is_required' => true,
                        'is_critical' => false,
                        'validation_rules' => ['required', 'min:-20', 'max:20'],
                        'reference_ranges' => [
                            'normal' => ['min' => -2, 'max' => 2],
                            'mild' => ['min' => -4, 'max' => 4],
                            'moderate' => ['min' => -6, 'max' => 6],
                            'high' => ['min' => -20, 'max' => 20]
                        ],
                        'abnormal_criteria' => [
                            'high_prescription' => ['operator' => '>', 'threshold' => 6]
                        ],
                        'sort_order' => 2,
                        'is_active' => true,
                    ],
                    [
                        'parameter_code' => 'CYL_R',
                        'parameter_name' => 'Cylinder - Right Eye',
                        'description' => 'Cylindrical power for right eye',
                        'data_type' => 'numeric',
                        'input_type' => 'number',
                        'input_options' => null,
                        'unit' => 'D',
                        'decimal_places' => 2,
                        'is_required' => true,
                        'is_critical' => false,
                        'validation_rules' => ['required', 'min:-6', 'max:6'],
                        'reference_ranges' => [
                            'normal' => ['min' => -0.5, 'max' => 0.5],
                            'mild' => ['min' => -1, 'max' => 1],
                            'moderate' => ['min' => -2, 'max' => 2],
                            'high' => ['min' => -6, 'max' => 6]
                        ],
                        'abnormal_criteria' => [
                            'high_astigmatism' => ['operator' => '>', 'threshold' => 2]
                        ],
                        'sort_order' => 3,
                        'is_active' => true,
                    ],
                    [
                        'parameter_code' => 'CYL_L',
                        'parameter_name' => 'Cylinder - Left Eye',
                        'description' => 'Cylindrical power for left eye',
                        'data_type' => 'numeric',
                        'input_type' => 'number',
                        'input_options' => null,
                        'unit' => 'D',
                        'decimal_places' => 2,
                        'is_required' => true,
                        'is_critical' => false,
                        'validation_rules' => ['required', 'min:-6', 'max:6'],
                        'reference_ranges' => [
                            'normal' => ['min' => -0.5, 'max' => 0.5],
                            'mild' => ['min' => -1, 'max' => 1],
                            'moderate' => ['min' => -2, 'max' => 2],
                            'high' => ['min' => -6, 'max' => 6]
                        ],
                        'abnormal_criteria' => [
                            'high_astigmatism' => ['operator' => '>', 'threshold' => 2]
                        ],
                        'sort_order' => 4,
                        'is_active' => true,
                    ],
                    [
                        'parameter_code' => 'AXIS_R',
                        'parameter_name' => 'Axis - Right Eye',
                        'description' => 'Cylindrical axis for right eye',
                        'data_type' => 'numeric',
                        'input_type' => 'number',
                        'input_options' => null,
                        'unit' => 'degrees',
                        'decimal_places' => 0,
                        'is_required' => true,
                        'is_critical' => false,
                        'validation_rules' => ['required', 'min:0', 'max:180'],
                        'reference_ranges' => [
                            'normal' => ['min' => 0, 'max' => 180]
                        ],
                        'abnormal_criteria' => [],
                        'sort_order' => 5,
                        'is_active' => true,
                    ],
                    [
                        'parameter_code' => 'AXIS_L',
                        'parameter_name' => 'Axis - Left Eye',
                        'description' => 'Cylindrical axis for left eye',
                        'data_type' => 'numeric',
                        'input_type' => 'number',
                        'input_options' => null,
                        'unit' => 'degrees',
                        'decimal_places' => 0,
                        'is_required' => true,
                        'is_critical' => false,
                        'validation_rules' => ['required', 'min:0', 'max:180'],
                        'reference_ranges' => [
                            'normal' => ['min' => 0, 'max' => 180]
                        ],
                        'abnormal_criteria' => [],
                        'sort_order' => 6,
                        'is_active' => true,
                    ],
                ];
                break;
        }

        foreach ($parameters as $parameterData) {
            EyeTestParameter::create([
                'template_id' => $template->id,
                ...$parameterData,
            ]);
        }
    }
}
