<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\LabTestType;
use App\Models\SurgeryProcedure;
use App\Models\Theatre;
use App\Models\Ward;
use App\Models\Drug;
use App\Models\InsuranceProvider;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $this->createRoles();
        
        // Create permissions
        $this->createPermissions();
        
        // Create super admin user
        $this->createSuperAdmin();
        
        // Create branches
        $this->createBranches();
        
        // Create lab test types
        $this->createLabTestTypes();
        
        // Create surgery procedures
        $this->createSurgeryProcedures();
        
        // Create theatres
        $this->createTheatres();
        
        // Create wards
        $this->createWards();
        
        // Create sample drugs
        $this->createDrugs();
        
        // Create insurance providers
        $this->createInsuranceProviders();
    }

    private function createRoles()
    {
        $roles = [
            'super_admin',
            'admin',
            'doctor',
            'nurse',
            'lab_technician',
            'pharmacist',
            'receptionist',
            'accountant',
            'emergency_staff',
            'surgery_staff',
            'patient'
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    private function createPermissions()
    {
        $permissions = [
            // Patient permissions
            'view_patients', 'create_patients', 'edit_patients', 'delete_patients',
            
            // Appointment permissions
            'view_appointments', 'create_appointments', 'edit_appointments', 'delete_appointments',
            
            // Consultation permissions
            'view_consultations', 'create_consultations', 'edit_consultations', 'delete_consultations',
            
            // Lab permissions
            'view_lab_requests', 'create_lab_requests', 'edit_lab_requests', 'delete_lab_requests',
            'view_lab_results', 'create_lab_results', 'edit_lab_results', 'delete_lab_results',
            
            // Pharmacy permissions
            'view_drugs', 'create_drugs', 'edit_drugs', 'delete_drugs',
            'view_prescriptions', 'create_prescriptions', 'edit_prescriptions', 'delete_prescriptions',
            
            // Billing permissions
            'view_invoices', 'create_invoices', 'edit_invoices', 'delete_invoices',
            'view_payments', 'create_payments', 'edit_payments', 'delete_payments',
            
            // Insurance permissions
            'view_insurance', 'create_insurance', 'edit_insurance', 'delete_insurance',
            'view_insurance_providers', 'create_insurance_providers', 'edit_insurance_providers', 'delete_insurance_providers',
            'view_insurance_policies', 'create_insurance_policies', 'edit_insurance_policies', 'delete_insurance_policies',
            'view_insurance_claims', 'create_insurance_claims', 'edit_insurance_claims', 'delete_insurance_claims',
            'view_pre_authorizations', 'create_pre_authorizations', 'edit_pre_authorizations', 'delete_pre_authorizations',
            'view_coverage_policies', 'create_coverage_policies', 'edit_coverage_policies', 'delete_coverage_policies',
            'calculate_insurance_coverage', 'process_insurance_claims', 'manage_insurance_reports',
            
            // Ward permissions
            'view_wards', 'create_wards', 'edit_wards', 'delete_wards',
            'view_beds', 'create_beds', 'edit_beds', 'delete_beds',
            
            // Emergency permissions
            'view_emergency_visits', 'create_emergency_visits', 'edit_emergency_visits', 'delete_emergency_visits',
            'view_emergency_alerts', 'create_emergency_alerts', 'edit_emergency_alerts', 'delete_emergency_alerts',
            
            // Surgery permissions
            'view_surgery_schedules', 'create_surgery_schedules', 'edit_surgery_schedules', 'delete_surgery_schedules',
            'view_theatres', 'create_theatres', 'edit_theatres', 'delete_theatres',
            
            // User management permissions
            'view_users', 'create_users', 'edit_users', 'delete_users',
            'view_roles', 'create_roles', 'edit_roles', 'delete_roles',
            
            // Reporting permissions
            'view_reports', 'create_reports', 'export_reports',
            
            // File upload permissions
            'upload_files', 'view_files', 'delete_files',
            
            // Sync permissions
            'sync_data', 'view_sync_logs'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }

    private function createSuperAdmin()
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@nexthospital.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'name' => 'Super Admin',
                'email' => 'admin@nexthospital.com',
                'password' => Hash::make('password123'),
                'is_active' => true
            ]
        );

        $superAdmin->assignRole('super_admin');
    }

    private function createBranches()
    {
        $branches = [
            [
                'name' => 'Main Hospital',
                'code' => 'MAIN',
                'address' => '123 Independence Avenue, Accra Central',
                'phone' => '+233 302 123456',
                'email' => 'main@nexthospital.com',
                'is_active' => true
            ],
            [
                'name' => 'East Legon Branch',
                'code' => 'ELEG',
                'address' => '456 East Legon Road, Accra',
                'phone' => '+233 302 654321',
                'email' => 'eastlegon@nexthospital.com',
                'is_active' => true
            ],
            [
                'name' => 'Tema Branch',
                'code' => 'TEMA',
                'address' => '789 Tema Industrial Area, Tema',
                'phone' => '+233 303 789012',
                'email' => 'tema@nexthospital.com',
                'is_active' => true
            ]
        ];

        foreach ($branches as $branchData) {
            Branch::firstOrCreate(
                ['name' => $branchData['name']],
                $branchData
            );
        }
    }

    private function createLabTestTypes()
    {
        $testTypes = [
            // Hematology
            [
                'name' => 'Complete Blood Count (CBC)',
                'category' => 'Hematology',
                'description' => 'Complete blood count with differential',
                'normal_range_male' => '4.5-5.5 x 10^12/L',
                'normal_range_female' => '4.0-5.0 x 10^12/L',
                'unit' => 'x 10^12/L',
                'is_active' => true
            ],
            [
                'name' => 'Hemoglobin',
                'category' => 'Hematology',
                'description' => 'Hemoglobin level',
                'normal_range_male' => '13.8-17.2 g/dL',
                'normal_range_female' => '12.1-15.1 g/dL',
                'unit' => 'g/dL',
                'is_active' => true
            ],
            [
                'name' => 'White Blood Cell Count',
                'category' => 'Hematology',
                'description' => 'Total white blood cell count',
                'normal_range_male' => '4.5-11.0 x 10^9/L',
                'normal_range_female' => '4.5-11.0 x 10^9/L',
                'unit' => 'x 10^9/L',
                'is_active' => true
            ],
            
            // Biochemistry
            [
                'name' => 'Blood Glucose (Fasting)',
                'category' => 'Biochemistry',
                'description' => 'Fasting blood glucose level',
                'normal_range_male' => '70-100 mg/dL',
                'normal_range_female' => '70-100 mg/dL',
                'unit' => 'mg/dL',
                'is_active' => true
            ],
            [
                'name' => 'Total Cholesterol',
                'category' => 'Biochemistry',
                'description' => 'Total cholesterol level',
                'normal_range_male' => '<200 mg/dL',
                'normal_range_female' => '<200 mg/dL',
                'unit' => 'mg/dL',
                'is_active' => true
            ],
            [
                'name' => 'Creatinine',
                'category' => 'Biochemistry',
                'description' => 'Serum creatinine level',
                'normal_range_male' => '0.7-1.3 mg/dL',
                'normal_range_female' => '0.6-1.1 mg/dL',
                'unit' => 'mg/dL',
                'is_active' => true
            ],
            
            // Microbiology
            [
                'name' => 'Malaria Parasite',
                'category' => 'Microbiology',
                'description' => 'Malaria parasite detection',
                'normal_range_male' => 'Negative',
                'normal_range_female' => 'Negative',
                'unit' => 'Negative/Positive',
                'is_active' => true
            ],
            [
                'name' => 'Urine Culture',
                'category' => 'Microbiology',
                'description' => 'Bacterial culture of urine',
                'normal_range_male' => 'No growth',
                'normal_range_female' => 'No growth',
                'unit' => 'CFU/mL',
                'is_active' => true
            ]
        ];

        foreach ($testTypes as $testType) {
            LabTestType::firstOrCreate(
                ['name' => $testType['name']],
                $testType
            );
        }
    }

    private function createSurgeryProcedures()
    {
        $procedures = [
            [
                'name' => 'Appendectomy',
                'description' => 'Surgical removal of the appendix',
                'procedure_type' => 'major',
                'category' => 'General Surgery',
                'duration_minutes' => 60,
                'anesthesia_type' => 'general',
                'complexity_level' => 2,
                'is_active' => true
            ],
            [
                'name' => 'Cholecystectomy',
                'description' => 'Surgical removal of the gallbladder',
                'procedure_type' => 'major',
                'category' => 'General Surgery',
                'duration_minutes' => 90,
                'anesthesia_type' => 'general',
                'complexity_level' => 3,
                'is_active' => true
            ],
            [
                'name' => 'Cataract Surgery',
                'description' => 'Surgical removal of cataract',
                'procedure_type' => 'minor',
                'category' => 'Ophthalmology',
                'duration_minutes' => 45,
                'anesthesia_type' => 'local',
                'complexity_level' => 2,
                'is_active' => true
            ],
            [
                'name' => 'Hernia Repair',
                'description' => 'Surgical repair of hernia',
                'procedure_type' => 'major',
                'category' => 'General Surgery',
                'duration_minutes' => 75,
                'anesthesia_type' => 'general',
                'complexity_level' => 2,
                'is_active' => true
            ]
        ];

        foreach ($procedures as $procedure) {
            SurgeryProcedure::firstOrCreate(
                ['name' => $procedure['name']],
                $procedure
            );
        }
    }

    private function createTheatres()
    {
        $branches = Branch::all();
        
        foreach ($branches as $branch) {
            $theatres = [
                [
                    'name' => 'Theatre 1',
                    'description' => 'Main operating theatre',
                    'capacity' => 1,
                    'is_active' => true
                ],
                [
                    'name' => 'Theatre 2',
                    'description' => 'Secondary operating theatre',
                    'capacity' => 1,
                    'is_active' => true
                ],
                [
                    'name' => 'Emergency Theatre',
                    'description' => 'Emergency surgery theatre',
                    'capacity' => 1,
                    'is_active' => true
                ]
            ];

            foreach ($theatres as $theatreData) {
                Theatre::firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'name' => $theatreData['name']
                    ],
                    array_merge($theatreData, ['branch_id' => $branch->id])
                );
            }
        }
    }

    private function createWards()
    {
        $branches = Branch::all();
        
        foreach ($branches as $branch) {
            $wards = [
                [
                    'name' => 'General Ward',
                    'type' => 'general',
                    'total_beds' => 20,
                    'is_active' => true
                ],
                [
                    'name' => 'ICU',
                    'type' => 'icu',
                    'total_beds' => 8,
                    'is_active' => true
                ],
                [
                    'name' => 'Pediatric Ward',
                    'type' => 'pediatric',
                    'total_beds' => 15,
                    'is_active' => true
                ],
                [
                    'name' => 'Maternity Ward',
                    'type' => 'maternity',
                    'total_beds' => 12,
                    'is_active' => true
                ]
            ];

            foreach ($wards as $wardData) {
                Ward::firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'name' => $wardData['name']
                    ],
                    array_merge($wardData, ['branch_id' => $branch->id])
                );
            }
        }
    }

    private function createDrugs()
    {
        $drugs = [
            [
                'name' => 'Paracetamol',
                'generic_name' => 'Acetaminophen',
                'drug_code' => 'PAR500',
                'category' => 'Analgesic',
                'dosage_form' => 'Tablet',
                'strength' => '500mg',
                'unit' => 'tablet',
                'manufacturer' => 'Generic Pharma Ltd',
                'description' => 'Pain reliever and fever reducer',
                'indications' => 'Headache, fever, mild to moderate pain',
                'contraindications' => 'Severe liver disease',
                'side_effects' => 'Rare allergic reactions',
                'dosage_instructions' => 'Take 1-2 tablets every 4-6 hours as needed',
                'storage_conditions' => 'Store at room temperature',
                'prescription_required' => false,
                'controlled_substance' => false,
                'nhis_covered' => true,
                'cost_price' => 0.50,
                'selling_price' => 1.00,
                'nhis_price' => 0.80,
                'is_active' => true,
                'created_by' => 1
            ],
            [
                'name' => 'Amoxicillin',
                'generic_name' => 'Amoxicillin',
                'drug_code' => 'AMX250',
                'category' => 'Antibiotic',
                'dosage_form' => 'Capsule',
                'strength' => '250mg',
                'unit' => 'capsule',
                'manufacturer' => 'Antibio Pharma',
                'description' => 'Broad-spectrum antibiotic',
                'indications' => 'Bacterial infections, respiratory tract infections',
                'contraindications' => 'Penicillin allergy',
                'side_effects' => 'Nausea, diarrhea, allergic reactions',
                'dosage_instructions' => 'Take 1 capsule every 8 hours for 7 days',
                'storage_conditions' => 'Store in cool, dry place',
                'prescription_required' => true,
                'controlled_substance' => false,
                'nhis_covered' => true,
                'cost_price' => 2.00,
                'selling_price' => 4.00,
                'nhis_price' => 3.20,
                'is_active' => true,
                'created_by' => 1
            ],
            [
                'name' => 'Ibuprofen',
                'generic_name' => 'Ibuprofen',
                'drug_code' => 'IBU400',
                'category' => 'NSAID',
                'dosage_form' => 'Tablet',
                'strength' => '400mg',
                'unit' => 'tablet',
                'manufacturer' => 'Pain Relief Inc',
                'description' => 'Anti-inflammatory pain reliever',
                'indications' => 'Arthritis, muscle pain, inflammation',
                'contraindications' => 'Stomach ulcers, severe heart failure',
                'side_effects' => 'Stomach upset, dizziness',
                'dosage_instructions' => 'Take 1 tablet every 6-8 hours with food',
                'storage_conditions' => 'Store at room temperature',
                'prescription_required' => false,
                'controlled_substance' => false,
                'nhis_covered' => true,
                'cost_price' => 1.50,
                'selling_price' => 3.00,
                'nhis_price' => 2.40,
                'is_active' => true,
                'created_by' => 1
            ],
            [
                'name' => 'Insulin',
                'generic_name' => 'Human Insulin',
                'drug_code' => 'INS100',
                'category' => 'Antidiabetic',
                'dosage_form' => 'Injection',
                'strength' => '100 units/mL',
                'unit' => 'vial',
                'manufacturer' => 'Diabetes Care Ltd',
                'description' => 'Blood sugar control medication',
                'indications' => 'Type 1 and Type 2 diabetes',
                'contraindications' => 'Hypoglycemia, insulin allergy',
                'side_effects' => 'Hypoglycemia, injection site reactions',
                'dosage_instructions' => 'As prescribed by physician',
                'storage_conditions' => 'Refrigerate, do not freeze',
                'prescription_required' => true,
                'controlled_substance' => false,
                'nhis_covered' => true,
                'cost_price' => 25.00,
                'selling_price' => 50.00,
                'nhis_price' => 40.00,
                'is_active' => true,
                'created_by' => 1
            ]
        ];

        foreach ($drugs as $drug) {
            Drug::firstOrCreate(
                ['drug_code' => $drug['drug_code']],
                $drug
            );
        }
    }

    private function createInsuranceProviders()
    {
        $providers = [
            [
                'name' => 'National Health Insurance Scheme',
                'code' => 'NHIS',
                'contact_info' => 'info@nhis.gov.gh',
                'is_active' => true
            ],
            [
                'name' => 'Axa Health Insurance',
                'code' => 'AXA',
                'contact_info' => 'info@axa.com.gh',
                'is_active' => true
            ],
            [
                'name' => 'Metropolitan Health Insurance',
                'code' => 'MET',
                'contact_info' => 'info@metropolitan.com.gh',
                'is_active' => true
            ]
        ];

        foreach ($providers as $provider) {
            InsuranceProvider::firstOrCreate(
                ['code' => $provider['code']],
                $provider
            );
        }
    }
}
