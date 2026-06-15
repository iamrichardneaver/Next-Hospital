<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\LabRequest;
use App\Models\Invoice;
use App\Models\Visit;
use App\Models\Prescription;
use App\Models\Drug;
use App\Models\LabTestType;
use App\Models\LabResult;
use App\Models\Vital;
use App\Models\User;
use App\Models\Branch;
use Carbon\Carbon;
use Faker\Factory as Faker;

class GhanaianDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
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
        
        $pharmacists = User::whereHas('roles', function($q) {
            $q->where('name', 'pharmacist');
        })->get();
        
        $receptionists = User::whereHas('roles', function($q) {
            $q->where('name', 'receptionist');
        })->get();
        
        $branches = Branch::all();
        
        // Ghanaian names and locations
        $ghanaianFirstNames = [
            'Kwame', 'Kofi', 'Ama', 'Akosua', 'Yaw', 'Adwoa', 'Kwaku', 'Efua',
            'Samuel', 'Grace', 'John', 'Mary', 'Joseph', 'Elizabeth', 'David', 'Sarah',
            'Emmanuel', 'Patience', 'Michael', 'Comfort', 'Daniel', 'Blessing', 'Peter', 'Gifty',
            'Isaac', 'Ruth', 'Solomon', 'Esther', 'Benjamin', 'Joyce', 'Francis', 'Peace',
            'Richard', 'Mercy', 'Stephen', 'Faith', 'Mark', 'Hope', 'Paul', 'Charity',
            'James', 'Princess', 'Thomas', 'Victoria', 'Andrew', 'Cynthia', 'Matthew', 'Gloria'
        ];
        
        $ghanaianLastNames = [
            'Asante', 'Osei', 'Mensah', 'Appiah', 'Owusu', 'Bonsu', 'Agyemang', 'Tetteh',
            'Adjei', 'Sarpong', 'Amoah', 'Darko', 'Boateng', 'Frimpong', 'Acheampong', 'Ofori',
            'Agyei', 'Antwi', 'Bediako', 'Gyasi', 'Kwarteng', 'Nkrumah', 'Opoku', 'Prempeh',
            'Sackey', 'Takyi', 'Wiredu', 'Yeboah', 'Adu', 'Baffour', 'Danso', 'Essien',
            'Fosu', 'Gyamfi', 'Hagan', 'Koomson', 'Larbi', 'Mintah', 'Ntim', 'Okyere'
        ];
        
        $ghanaianRegions = [
            'Greater Accra', 'Ashanti', 'Western', 'Central', 'Volta', 'Eastern',
            'Northern', 'Upper East', 'Upper West', 'Brong-Ahafo', 'Western North',
            'Ahafo', 'Bono', 'Bono East', 'Oti', 'Savannah', 'North East'
        ];
        
        $ghanaianCities = [
            'Accra', 'Kumasi', 'Tamale', 'Tema', 'Sekondi-Takoradi', 'Cape Coast',
            'Koforidua', 'Sunyani', 'Ho', 'Wa', 'Bolgatanga', 'Techiman',
            'Kasoa', 'Madina', 'Teshie', 'Nungua', 'Dansoman', 'Adenta',
            'Ashaiman', 'Tema New Town', 'Spintex', 'East Legon', 'Labone'
        ];
        
        $commonDiseases = [
            'Malaria', 'Typhoid Fever', 'Hypertension', 'Diabetes', 'Common Cold',
            'Pneumonia', 'Diarrhea', 'Headache', 'Fever', 'Cough', 'Chest Pain',
            'Stomach Pain', 'Back Pain', 'Joint Pain', 'Skin Rash', 'Eye Problem',
            'Ear Infection', 'Sore Throat', 'Nausea', 'Vomiting', 'Dizziness'
        ];
        
        $labTestTypes = [
            'Malaria Test', 'Typhoid Test', 'Blood Sugar', 'Blood Pressure',
            'Cholesterol', 'HIV Test', 'Hepatitis B', 'Full Blood Count',
            'Urinalysis', 'Stool Test', 'Pregnancy Test', 'Thyroid Function',
            'Liver Function', 'Kidney Function', 'X-Ray', 'Ultrasound'
        ];
        
        // Create 200 patients with Ghanaian data
        $this->command->info('Creating 200 Ghanaian patients...');
        for ($i = 0; $i < 200; $i++) {
            $firstName = $faker->randomElement($ghanaianFirstNames);
            $lastName = $faker->randomElement($ghanaianLastNames);
            $gender = $faker->randomElement(['Male', 'Female']);
            
            Patient::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'other_names' => $faker->optional(0.3)->firstName,
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
        
        // Create 500 appointments
        $this->command->info('Creating 500 appointments...');
        for ($i = 0; $i < 500; $i++) {
            $appointmentDate = $faker->dateTimeBetween('-6 months', '+3 months');
            $patient = $faker->randomElement($patients);
            $doctor = $faker->randomElement($doctors);
            
            Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'appointment_date' => $appointmentDate,
                'appointment_time' => $faker->time('H:i'),
                'reason' => $faker->randomElement($commonDiseases),
                'status' => $faker->randomElement(['scheduled', 'completed', 'cancelled', 'no-show']),
                'appointment_type' => $faker->randomElement(['in-person', 'teleconsultation']),
                'notes' => $faker->optional(0.5)->sentence,
                'branch_id' => $patient->branch_id,
                'created_by' => $faker->randomElement($receptionists)->id,
            ]);
        }
        
        // Create 300 visits (OPD/IPD)
        $this->command->info('Creating 300 visits...');
        for ($i = 0; $i < 300; $i++) {
            $patient = $faker->randomElement($patients);
            $visitDate = $faker->dateTimeBetween('-3 months', 'now');
            $doctor = $faker->randomElement($doctors);
            $nurse = $faker->randomElement($nurses);
            
            Visit::create([
                'visit_token' => 'VST-' . time() . '-' . $i,
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
        
        // Create 400 consultations
        $this->command->info('Creating 400 consultations...');
        for ($i = 0; $i < 400; $i++) {
            $patient = $faker->randomElement($patients);
            $doctor = $faker->randomElement($doctors);
            $consultationDate = $faker->dateTimeBetween('-3 months', 'now');
            $visit = $faker->optional(0.8)->randomElement(Visit::all());
            
            Consultation::create([
                'consultation_number' => 'CON-' . time() . '-' . $i,
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
        
        // Create 250 lab requests
        $this->command->info('Creating 250 lab requests...');
        $consultations = Consultation::all();
        for ($i = 0; $i < 250; $i++) {
            $patient = $faker->randomElement($patients);
            $doctor = $faker->randomElement($doctors);
            $labTechnician = $faker->randomElement($labTechnicians);
            $consultation = $faker->randomElement($consultations);
            $requestDate = $faker->dateTimeBetween('-2 months', 'now');
            
            $labRequest = LabRequest::create([
                'patient_id' => $patient->id,
                'consultation_id' => $consultation->id,
                'doctor_id' => $doctor->id,
                'technician_id' => $labTechnician->id,
                'test_type' => $faker->randomElement($labTestTypes),
                'test_description' => $faker->sentence,
                'specimen_type' => $faker->randomElement(['Blood', 'Urine', 'Stool', 'Sputum', 'Swab']),
                'clinical_notes' => $faker->optional(0.6)->paragraph,
                'priority' => $faker->randomElement(['routine', 'urgent', 'stat']),
                'status' => $faker->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
                'overall_status' => $faker->randomElement(['pending', 'partial', 'completed', 'cancelled']),
                'collected_at' => $faker->optional(0.7)->dateTimeBetween($requestDate, 'now'),
                'completed_at' => $faker->optional(0.6)->dateTimeBetween($requestDate, 'now'),
                'branch_id' => $patient->branch_id,
                'created_by' => $doctor->id,
            ]);
            
            // Create lab results for completed requests
            if ($labRequest->status === 'completed') {
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
        
        // Create 200 prescriptions
        $this->command->info('Creating 200 prescriptions...');
        $drugs = Drug::all();
        if ($drugs->count() > 0) {
            for ($i = 0; $i < 200; $i++) {
                $patient = $faker->randomElement($patients);
                $doctor = $faker->randomElement($doctors);
                $pharmacist = $faker->randomElement($pharmacists);
                
                Prescription::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'pharmacist_id' => $pharmacist->id,
                    'prescription_date' => $faker->dateTimeBetween('-2 months', 'now'),
                    'medication_name' => $faker->randomElement($drugs)->name,
                    'dosage' => $faker->randomElement(['1 tablet', '2 tablets', '1 capsule', '5ml', '10ml']),
                    'frequency' => $faker->randomElement(['Once daily', 'Twice daily', 'Three times daily', 'As needed']),
                    'duration' => $faker->randomElement(['3 days', '1 week', '2 weeks', '1 month']),
                    'instructions' => $faker->sentence,
                    'status' => $faker->randomElement(['pending', 'dispensed', 'completed']),
                    'branch_id' => $patient->branch_id,
                    'created_by' => $doctor->id,
                ]);
            }
        }
        
        // Create 300 invoices
        $this->command->info('Creating 300 invoices...');
        for ($i = 0; $i < 300; $i++) {
            $patient = $faker->randomElement($patients);
            $subtotal = $faker->randomFloat(2, 50, 2000);
            $taxAmount = $subtotal * 0.15; // 15% VAT
            $totalAmount = $subtotal + $taxAmount;
            
            Invoice::create([
                    'patient_id' => $patient->id,
                'invoice_number' => 'INV-' . time() . '-' . $i,
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
                'discount_amount' => $faker->randomFloat(2, 0, $subtotal * 0.1),
                'total_amount' => $totalAmount,
                'status' => $faker->randomElement(['draft', 'pending', 'paid', 'cancelled', 'refunded']),
                'payment_method' => $faker->randomElement(['cash', 'card', 'momo', 'insurance', 'bank_transfer']),
                'notes' => $faker->optional(0.4)->sentence,
                    'branch_id' => $patient->branch_id,
                'created_by' => $faker->randomElement($receptionists)->id,
            ]);
        }
        
        // Create 150 vital records
        $this->command->info('Creating 150 vital records...');
        $consultations = Consultation::all();
        for ($i = 0; $i < 150; $i++) {
            $consultation = $faker->randomElement($consultations);
            $nurse = $faker->randomElement($nurses);
            $recordDate = $faker->dateTimeBetween('-1 month', 'now');
            $weight = $faker->numberBetween(45, 120);
            $height = $faker->numberBetween(150, 200);
            $bmi = $weight / (($height / 100) ** 2);
            
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
                'bmi' => round($bmi, 1),
                'oxygen_saturation' => $faker->numberBetween(95, 100),
            ]);
        }
        
        $this->command->info('Ghanaian data population completed successfully!');
        $this->command->info('Summary:');
        $this->command->info('- Patients: ' . Patient::count());
        $this->command->info('- Appointments: ' . Appointment::count());
        $this->command->info('- Visits: ' . Visit::count());
        $this->command->info('- Consultations: ' . Consultation::count());
        $this->command->info('- Lab Requests: ' . LabRequest::count());
        $this->command->info('- Lab Results: ' . LabResult::count());
        $this->command->info('- Prescriptions: ' . Prescription::count());
        $this->command->info('- Invoices: ' . Invoice::count());
        $this->command->info('- Vital Records: ' . Vital::count());
    }
}