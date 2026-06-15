<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LabTestTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $testTypes = [
            // HEMATOLOGY TESTS
            [
                'test_code' => 'FBC',
                'test_name' => 'Full Blood Count',
                'category' => 'Hematology',
                'subcategory' => 'Complete Blood Count',
                'description' => 'Complete blood count including hemoglobin, hematocrit, white blood cells, red blood cells, and platelets',
                'specimen_type' => 'Blood',
                'collection_method' => 'Venipuncture',
                'preparation_instructions' => json_encode(['Fasting not required', 'No special preparation needed']),
                'collection_instructions' => json_encode(['Collect 2-3ml EDTA blood', 'Mix gently by inversion']),
                'storage_requirements' => json_encode(['Store at 2-8°C', 'Process within 4 hours']),
                'transport_requirements' => json_encode(['Transport at room temperature', 'Avoid extreme temperatures']),
                'parameters' => json_encode([
                    'Hemoglobin (Hb)',
                    'Hematocrit (Hct)',
                    'White Blood Cell Count (WBC)',
                    'Red Blood Cell Count (RBC)',
                    'Platelet Count (PLT)',
                    'Mean Corpuscular Volume (MCV)',
                    'Mean Corpuscular Hemoglobin (MCH)',
                    'Mean Corpuscular Hemoglobin Concentration (MCHC)',
                    'Red Cell Distribution Width (RDW)',
                    'Differential Count (Neutrophils, Lymphocytes, Monocytes, Eosinophils, Basophils)'
                ]),
                'normal_ranges' => json_encode([
                    'Hb' => ['Male' => '13.8-17.2 g/dL', 'Female' => '12.1-15.1 g/dL'],
                    'Hct' => ['Male' => '40.7-50.3%', 'Female' => '36.1-44.3%'],
                    'WBC' => ['Adult' => '4.5-11.0 x 10³/μL'],
                    'RBC' => ['Male' => '4.7-6.1 x 10⁶/μL', 'Female' => '4.2-5.4 x 10⁶/μL'],
                    'PLT' => ['Adult' => '150-450 x 10³/μL']
                ]),
                'critical_values' => json_encode([
                    'Hb' => ['Critical Low' => '<7.0 g/dL', 'Critical High' => '>20.0 g/dL'],
                    'WBC' => ['Critical Low' => '<2.0 x 10³/μL', 'Critical High' => '>30.0 x 10³/μL'],
                    'PLT' => ['Critical Low' => '<50 x 10³/μL', 'Critical High' => '>1000 x 10³/μL']
                ]),
                'units' => json_encode(['g/dL', '%', 'x 10³/μL', 'x 10⁶/μL', 'fL', 'pg', 'g/dL']),
                'routine_tat_hours' => 4,
                'urgent_tat_hours' => 2,
                'stat_tat_hours' => 1,
                'cost' => 25.00,
                'nhis_cost' => 20.00,
                'nhis_covered' => true,
                'requires_qc' => true,
                'qc_requirements' => json_encode(['Daily QC', 'Calibration verification']),
                'requires_verification' => true,
                'verification_requirements' => json_encode(['Technician verification', 'Supervisor review']),
                'equipment_required' => 'Automated Hematology Analyzer',
                'reagents_required' => json_encode(['EDTA tubes', 'Calibrators', 'Controls', 'Cleaning solutions']),
                'methodology' => 'Automated impedance and flow cytometry',
                'ghs_code' => 'LAB001',
                'ghs_mandatory' => false,
                'ghs_reporting_requirements' => json_encode(['Monthly statistics', 'Quality indicators']),
                'is_active' => true,
                'requires_doctor_approval' => false,
                'requires_consultant_review' => false,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // BIOCHEMISTRY TESTS
            [
                'test_code' => 'LFT',
                'test_name' => 'Liver Function Test',
                'category' => 'Biochemistry',
                'subcategory' => 'Liver Function',
                'description' => 'Comprehensive liver function assessment including enzymes, proteins, and bilirubin',
                'specimen_type' => 'Blood',
                'collection_method' => 'Venipuncture',
                'preparation_instructions' => json_encode(['Fasting required for 8-12 hours', 'Water allowed']),
                'collection_instructions' => json_encode(['Collect 3-5ml serum separator tube', 'Allow to clot for 30 minutes']),
                'storage_requirements' => json_encode(['Store at 2-8°C', 'Process within 24 hours']),
                'transport_requirements' => json_encode(['Transport at 2-8°C', 'Avoid freezing']),
                'parameters' => json_encode([
                    'Alanine Aminotransferase (ALT)',
                    'Aspartate Aminotransferase (AST)',
                    'Alkaline Phosphatase (ALP)',
                    'Total Bilirubin',
                    'Direct Bilirubin',
                    'Indirect Bilirubin',
                    'Total Protein',
                    'Albumin',
                    'Globulin',
                    'Albumin/Globulin Ratio'
                ]),
                'normal_ranges' => json_encode([
                    'ALT' => ['Adult' => '7-56 U/L'],
                    'AST' => ['Adult' => '10-40 U/L'],
                    'ALP' => ['Adult' => '44-147 U/L'],
                    'Total Bilirubin' => ['Adult' => '0.3-1.2 mg/dL'],
                    'Direct Bilirubin' => ['Adult' => '0.0-0.3 mg/dL'],
                    'Total Protein' => ['Adult' => '6.0-8.3 g/dL'],
                    'Albumin' => ['Adult' => '3.5-5.0 g/dL']
                ]),
                'critical_values' => json_encode([
                    'ALT' => ['Critical High' => '>500 U/L'],
                    'AST' => ['Critical High' => '>500 U/L'],
                    'Total Bilirubin' => ['Critical High' => '>15 mg/dL']
                ]),
                'units' => json_encode(['U/L', 'mg/dL', 'g/dL']),
                'routine_tat_hours' => 6,
                'urgent_tat_hours' => 3,
                'stat_tat_hours' => 1,
                'cost' => 35.00,
                'nhis_cost' => 28.00,
                'nhis_covered' => true,
                'requires_qc' => true,
                'qc_requirements' => json_encode(['Daily QC', 'Calibration verification', 'Linearity check']),
                'requires_verification' => true,
                'verification_requirements' => json_encode(['Technician verification', 'Pathologist review for abnormal results']),
                'equipment_required' => 'Automated Chemistry Analyzer',
                'reagents_required' => json_encode(['Serum separator tubes', 'Calibrators', 'Controls', 'Enzyme reagents']),
                'methodology' => 'Enzymatic colorimetric assay',
                'ghs_code' => 'LAB002',
                'ghs_mandatory' => false,
                'ghs_reporting_requirements' => json_encode(['Monthly statistics', 'Quality indicators']),
                'is_active' => true,
                'requires_doctor_approval' => false,
                'requires_consultant_review' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // MICROBIOLOGY TESTS
            [
                'test_code' => 'CSU',
                'test_name' => 'Culture and Sensitivity - Urine',
                'category' => 'Microbiology',
                'subcategory' => 'Bacterial Culture',
                'description' => 'Bacterial culture and antibiotic sensitivity testing of urine specimen',
                'specimen_type' => 'Urine',
                'collection_method' => 'Midstream clean catch',
                'preparation_instructions' => json_encode(['Clean genital area', 'Collect midstream urine', 'Avoid contamination']),
                'collection_instructions' => json_encode(['Use sterile container', 'Collect 10-15ml', 'Transport immediately']),
                'storage_requirements' => json_encode(['Store at 2-8°C', 'Process within 2 hours']),
                'transport_requirements' => json_encode(['Transport at 2-8°C', 'Process within 2 hours']),
                'parameters' => json_encode([
                    'Organism Identification',
                    'Colony Count',
                    'Antibiotic Sensitivity',
                    'Resistance Pattern'
                ]),
                'normal_ranges' => json_encode([
                    'Colony Count' => ['Normal' => '<10³ CFU/ml', 'Significant' => '≥10⁵ CFU/ml']
                ]),
                'critical_values' => json_encode([
                    'Colony Count' => ['Critical' => '≥10⁵ CFU/ml with significant organism']
                ]),
                'units' => json_encode(['CFU/ml', 'mm', 'S/R/I']),
                'routine_tat_hours' => 48,
                'urgent_tat_hours' => 24,
                'stat_tat_hours' => 12,
                'cost' => 45.00,
                'nhis_cost' => 36.00,
                'nhis_covered' => true,
                'requires_qc' => true,
                'qc_requirements' => json_encode(['Daily QC', 'Media quality control', 'Antibiotic disk verification']),
                'requires_verification' => true,
                'verification_requirements' => json_encode(['Microbiologist verification', 'Antibiotic sensitivity review']),
                'equipment_required' => 'Incubator, Microscope, Automated ID System',
                'reagents_required' => json_encode(['Culture media', 'Antibiotic disks', 'Identification kits']),
                'methodology' => 'Standard culture and sensitivity testing',
                'ghs_code' => 'LAB003',
                'ghs_mandatory' => true,
                'ghs_reporting_requirements' => json_encode(['Monthly statistics', 'Antibiotic resistance patterns', 'Outbreak reporting']),
                'is_active' => true,
                'requires_doctor_approval' => true,
                'requires_consultant_review' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // IMMUNOLOGY TESTS
            [
                'test_code' => 'HIV',
                'test_name' => 'HIV Rapid Test',
                'category' => 'Immunology',
                'subcategory' => 'Infectious Disease',
                'description' => 'Rapid diagnostic test for HIV antibodies',
                'specimen_type' => 'Blood',
                'collection_method' => 'Finger prick or venipuncture',
                'preparation_instructions' => json_encode(['No special preparation required', 'Counseling recommended']),
                'collection_instructions' => json_encode(['Collect 2-3 drops blood', 'Apply to test strip']),
                'storage_requirements' => json_encode(['Store at room temperature', 'Use within 30 minutes']),
                'transport_requirements' => json_encode(['Transport at room temperature', 'Avoid extreme temperatures']),
                'parameters' => json_encode([
                    'HIV Antibody Test',
                    'Control Line',
                    'Test Line'
                ]),
                'normal_ranges' => json_encode([
                    'HIV Antibody' => ['Negative' => 'No test line', 'Positive' => 'Test line present']
                ]),
                'critical_values' => json_encode([
                    'HIV Antibody' => ['Critical' => 'Positive result requires confirmation']
                ]),
                'units' => json_encode(['Reactive/Non-reactive']),
                'routine_tat_hours' => 1,
                'urgent_tat_hours' => 1,
                'stat_tat_hours' => 1,
                'cost' => 15.00,
                'nhis_cost' => 12.00,
                'nhis_covered' => true,
                'requires_qc' => true,
                'qc_requirements' => json_encode(['Daily QC', 'Control testing', 'Lot verification']),
                'requires_verification' => true,
                'verification_requirements' => json_encode(['Technician verification', 'Supervisor review for positive results']),
                'equipment_required' => 'HIV Rapid Test Kit',
                'reagents_required' => json_encode(['Test strips', 'Buffer solution', 'Controls']),
                'methodology' => 'Lateral flow immunoassay',
                'ghs_code' => 'LAB004',
                'ghs_mandatory' => true,
                'ghs_reporting_requirements' => json_encode(['Monthly statistics', 'Positive case reporting', 'Quality indicators']),
                'is_active' => true,
                'requires_doctor_approval' => true,
                'requires_consultant_review' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('lab_test_types')->insert($testTypes);
    }
}
