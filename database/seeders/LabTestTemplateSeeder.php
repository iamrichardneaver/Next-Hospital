<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LabTestTemplate;
use App\Models\LabTestParameter;
use App\Models\LabReferenceRange;
use App\Models\LabCriticalValue;

class LabTestTemplateSeeder extends Seeder
{
    public function run()
    {
        // Complete Blood Count (CBC) Template
        $cbcTemplate = LabTestTemplate::updateOrCreate(
            ['template_code' => 'CBC'],
            [
            'template_code' => 'CBC',
            'template_name' => 'Complete Blood Count',
            'category' => 'Hematology',
            'subcategory' => 'Complete Blood Count',
            'description' => 'Complete blood count with differential',
            'test_type' => 'quantitative',
            'specimen_type' => 'Blood',
            'collection_instructions' => [
                'Collect 2-3ml venous blood in EDTA tube',
                'Mix gently by inverting 8-10 times',
                'Process within 2 hours of collection'
            ],
            'preparation_instructions' => [
                'No special preparation required',
                'Patient may eat and drink normally'
            ],
            'storage_requirements' => [
                'Store at room temperature',
                'Do not refrigerate',
                'Process within 2 hours'
            ],
            'methodology' => 'Automated hematology analyzer',
            'equipment_required' => 'Hematology Analyzer',
            'routine_tat_hours' => 2,
            'urgent_tat_hours' => 1,
            'stat_tat_hours' => 0.5,
            'cost' => 25.00,
            'nhis_cost' => 20.00,
            'nhis_covered' => true,
            'is_active' => true,
            'is_template_bank' => true,
            'template_source' => 'WHO/CDC',
            'created_by' => 1
            ]
        );

        // CBC Parameters
        $cbcParameters = [
            ['code' => 'WBC', 'name' => 'White Blood Cell Count', 'unit' => '×10³/μL', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 2, 'is_critical' => true, 'allows_delta_check' => true],
            ['code' => 'RBC', 'name' => 'Red Blood Cell Count', 'unit' => '×10⁶/μL', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 2, 'is_critical' => false, 'allows_delta_check' => true],
            ['code' => 'HGB', 'name' => 'Hemoglobin', 'unit' => 'g/dL', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 1, 'is_critical' => true, 'allows_delta_check' => true],
            ['code' => 'HCT', 'name' => 'Hematocrit', 'unit' => '%', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 1, 'is_critical' => true, 'allows_delta_check' => true],
            ['code' => 'MCV', 'name' => 'Mean Corpuscular Volume', 'unit' => 'fL', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 1, 'is_critical' => false, 'allows_delta_check' => false],
            ['code' => 'MCH', 'name' => 'Mean Corpuscular Hemoglobin', 'unit' => 'pg', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 1, 'is_critical' => false, 'allows_delta_check' => false],
            ['code' => 'MCHC', 'name' => 'Mean Corpuscular Hemoglobin Concentration', 'unit' => 'g/dL', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 1, 'is_critical' => false, 'allows_delta_check' => false],
            ['code' => 'PLT', 'name' => 'Platelet Count', 'unit' => '×10³/μL', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 0, 'is_critical' => true, 'allows_delta_check' => true],
            ['code' => 'NEUT', 'name' => 'Neutrophils', 'unit' => '%', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 1, 'is_critical' => false, 'allows_delta_check' => true],
            ['code' => 'LYMPH', 'name' => 'Lymphocytes', 'unit' => '%', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 1, 'is_critical' => false, 'allows_delta_check' => true],
            ['code' => 'MONO', 'name' => 'Monocytes', 'unit' => '%', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 1, 'is_critical' => false, 'allows_delta_check' => false],
            ['code' => 'EOS', 'name' => 'Eosinophils', 'unit' => '%', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 1, 'is_critical' => false, 'allows_delta_check' => false],
            ['code' => 'BASO', 'name' => 'Basophils', 'unit' => '%', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 1, 'is_critical' => false, 'allows_delta_check' => false]
        ];

        foreach ($cbcParameters as $index => $param) {
            $parameter = LabTestParameter::updateOrCreate(
                ['template_id' => $cbcTemplate->id, 'parameter_code' => $param['code']],
                [
                'template_id' => $cbcTemplate->id,
                'parameter_code' => $param['code'],
                'parameter_name' => $param['name'],
                'data_type' => $param['data_type'],
                'input_type' => $param['input_type'],
                'unit' => $param['unit'],
                'decimal_places' => $param['decimal_places'],
                'is_required' => true,
                'is_critical' => $param['is_critical'],
                'allows_delta_check' => $param['allows_delta_check'],
                'sort_order' => $index + 1,
                'is_active' => true
                ]
            );

            // Add reference ranges for WBC
            if ($param['code'] === 'WBC') {
                $this->createReferenceRanges($parameter, [
                    ['age_group' => 'Newborn', 'gender' => 'Both', 'min_value' => 9.0, 'max_value' => 30.0],
                    ['age_group' => 'Infant', 'gender' => 'Both', 'min_value' => 6.0, 'max_value' => 17.5],
                    ['age_group' => 'Child', 'gender' => 'Both', 'min_value' => 5.0, 'max_value' => 15.5],
                    ['age_group' => 'Adult', 'gender' => 'Male', 'min_value' => 4.5, 'max_value' => 11.0],
                    ['age_group' => 'Adult', 'gender' => 'Female', 'min_value' => 4.5, 'max_value' => 11.0],
                    ['age_group' => 'Elderly', 'gender' => 'Both', 'min_value' => 3.5, 'max_value' => 9.0]
                ]);

                $this->createCriticalValues($parameter, [
                    ['age_group' => 'Adult', 'gender' => 'Both', 'critical_low' => 2.0, 'critical_high' => 30.0, 'panic_low' => 1.0, 'panic_high' => 50.0]
                ]);
            }

            // Add reference ranges for HGB
            if ($param['code'] === 'HGB') {
                $this->createReferenceRanges($parameter, [
                    ['age_group' => 'Newborn', 'gender' => 'Both', 'min_value' => 13.5, 'max_value' => 20.0],
                    ['age_group' => 'Infant', 'gender' => 'Both', 'min_value' => 10.0, 'max_value' => 14.0],
                    ['age_group' => 'Child', 'gender' => 'Both', 'min_value' => 11.0, 'max_value' => 13.0],
                    ['age_group' => 'Adult', 'gender' => 'Male', 'min_value' => 13.8, 'max_value' => 17.2],
                    ['age_group' => 'Adult', 'gender' => 'Female', 'min_value' => 12.1, 'max_value' => 15.1],
                    ['age_group' => 'Adult', 'gender' => 'Female', 'is_pregnant' => true, 'min_value' => 11.0, 'max_value' => 13.0],
                    ['age_group' => 'Elderly', 'gender' => 'Male', 'min_value' => 12.0, 'max_value' => 16.0],
                    ['age_group' => 'Elderly', 'gender' => 'Female', 'min_value' => 11.0, 'max_value' => 15.0]
                ]);

                $this->createCriticalValues($parameter, [
                    ['age_group' => 'Adult', 'gender' => 'Male', 'critical_low' => 7.0, 'critical_high' => 20.0, 'panic_low' => 5.0, 'panic_high' => 25.0],
                    ['age_group' => 'Adult', 'gender' => 'Female', 'critical_low' => 6.0, 'critical_high' => 18.0, 'panic_low' => 4.0, 'panic_high' => 22.0]
                ]);
            }
        }

        // Liver Function Tests Template
        $lftTemplate = LabTestTemplate::updateOrCreate(
            ['template_code' => 'LFT'],
            [
            'template_code' => 'LFT',
            'template_name' => 'Liver Function Tests',
            'category' => 'Biochemistry',
            'subcategory' => 'Liver Function',
            'description' => 'Comprehensive liver function panel',
            'test_type' => 'quantitative',
            'specimen_type' => 'Blood',
            'collection_instructions' => [
                'Collect 3-5ml venous blood in plain tube',
                'Allow to clot for 30 minutes',
                'Centrifuge and separate serum within 2 hours'
            ],
            'preparation_instructions' => [
                'Fasting required for 8-12 hours',
                'Water is allowed',
                'Avoid alcohol for 24 hours'
            ],
            'storage_requirements' => [
                'Store serum at 2-8°C',
                'Stable for 7 days refrigerated'
            ],
            'methodology' => 'Automated chemistry analyzer',
            'equipment_required' => 'Chemistry Analyzer',
            'routine_tat_hours' => 4,
            'urgent_tat_hours' => 2,
            'stat_tat_hours' => 1,
            'cost' => 45.00,
            'nhis_cost' => 35.00,
            'nhis_covered' => true,
            'is_active' => true,
            'is_template_bank' => true,
            'template_source' => 'WHO/CDC',
            'created_by' => 1
            ]
        );

        // LFT Parameters
        $lftParameters = [
            ['code' => 'ALT', 'name' => 'Alanine Aminotransferase', 'unit' => 'U/L', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 0, 'is_critical' => true, 'allows_delta_check' => true],
            ['code' => 'AST', 'name' => 'Aspartate Aminotransferase', 'unit' => 'U/L', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 0, 'is_critical' => true, 'allows_delta_check' => true],
            ['code' => 'ALP', 'name' => 'Alkaline Phosphatase', 'unit' => 'U/L', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 0, 'is_critical' => false, 'allows_delta_check' => true],
            ['code' => 'TBIL', 'name' => 'Total Bilirubin', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 1, 'is_critical' => true, 'allows_delta_check' => true],
            ['code' => 'DBIL', 'name' => 'Direct Bilirubin', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 1, 'is_critical' => false, 'allows_delta_check' => true],
            ['code' => 'ALB', 'name' => 'Albumin', 'unit' => 'g/dL', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 1, 'is_critical' => false, 'allows_delta_check' => true],
            ['code' => 'TP', 'name' => 'Total Protein', 'unit' => 'g/dL', 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 1, 'is_critical' => false, 'allows_delta_check' => false]
        ];

        foreach ($lftParameters as $index => $param) {
            LabTestParameter::updateOrCreate(
                ['template_id' => $lftTemplate->id, 'parameter_code' => $param['code']],
                [
                'template_id' => $lftTemplate->id,
                'parameter_code' => $param['code'],
                'parameter_name' => $param['name'],
                'data_type' => $param['data_type'],
                'input_type' => $param['input_type'],
                'unit' => $param['unit'],
                'decimal_places' => $param['decimal_places'],
                'is_required' => true,
                'is_critical' => $param['is_critical'],
                'allows_delta_check' => $param['allows_delta_check'],
                'sort_order' => $index + 1,
                'is_active' => true
                ]
            );
        }

        // Urinalysis Template
        $urinalysisTemplate = LabTestTemplate::updateOrCreate(
            ['template_code' => 'UA'],
            [
            'template_code' => 'UA',
            'template_name' => 'Urinalysis',
            'category' => 'Clinical Chemistry',
            'subcategory' => 'Urinalysis',
            'description' => 'Complete urinalysis with microscopy',
            'test_type' => 'combined',
            'specimen_type' => 'Urine',
            'collection_instructions' => [
                'Collect mid-stream urine in sterile container',
                'Process within 2 hours of collection',
                'Refrigerate if delay expected'
            ],
            'preparation_instructions' => [
                'Clean genital area before collection',
                'First morning urine preferred',
                'Avoid contamination'
            ],
            'storage_requirements' => [
                'Store at 2-8°C if not processed immediately',
                'Process within 2 hours for best results'
            ],
            'methodology' => 'Dipstick + Microscopy',
            'equipment_required' => 'Urine Analyzer + Microscope',
            'routine_tat_hours' => 2,
            'urgent_tat_hours' => 1,
            'stat_tat_hours' => 0.5,
            'cost' => 15.00,
            'nhis_cost' => 12.00,
            'nhis_covered' => true,
            'is_active' => true,
            'is_template_bank' => true,
            'template_source' => 'WHO/CDC',
            'created_by' => 1
            ]
        );

        // Urinalysis Parameters (Mixed types)
        $uaParameters = [
            ['code' => 'COLOR', 'name' => 'Color', 'unit' => null, 'data_type' => 'text', 'input_type' => 'select', 'input_options' => ['Yellow', 'Amber', 'Red', 'Brown', 'Green', 'Blue'], 'is_critical' => false, 'allows_delta_check' => false],
            ['code' => 'APPEARANCE', 'name' => 'Appearance', 'unit' => null, 'data_type' => 'text', 'input_type' => 'select', 'input_options' => ['Clear', 'Slightly Cloudy', 'Cloudy', 'Turbid'], 'is_critical' => false, 'allows_delta_check' => false],
            ['code' => 'SG', 'name' => 'Specific Gravity', 'unit' => null, 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 3, 'is_critical' => false, 'allows_delta_check' => true],
            ['code' => 'PH', 'name' => 'pH', 'unit' => null, 'data_type' => 'numeric', 'input_type' => 'number', 'decimal_places' => 1, 'is_critical' => false, 'allows_delta_check' => false],
            ['code' => 'GLUCOSE', 'name' => 'Glucose', 'unit' => 'mg/dL', 'data_type' => 'text', 'input_type' => 'select', 'input_options' => ['Negative', 'Trace', '1+', '2+', '3+', '4+'], 'is_critical' => true, 'allows_delta_check' => true],
            ['code' => 'PROTEIN', 'name' => 'Protein', 'unit' => 'mg/dL', 'data_type' => 'text', 'input_type' => 'select', 'input_options' => ['Negative', 'Trace', '1+', '2+', '3+', '4+'], 'is_critical' => false, 'allows_delta_check' => true],
            ['code' => 'BLOOD', 'name' => 'Blood', 'unit' => null, 'data_type' => 'text', 'input_type' => 'select', 'input_options' => ['Negative', 'Trace', '1+', '2+', '3+', '4+'], 'is_critical' => true, 'allows_delta_check' => true],
            ['code' => 'LEUKOCYTES', 'name' => 'Leukocytes', 'unit' => null, 'data_type' => 'text', 'input_type' => 'select', 'input_options' => ['Negative', 'Trace', '1+', '2+', '3+', '4+'], 'is_critical' => false, 'allows_delta_check' => true],
            ['code' => 'NITRITE', 'name' => 'Nitrite', 'unit' => null, 'data_type' => 'boolean', 'input_type' => 'radio', 'input_options' => ['Negative', 'Positive'], 'is_critical' => false, 'allows_delta_check' => false],
            ['code' => 'KETONES', 'name' => 'Ketones', 'unit' => 'mg/dL', 'data_type' => 'text', 'input_type' => 'select', 'input_options' => ['Negative', 'Trace', '1+', '2+', '3+', '4+'], 'is_critical' => true, 'allows_delta_check' => true],
            ['code' => 'BILIRUBIN', 'name' => 'Bilirubin', 'unit' => null, 'data_type' => 'text', 'input_type' => 'select', 'input_options' => ['Negative', 'Trace', '1+', '2+', '3+', '4+'], 'is_critical' => false, 'allows_delta_check' => false],
            ['code' => 'UROBILINOGEN', 'name' => 'Urobilinogen', 'unit' => 'mg/dL', 'data_type' => 'text', 'input_type' => 'select', 'input_options' => ['Normal', 'Increased', 'Decreased'], 'is_critical' => false, 'allows_delta_check' => false],
            ['code' => 'MICROSCOPY', 'name' => 'Microscopy', 'unit' => null, 'data_type' => 'text', 'input_type' => 'rich_text', 'is_critical' => false, 'allows_delta_check' => false]
        ];

        foreach ($uaParameters as $index => $param) {
            LabTestParameter::updateOrCreate(
                ['template_id' => $urinalysisTemplate->id, 'parameter_code' => $param['code']],
                [
                'template_id' => $urinalysisTemplate->id,
                'parameter_code' => $param['code'],
                'parameter_name' => $param['name'],
                'data_type' => $param['data_type'],
                'input_type' => $param['input_type'],
                'input_options' => $param['input_options'] ?? null,
                'unit' => $param['unit'],
                'decimal_places' => $param['decimal_places'] ?? 0,
                'is_required' => true,
                'is_critical' => $param['is_critical'],
                'allows_delta_check' => $param['allows_delta_check'],
                'sort_order' => $index + 1,
                'is_active' => true
                ]
            );
        }
    }

    private function createReferenceRanges($parameter, $ranges)
    {
        foreach ($ranges as $range) {
            LabReferenceRange::create([
                'parameter_id' => $parameter->id,
                'age_group' => $range['age_group'],
                'gender' => $range['gender'],
                'is_pregnant' => $range['is_pregnant'] ?? false,
                'pregnancy_trimester' => $range['pregnancy_trimester'] ?? null,
                'min_value' => $range['min_value'],
                'max_value' => $range['max_value'],
                'min_operator' => '>=',
                'max_operator' => '<=',
                'unit' => $parameter->unit,
                'source' => 'WHO/CDC',
                'is_active' => true
            ]);
        }
    }

    private function createCriticalValues($parameter, $values)
    {
        foreach ($values as $value) {
            LabCriticalValue::create([
                'parameter_id' => $parameter->id,
                'age_group' => $value['age_group'],
                'gender' => $value['gender'],
                'is_pregnant' => $value['is_pregnant'] ?? false,
                'critical_low' => $value['critical_low'],
                'critical_high' => $value['critical_high'],
                'panic_low' => $value['panic_low'],
                'panic_high' => $value['panic_high'],
                'unit' => $parameter->unit,
                'alert_message' => "CRITICAL VALUE ALERT: {$parameter->parameter_name}",
                'notification_recipients' => ['doctor', 'nurse', 'lab_manager'],
                'escalation_time_minutes' => 15,
                'is_active' => true
            ]);
        }
    }
}
