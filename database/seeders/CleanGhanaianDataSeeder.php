<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Visit;
use App\Models\Consultation;
use App\Models\LabRequest;
use App\Models\LabResult;
use App\Models\Invoice;
use App\Models\Vital;
use App\Models\User;
use App\Models\Branch;
use App\Models\IdPrefixSetting;
use Faker\Factory as Faker;

class CleanGhanaianDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder matches the actual database structure exactly
     */
    public function run(): void
    {
        $faker = Faker::create('en_GH'); // Ghana-specific faker
        
        // Get existing users and branches
        $doctors = User::whereHas('roles', function($q) {
            $q->where('name', 'doctor');
        })->get();
        
        $nurses = User::whereHas('roles', function($q) {
            $q->where('name', 'nurse');
        })->get();
        
        $labTechnicians = User::whereHas('roles', function($q) {
            $q->where('name', 'lab_technician');
        })->get();
        
        $receptionists = User::whereHas('roles', function($q) {
            $q->where('name', 'receptionist');
        })->get();
        
        $branches = Branch::all();
        
        if ($doctors->isEmpty() || $nurses->isEmpty() || $labTechnicians->isEmpty() || $receptionists->isEmpty() || $branches->isEmpty()) {
            $this->command->error('Required users or branches not found. Please run the main seeder first.');
            return;
        }

        // Ghanaian names and locations
        $ghanaianFirstNames = [
            'Kwame', 'Kofi', 'Ama', 'Akosua', 'Yaw', 'Efua', 'Kojo', 'Adwoa', 'Kwaku', 'Abena',
            'Kwasi', 'Akua', 'Yaa', 'Fiifi', 'Esi', 'Kweku', 'Aba', 'Kofi', 'Ama', 'Kwabena',
            'Akosua', 'Yaw', 'Efua', 'Kojo', 'Adwoa', 'Kwaku', 'Abena', 'Kwasi', 'Akua', 'Yaa',
            'Fiifi', 'Esi', 'Kweku', 'Aba', 'Kofi', 'Ama', 'Kwabena', 'Akosua', 'Yaw', 'Efua'
        ];

        $ghanaianLastNames = [
            'Asante', 'Osei', 'Boateng', 'Mensah', 'Appiah', 'Darko', 'Owusu', 'Agyemang', 'Adjei', 'Sarpong',
            'Tetteh', 'Quaye', 'Amoah', 'Frimpong', 'Ofori', 'Acheampong', 'Antwi', 'Gyasi', 'Bonsu', 'Amoako',
            'Asiedu', 'Baffour', 'Danso', 'Essien', 'Fosu', 'Gyamfi', 'Hagan', 'Kwarteng', 'Larbi', 'Mintah',
            'Nkrumah', 'Opoku', 'Poku', 'Quansah', 'Rockson', 'Sackey', 'Tettey', 'Umar', 'Vandyck', 'Wiredu'
        ];

        $ghanaianCities = [
            'Accra', 'Kumasi', 'Tamale', 'Sekondi-Takoradi', 'Sunyani', 'Cape Coast', 'Koforidua', 'Techiman',
            'Ho', 'Wa', 'Bolgatanga', 'Kumasi', 'Tema', 'Ashaiman', 'Tema New Town', 'Madina', 'Adenta',
            'Kasoa', 'Tema', 'Nungua', 'Dansoman', 'Osu', 'Labadi', 'Teshie', 'Nungua', 'Tema', 'Ashaiman'
        ];

        $ghanaianRegions = [
            'Greater Accra', 'Ashanti', 'Northern', 'Western', 'Brong-Ahafo', 'Central', 'Eastern', 'Volta',
            'Upper East', 'Upper West', 'Western North', 'Ahafo', 'Bono', 'Bono East', 'Oti', 'Savannah',
            'North East', 'Western North'
        ];

        $commonDiseases = [
            'Malaria', 'Typhoid Fever', 'Upper Respiratory Tract Infection', 'Hypertension', 'Diabetes Mellitus',
            'Pneumonia', 'Gastroenteritis', 'Anemia', 'Urinary Tract Infection', 'Skin Infection',
            'Headache', 'Chest Pain', 'Abdominal Pain', 'Fever', 'Cough', 'Diarrhea', 'Vomiting',
            'Joint Pain', 'Back Pain', 'Eye Problem', 'Ear Problem', 'Dental Problem'
        ];

        $labTestTypes = [
            'Malaria Test', 'Typhoid Test', 'Blood Sugar', 'HIV Test', 'Hepatitis B Test', 'Hepatitis C Test',
            'Full Blood Count', 'Liver Function Test', 'Kidney Function Test', 'Lipid Profile', 'Thyroid Function Test',
            'Pregnancy Test', 'Urinalysis', 'Stool Analysis', 'Blood Group', 'Rhesus Factor', 'Sickle Cell Test'
        ];

        // Create ID prefix settings if they don't exist
        $this->createIdPrefixSettings();

        $this->command->info('Creating Ghanaian patients...');
        
        // Create patients (only 5 records)
        for ($i = 1; $i <= 5; $i++) {
            $firstName = $faker->randomElement($ghanaianFirstNames);
            $lastName = $faker->randomElement($ghanaianLastNames);
            $gender = $faker->randomElement(['Male', 'Female']);
            
            Patient::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'other_names' => $faker->optional(0.3)->randomElement($ghanaianFirstNames),
                'gender' => $gender,
                'date_of_birth' => $faker->dateTimeBetween('-80 years', '-18 years'),
                'phone' => $faker->phoneNumber,
                'email' => $faker->optional(0.7)->email,
                'address' => $faker->streetAddress . ', ' . $faker->randomElement($ghanaianCities) . ', ' . $faker->randomElement($ghanaianRegions),
                'nhis_number' => $faker->optional(0.6)->numerify('NHIS-####-####-####'),
                'ghana_card_number' => $faker->optional(0.8)->numerify('GHA-########-####-####'),
                'emergency_contact_name' => $faker->name,
                'emergency_contact_phone' => $faker->phoneNumber,
                'emergency_contact_relationship' => $faker->randomElement(['Father', 'Mother', 'Spouse', 'Sibling', 'Child', 'Friend']),
                'branch_id' => $faker->randomElement($branches)->id,
                'created_by' => $faker->randomElement($receptionists)->id,
            ]);
        }

        $patients = Patient::all();
        $this->command->info('Created ' . $patients->count() . ' patients');

        // Create visits
        $this->command->info('Creating visits...');
        foreach ($patients as $patient) {
            $visitCount = 1; // Only 1 visit per patient
            for ($j = 0; $j < $visitCount; $j++) {
                $visitDate = $faker->dateTimeBetween('-6 months', 'now');
                $doctor = $faker->randomElement($doctors);
                $nurse = $faker->randomElement($nurses);
                
                Visit::create([
                    'visit_token' => 'VST-' . time() . '-' . $patient->id . '-' . $j,
                    'patient_id' => $patient->id,
                    'visit_type' => $faker->randomElement(['OPD', 'IPD', 'Emergency', 'LabOnly', 'PharmacyOnly']),
                    'status' => $faker->randomElement(['active', 'completed', 'cancelled', 'transferred']),
                    'check_in_time' => $visitDate,
                    'check_out_time' => $faker->optional(0.7)->dateTimeBetween($visitDate, 'now'),
                    'assigned_doctor_id' => $doctor->id,
                    'assigned_nurse_id' => $nurse->id,
                    'chief_complaint' => $faker->randomElement($commonDiseases),
                    'visit_notes' => $faker->optional(0.5)->paragraph,
                    'vital_signs' => json_encode([
                        'blood_pressure' => $faker->numberBetween(90, 180) . '/' . $faker->numberBetween(60, 120),
                        'temperature' => $faker->randomFloat(1, 36.0, 39.5),
                        'pulse' => $faker->numberBetween(60, 120),
                        'weight' => $faker->numberBetween(45, 120),
                        'height' => $faker->numberBetween(150, 200)
                    ]),
                    'priority' => $faker->randomElement(['routine', 'urgent', 'critical']),
                    'referral_source' => $faker->optional(0.3)->randomElement(['Self', 'Other Hospital', 'Clinic', 'Emergency']),
                    'referral_notes' => $faker->optional(0.3)->sentence,
                    'branch_id' => $patient->branch_id,
                    'created_by' => $faker->randomElement($receptionists)->id,
                ]);
            }
        }

        $visits = Visit::all();
        $this->command->info('Created ' . $visits->count() . ' visits');

        // Create appointments
        $this->command->info('Creating appointments...');
        foreach ($patients as $patient) {
            $appointmentCount = 1; // Only 1 appointment per patient
            for ($j = 0; $j < $appointmentCount; $j++) {
                $appointmentDate = $faker->dateTimeBetween('-3 months', '+3 months');
                $doctor = $faker->randomElement($doctors);
                
                Appointment::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'branch_id' => $patient->branch_id,
                    'appointment_date' => $appointmentDate->format('Y-m-d'),
                    'appointment_time' => $appointmentDate->format('H:i:s'),
                    'reason' => $faker->randomElement($commonDiseases),
                    'status' => $faker->randomElement(['scheduled', 'completed', 'cancelled', 'no-show']),
                    'appointment_type' => $faker->randomElement(['in-person', 'teleconsultation']),
                    'notes' => $faker->optional(0.4)->sentence,
                    'is_teleconsultation' => $faker->boolean(20),
                    'created_by' => $faker->randomElement($receptionists)->id,
                ]);
            }
        }

        $appointments = Appointment::all();
        $this->command->info('Created ' . $appointments->count() . ' appointments');

        // Create consultations
        $this->command->info('Creating consultations...');
        foreach ($patients as $patient) {
            $consultationCount = 1; // Only 1 consultation per patient
            $visits = Visit::where('patient_id', $patient->id)->get();
            
            for ($j = 0; $j < $consultationCount; $j++) {
                $consultationDate = $faker->dateTimeBetween('-6 months', 'now');
                $doctor = $faker->randomElement($doctors);
                $visit = $visits->random();
                
                Consultation::create([
                    'consultation_number' => 'CON-' . time() . '-' . $patient->id . '-' . $j,
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'branch_id' => $patient->branch_id,
                    'visit_id' => $visit ? $visit->id : null,
                    'consultation_date' => $consultationDate->format('Y-m-d'),
                    'consultation_time' => $consultationDate->format('H:i:s'),
                    'consultation_type' => $faker->randomElement(['in-person', 'teleconsultation']),
                    'chief_complaint' => $faker->randomElement($commonDiseases),
                    'presenting_complaints' => $faker->paragraph,
                    'history_of_present_illness' => $faker->paragraph,
                    'past_medical_history' => $faker->optional(0.6)->paragraph,
                    'family_history' => $faker->optional(0.4)->paragraph,
                    'social_history' => $faker->optional(0.5)->paragraph,
                    'drug_history' => $faker->optional(0.5)->paragraph,
                    'allergy_history' => $faker->optional(0.3)->paragraph,
                    'physical_examination' => $faker->optional(0.7)->paragraph,
                    'blood_pressure_systolic' => $faker->numberBetween(90, 180),
                    'blood_pressure_diastolic' => $faker->numberBetween(60, 120),
                    'pulse_rate' => $faker->numberBetween(60, 120),
                    'temperature' => $faker->randomFloat(2, 36.0, 39.5),
                    'respiratory_rate' => $faker->numberBetween(12, 25),
                    'oxygen_saturation' => $faker->numberBetween(95, 100),
                    'height' => $faker->numberBetween(150, 200),
                    'weight' => $faker->numberBetween(45, 120),
                    'bmi' => $faker->randomFloat(2, 18.5, 35.0),
                    'diagnoses' => $faker->randomElement($commonDiseases),
                    'treatment_plan' => $faker->paragraph,
                    'medications_prescribed' => $faker->optional(0.7)->paragraph,
                    'investigations_ordered' => $faker->optional(0.6)->paragraph,
                    'follow_up_instructions' => $faker->optional(0.5)->paragraph,
                    'clinical_notes' => $faker->optional(0.6)->paragraph,
                    'next_appointment_date' => $faker->optional(0.6)->dateTimeBetween('+1 week', '+1 month'),
                    'icd_10_code' => $faker->optional(0.7)->numerify('A##'),
                    'severity' => $faker->randomElement(['mild', 'moderate', 'severe']),
                    'urgency' => $faker->randomElement(['routine', 'urgent', 'critical']),
                    'consultation_status' => $faker->randomElement(['ongoing', 'completed', 'cancelled']),
                    'is_draft' => $faker->boolean(20),
                    'nhis_eligible' => $faker->boolean(60),
                    'requires_referral' => $faker->boolean(20),
                    'referral_notes' => $faker->optional(0.3)->paragraph,
                    'referral_specialty' => $faker->optional(0.3)->randomElement(['Cardiology', 'Neurology', 'Orthopedics', 'Dermatology']),
                    'started_at' => $consultationDate,
                    'completed_at' => $faker->optional(0.8)->dateTimeBetween($consultationDate, 'now'),
                    'created_by' => $doctor->id,
                ]);
            }
        }

        $consultations = Consultation::all();
        $this->command->info('Created ' . $consultations->count() . ' consultations');

        // Create lab requests
        $this->command->info('Creating lab requests...');
        foreach ($consultations as $consultation) {
            if ($faker->boolean(80)) { // 80% of consultations have lab requests
                $labTechnician = $faker->randomElement($labTechnicians);
                $requestDate = $faker->dateTimeBetween($consultation->created_at, 'now');
                
                $labRequest = LabRequest::create([
                    'lab_request_number' => 'LAB-' . time() . '-' . $consultation->id,
                    'patient_id' => $consultation->patient_id,
                    'consultation_id' => $consultation->id,
                    'doctor_id' => $consultation->doctor_id,
                    'branch_id' => $consultation->branch_id,
                    'request_number' => 'REQ-' . time() . '-' . $consultation->id,
                    'test_type' => $faker->randomElement($labTestTypes),
                    'test_description' => $faker->sentence,
                    'specimen_type' => $faker->randomElement(['Blood', 'Urine', 'Stool', 'Sputum', 'Swab']),
                    'clinical_notes' => $faker->optional(0.6)->paragraph,
                    'priority' => $faker->randomElement(['routine', 'urgent', 'stat']),
                    'status' => $faker->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
                    'overall_status' => $faker->randomElement(['pending', 'partial', 'completed', 'cancelled']),
                    'technician_id' => $labTechnician->id,
                    'collected_at' => $faker->optional(0.7)->dateTimeBetween($requestDate, 'now'),
                    'completed_at' => $faker->optional(0.6)->dateTimeBetween($requestDate, 'now'),
                    'created_by' => $consultation->doctor_id,
                ]);

                // Create lab results for completed requests
                if ($labRequest->status === 'completed' && $faker->boolean(80)) {
                    LabResult::create([
                        'lab_request_id' => $labRequest->id,
                        'test_name' => $labRequest->test_type,
                        'result_value' => $faker->randomFloat(2, 0, 100),
                        'reference_range' => $faker->randomElement(['0-10', '10-20', '20-30', '30-40', '40-50']),
                        'unit' => $faker->randomElement(['mg/dL', 'g/L', '%', 'cells/μL', 'IU/L']),
                        'result_status' => $faker->randomElement(['normal', 'abnormal', 'critical']),
                        'verified_by' => $labTechnician->id,
                        'verified_at' => $faker->dateTimeBetween($requestDate, 'now'),
                        'notes' => $faker->optional(0.4)->sentence,
                    ]);
                }
            }
        }

        $labRequests = LabRequest::all();
        $labResults = LabResult::all();
        $this->command->info('Created ' . $labRequests->count() . ' lab requests and ' . $labResults->count() . ' lab results');

        // Create invoices
        $this->command->info('Creating invoices...');
        foreach ($patients as $patient) {
            $invoiceCount = 1; // Only 1 invoice per patient
            for ($j = 0; $j < $invoiceCount; $j++) {
                $subtotal = $faker->randomFloat(2, 50, 500);
                $taxAmount = $subtotal * 0.15; // 15% VAT
                $discountAmount = $faker->randomFloat(2, 0, $subtotal * 0.1);
                $totalAmount = $subtotal + $taxAmount - $discountAmount;
                
                Invoice::create([
                    'patient_id' => $patient->id,
                    'branch_id' => $patient->branch_id,
                    'invoice_number' => 'INV-' . time() . '-' . $patient->id . '-' . $j,
                    'invoice_date' => $faker->dateTimeBetween('-2 months', 'now'),
                    'items' => json_encode([
                        [
                            'name' => $faker->randomElement(['Consultation', 'Lab Test', 'Medication', 'Treatment', 'Procedure']),
                            'description' => $faker->sentence,
                            'quantity' => 1,
                            'unit_price' => $subtotal,
                            'total' => $subtotal
                        ]
                    ]),
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'status' => $faker->randomElement(['draft', 'pending', 'paid', 'cancelled', 'refunded']),
                    'payment_method' => $faker->randomElement(['cash', 'card', 'momo', 'insurance', 'bank_transfer']),
                    'notes' => $faker->optional(0.4)->sentence,
                    'created_by' => $faker->randomElement($receptionists)->id,
                ]);
            }
        }

        $invoices = Invoice::all();
        $this->command->info('Created ' . $invoices->count() . ' invoices');

        // Create vitals
        $this->command->info('Creating vitals...');
        foreach ($consultations as $consultation) {
            if ($faker->boolean(100)) { // 100% of consultations have vitals
                $nurse = $faker->randomElement($nurses);
                $recordDate = $faker->dateTimeBetween($consultation->created_at, 'now');
                $weight = $faker->numberBetween(45, 120);
                $height = $faker->numberBetween(150, 200);
                $bmi = round($weight / (($height / 100) ** 2), 1);
                
                Vital::create([
                    'consultation_id' => $consultation->id,
                    'recorded_by' => $nurse->id,
                    'recorded_at' => $recordDate,
                    'blood_pressure_systolic' => $faker->numberBetween(90, 180),
                    'blood_pressure_diastolic' => $faker->numberBetween(60, 120),
                    'pulse_rate' => $faker->numberBetween(60, 120),
                    'respiratory_rate' => $faker->numberBetween(12, 25),
                    'temperature' => $faker->randomFloat(1, 36.0, 39.5),
                    'weight' => $weight,
                    'height' => $height,
                    'bmi' => $bmi,
                    'oxygen_saturation' => $faker->numberBetween(95, 100),
                ]);
            }
        }

        $vitals = Vital::all();
        $this->command->info('Created ' . $vitals->count() . ' vitals records');

        $this->command->info('Ghanaian data seeding completed successfully!');
        $this->command->info('Summary:');
        $this->command->info('- Patients: ' . $patients->count());
        $this->command->info('- Visits: ' . $visits->count());
        $this->command->info('- Appointments: ' . $appointments->count());
        $this->command->info('- Consultations: ' . $consultations->count());
        $this->command->info('- Lab Requests: ' . $labRequests->count());
        $this->command->info('- Lab Results: ' . $labResults->count());
        $this->command->info('- Invoices: ' . $invoices->count());
        $this->command->info('- Vitals: ' . $vitals->count());
    }

    private function createIdPrefixSettings()
    {
        $prefixes = [
            ['entity_type' => 'patient', 'prefix' => 'PAT', 'number_length' => 7, 'next_number' => 1],
            ['entity_type' => 'visit', 'prefix' => 'VST', 'number_length' => 7, 'next_number' => 1],
            ['entity_type' => 'appointment', 'prefix' => 'APT', 'number_length' => 7, 'next_number' => 1],
            ['entity_type' => 'consultation', 'prefix' => 'CON', 'number_length' => 7, 'next_number' => 1],
            ['entity_type' => 'labrequest', 'prefix' => 'LAB', 'number_length' => 7, 'next_number' => 1],
            ['entity_type' => 'labresult', 'prefix' => 'LRS', 'number_length' => 7, 'next_number' => 1],
            ['entity_type' => 'invoice', 'prefix' => 'INV', 'number_length' => 7, 'next_number' => 1],
        ];

        foreach ($prefixes as $prefix) {
            IdPrefixSetting::firstOrCreate(
                ['entity_type' => $prefix['entity_type']],
                $prefix
            );
        }
    }
}
