<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Branch;
use App\Models\User;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Drug;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\LabRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\StoreOrder;
use App\Models\Scan;
use Spatie\Permission\Models\Role;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Starting sample data seeding...');
        // Skip creating branches as they already exist
        // $this->createBranches();
        // Skip creating users as they already exist
        // $this->createUsers();
        $this->createPatients();
        // Skip creating drugs as they already exist
        // $this->createDrugs();
        // Skip creating wards as they already exist
        // $this->createWards();
        $this->createBeds();
        $this->createAppointments();
        $this->createConsultations();
        // Skip creating lab requests as they already exist
        // $this->createLabRequests();
        $this->createPrescriptions();
        $this->createInvoices();
        $this->createPayments();
        // Skip creating store orders as they already exist
        // $this->createStoreOrders();
        $this->createScans();
        $this->command->info('✅ Sample data seeding completed successfully!');
    }

    private function createBranches()
    {
        $this->command->info('🏥 Creating branches...');
        $branches = [
            ['name' => 'Main Hospital', 'code' => 'MAIN', 'address' => '123 Hospital Road, Accra', 'phone' => '+233 30 123 4567', 'email' => 'main@nexthospital.com', 'is_active' => true],
            ['name' => 'East Legon Branch', 'code' => 'EAST', 'address' => '456 East Legon Avenue, Accra', 'phone' => '+233 30 234 5678', 'email' => 'eastlegon@nexthospital.com', 'is_active' => true],
            ['name' => 'Tema Branch', 'code' => 'TEMA', 'address' => '789 Tema Industrial Area, Tema', 'phone' => '+233 30 345 6789', 'email' => 'tema@nexthospital.com', 'is_active' => true],
        ];
        foreach ($branches as $branchData) {
            Branch::create($branchData);
        }
        $this->command->info('✅ Created ' . count($branches) . ' branches');
    }

    private function createUsers()
    {
        $this->command->info('👥 Creating users...');
        $staffUsers = [
            ['name' => 'Dr. Kwame Asante', 'first_name' => 'Dr. Kwame', 'last_name' => 'Asante', 'email' => 'dr.asante@nexthospital.com', 'phone' => '+233 24 111 1111', 'password' => Hash::make('password'), 'is_active' => true, 'roles' => ['doctor']],
            ['name' => 'Dr. Ama Osei', 'first_name' => 'Dr. Ama', 'last_name' => 'Osei', 'email' => 'dr.osei@nexthospital.com', 'phone' => '+233 24 222 2222', 'password' => Hash::make('password'), 'is_active' => true, 'roles' => ['doctor']],
            ['name' => 'Nurse Akosua', 'first_name' => 'Nurse', 'last_name' => 'Akosua', 'email' => 'nurse.akosua@nexthospital.com', 'phone' => '+233 24 333 3333', 'password' => Hash::make('password'), 'is_active' => true, 'roles' => ['nurse']],
            ['name' => 'Pharmacist Kofi', 'first_name' => 'Pharmacist', 'last_name' => 'Kofi', 'email' => 'pharmacist.kofi@nexthospital.com', 'phone' => '+233 24 444 4444', 'password' => Hash::make('password'), 'is_active' => true, 'roles' => ['pharmacist']],
            ['name' => 'Lab Technician', 'first_name' => 'Lab', 'last_name' => 'Technician', 'email' => 'lab.tech@nexthospital.com', 'phone' => '+233 24 555 5555', 'password' => Hash::make('password'), 'is_active' => true, 'roles' => ['lab_technician']],
            ['name' => 'Receptionist Adwoa', 'first_name' => 'Receptionist', 'last_name' => 'Adwoa', 'email' => 'reception@nexthospital.com', 'phone' => '+233 24 666 6666', 'password' => Hash::make('password'), 'is_active' => true, 'roles' => ['receptionist']],
            ['name' => 'Admin User', 'first_name' => 'Admin', 'last_name' => 'User', 'email' => 'admin@nexthospital.com', 'phone' => '+233 24 777 7777', 'password' => Hash::make('password'), 'is_active' => true, 'roles' => ['admin']],
        ];
        foreach ($staffUsers as $userData) {
            $roles = $userData['roles'];
            unset($userData['roles']);
            $user = User::create($userData);
            $user->assignRole($roles);
        }
        $patientUsers = [
            ['name' => 'John Doe', 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john.doe@email.com', 'phone' => '+233 24 888 8888', 'password' => Hash::make('password'), 'is_active' => true, 'roles' => ['patient']],
            ['name' => 'Jane Smith', 'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane.smith@email.com', 'phone' => '+233 24 999 9999', 'password' => Hash::make('password'), 'is_active' => true, 'roles' => ['patient']],
            ['name' => 'Kwame Mensah', 'first_name' => 'Kwame', 'last_name' => 'Mensah', 'email' => 'kwame.mensah@email.com', 'phone' => '+233 24 000 0000', 'password' => Hash::make('password'), 'is_active' => true, 'roles' => ['patient']],
        ];
        foreach ($patientUsers as $userData) {
            $roles = $userData['roles'];
            unset($userData['roles']);
            $user = User::create($userData);
            $user->assignRole($roles);
        }
        $this->command->info('✅ Created ' . (count($staffUsers) + count($patientUsers)) . ' users');
    }

    private function createPatients()
    {
        $this->command->info('🏥 Creating patients...');
        
        // Create patient users first if they don't exist
        $patientEmails = ['john.doe@email.com', 'jane.smith@email.com', 'kwame.mensah@email.com'];
        $patientUsers = [];
        
        foreach ($patientEmails as $email) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $user = User::create([
                    'name' => 'Patient User',
                    'first_name' => 'Patient',
                    'last_name' => 'User',
                    'email' => $email,
                    'phone' => '+233 24 000 0000',
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]);
                $user->assignRole('patient');
            }
            $patientUsers[] = $user;
        }
        
        $patients = [
            ['user_id' => $patientUsers[0]->id, 'first_name' => 'John', 'last_name' => 'Doe', 'gender' => 'Male', 'date_of_birth' => '1985-06-15', 'phone' => '+233 24 888 8888', 'email' => 'john.doe@email.com', 'address' => '123 Main Street, Accra', 'nhis_number' => 'NHIS123456789', 'branch_id' => 1, 'created_by' => 1],
            ['user_id' => $patientUsers[1]->id, 'first_name' => 'Jane', 'last_name' => 'Smith', 'gender' => 'Female', 'date_of_birth' => '1990-03-22', 'phone' => '+233 24 999 9999', 'email' => 'jane.smith@email.com', 'address' => '456 Oak Avenue, Accra', 'nhis_number' => 'NHIS987654321', 'branch_id' => 1, 'created_by' => 1],
            ['user_id' => $patientUsers[2]->id, 'first_name' => 'Kwame', 'last_name' => 'Mensah', 'gender' => 'Male', 'date_of_birth' => '1978-12-10', 'phone' => '+233 24 000 0000', 'email' => 'kwame.mensah@email.com', 'address' => '789 Pine Street, Tema', 'nhis_number' => 'NHIS456789123', 'branch_id' => 2, 'created_by' => 1],
        ];
        foreach ($patients as $patientData) {
            Patient::create($patientData);
        }
        $this->command->info('✅ Created ' . count($patients) . ' patients');
    }

    private function createDrugs()
    {
        $this->command->info('💊 Creating drugs...');
        $drugs = [
            ['name' => 'Paracetamol 500mg', 'generic_name' => 'Acetaminophen', 'drug_code' => 'PAR500', 'category' => 'Analgesic', 'dosage_form' => 'Tablet', 'strength' => '500mg', 'unit' => 'tablet', 'unit_price' => 2.50, 'cost_price' => 2.00, 'selling_price' => 2.50, 'nhis_price' => 2.00, 'is_active' => true, 'created_by' => 1],
            ['name' => 'Amoxicillin 250mg', 'generic_name' => 'Amoxicillin', 'drug_code' => 'AMO250', 'category' => 'Antibiotic', 'dosage_form' => 'Capsule', 'strength' => '250mg', 'unit' => 'capsule', 'unit_price' => 5.00, 'cost_price' => 4.00, 'selling_price' => 5.00, 'nhis_price' => 4.50, 'is_active' => true, 'created_by' => 1],
            ['name' => 'Insulin Glargine', 'generic_name' => 'Insulin Glargine', 'drug_code' => 'INS100', 'category' => 'Antidiabetic', 'dosage_form' => 'Injection', 'strength' => '100 units/ml', 'unit' => 'vial', 'unit_price' => 45.00, 'cost_price' => 40.00, 'selling_price' => 45.00, 'nhis_price' => 42.00, 'is_active' => true, 'created_by' => 1],
        ];
        foreach ($drugs as $drugData) {
            Drug::create($drugData);
        }
        $this->command->info('✅ Created ' . count($drugs) . ' drugs');
    }

    private function createWards()
    {
        $this->command->info('🏥 Creating wards...');
        $wards = [
            ['name' => 'General Ward A', 'code' => 'GWA', 'type' => 'general', 'total_beds' => 20, 'available_beds' => 20, 'description' => 'General medical ward for adult patients', 'branch_id' => 1, 'is_active' => true],
            ['name' => 'Pediatric Ward', 'code' => 'PED', 'type' => 'pediatric', 'total_beds' => 15, 'available_beds' => 15, 'description' => 'Specialized ward for children', 'branch_id' => 1, 'is_active' => true],
            ['name' => 'ICU', 'code' => 'ICU', 'type' => 'icu', 'total_beds' => 8, 'available_beds' => 8, 'description' => 'Intensive Care Unit for critical patients', 'branch_id' => 1, 'is_active' => true],
        ];
        foreach ($wards as $wardData) {
            Ward::create($wardData);
        }
        $this->command->info('✅ Created ' . count($wards) . ' wards');
    }

    private function createBeds()
    {
        $this->command->info('🛏️ Creating beds...');
        $wards = Ward::all();
        $bedCount = 0;
        foreach ($wards as $ward) {
            for ($i = 1; $i <= $ward->capacity; $i++) {
                Bed::create(['ward_id' => $ward->id, 'bed_number' => $i, 'is_occupied' => false, 'is_active' => true]);
                $bedCount++;
            }
        }
        $this->command->info('✅ Created ' . $bedCount . ' beds');
    }

    private function createAppointments()
    {
        $this->command->info('📅 Creating appointments...');
        $doctors = User::role('doctor')->get();
        $patients = Patient::all();
        $appointments = [
            ['patient_id' => $patients[0]->id, 'doctor_id' => $doctors[0]->id, 'appointment_date' => now()->addDays(1), 'appointment_time' => '09:00:00', 'reason' => 'Regular checkup', 'status' => 'scheduled', 'appointment_type' => 'in-person', 'branch_id' => 1, 'created_by' => 1],
            ['patient_id' => $patients[1]->id, 'doctor_id' => $doctors[1]->id, 'appointment_date' => now()->addDays(2), 'appointment_time' => '10:30:00', 'reason' => 'Follow-up consultation', 'status' => 'scheduled', 'appointment_type' => 'in-person', 'branch_id' => 1, 'created_by' => 1],
            ['patient_id' => $patients[2]->id, 'doctor_id' => $doctors[0]->id, 'appointment_date' => now()->addDays(3), 'appointment_time' => '14:00:00', 'reason' => 'Teleconsultation', 'status' => 'scheduled', 'appointment_type' => 'teleconsultation', 'branch_id' => 2, 'created_by' => 1],
        ];
        foreach ($appointments as $appointmentData) {
            Appointment::create($appointmentData);
        }
        $this->command->info('✅ Created ' . count($appointments) . ' appointments');
    }

    private function createConsultations()
    {
        $this->command->info('🩺 Creating consultations...');
        $doctors = User::role('doctor')->get();
        $patients = Patient::all();
        $consultations = [
            ['patient_id' => $patients[0]->id, 'doctor_id' => $doctors[0]->id, 'consultation_date' => now()->subDays(1), 'consultation_time' => '09:00:00', 'consultation_type' => 'in-person', 'chief_complaint' => 'Headache and fever', 'history_of_present_illness' => 'Patient reports headache for 2 days with low-grade fever', 'consultation_status' => 'completed', 'branch_id' => 1, 'created_by' => 1],
            ['patient_id' => $patients[1]->id, 'doctor_id' => $doctors[1]->id, 'consultation_date' => now()->subDays(2), 'consultation_time' => '10:30:00', 'consultation_type' => 'in-person', 'chief_complaint' => 'Chest pain', 'history_of_present_illness' => 'Patient reports chest pain for 1 day', 'consultation_status' => 'completed', 'branch_id' => 1, 'created_by' => 1],
        ];
        foreach ($consultations as $consultationData) {
            Consultation::create($consultationData);
        }
        $this->command->info('✅ Created ' . count($consultations) . ' consultations');
    }

    private function createLabRequests()
    {
        $this->command->info('🧪 Creating lab requests...');
        $doctors = User::role('doctor')->get();
        $patients = Patient::all();
        $consultations = Consultation::all();
        $labRequests = [
            ['patient_id' => $patients[0]->id, 'consultation_id' => $consultations[0]->id, 'doctor_id' => $doctors[0]->id, 'branch_id' => 1, 'request_number' => 'REQ001', 'test_type' => 'Blood Test', 'test_description' => 'Complete Blood Count (CBC)', 'priority' => 'routine', 'status' => 'pending'],
            ['patient_id' => $patients[1]->id, 'consultation_id' => $consultations[1]->id, 'doctor_id' => $doctors[1]->id, 'branch_id' => 1, 'request_number' => 'REQ002', 'test_type' => 'Urine Test', 'test_description' => 'Urinalysis', 'priority' => 'routine', 'status' => 'completed'],
        ];
        foreach ($labRequests as $labRequestData) {
            LabRequest::create($labRequestData);
        }
        $this->command->info('✅ Created ' . count($labRequests) . ' lab requests');
    }

    private function createPrescriptions()
    {
        $this->command->info('💊 Creating prescriptions...');
        $doctors = User::role('doctor')->get();
        $patients = Patient::all();
        $consultations = Consultation::all();
        $prescriptions = [
            ['patient_id' => $patients[0]->id, 'consultation_id' => $consultations[0]->id, 'doctor_id' => $doctors[0]->id, 'branch_id' => 1, 'prescription_date' => now(), 'status' => 'active', 'notes' => 'Take with food', 'created_by' => 1],
            ['patient_id' => $patients[1]->id, 'consultation_id' => $consultations[1]->id, 'doctor_id' => $doctors[1]->id, 'branch_id' => 1, 'prescription_date' => now()->subDays(1), 'status' => 'completed', 'notes' => 'Complete full course', 'created_by' => 1],
        ];
        foreach ($prescriptions as $prescriptionData) {
            Prescription::create($prescriptionData);
        }
        $this->command->info('✅ Created ' . count($prescriptions) . ' prescriptions');
    }

    private function createInvoices()
    {
        $this->command->info('💰 Creating invoices...');
        $patients = Patient::all();
        $invoices = [
            ['patient_id' => $patients[0]->id, 'branch_id' => 1, 'invoice_date' => now(), 'subtotal' => 150.00, 'tax_amount' => 22.50, 'discount_amount' => 0.00, 'total_amount' => 172.50, 'status' => 'pending', 'payment_method' => 'cash', 'created_by' => 1],
            ['patient_id' => $patients[1]->id, 'branch_id' => 1, 'invoice_date' => now()->subDays(1), 'subtotal' => 200.00, 'tax_amount' => 30.00, 'discount_amount' => 0.00, 'total_amount' => 230.00, 'status' => 'paid', 'payment_method' => 'card', 'created_by' => 1],
        ];
        foreach ($invoices as $invoiceData) {
            Invoice::create($invoiceData);
        }
        $this->command->info('✅ Created ' . count($invoices) . ' invoices');
    }

    private function createPayments()
    {
        $this->command->info('💳 Creating payments...');
        $invoices = Invoice::all();
        $payments = [
            ['invoice_id' => $invoices[1]->id, 'amount' => 230.00, 'payment_method' => 'card', 'transaction_id' => 'TXN' . str_pad(rand(1, 9999), 6, '0', STR_PAD_LEFT), 'status' => 'completed', 'processed_by' => 1, 'processed_at' => now()->subDays(1)],
        ];
        foreach ($payments as $paymentData) {
            Payment::create($paymentData);
        }
        $this->command->info('✅ Created ' . count($payments) . ' payments');
    }

    private function createStoreOrders()
    {
        $this->command->info('🛒 Creating store orders...');
        $patients = Patient::all();
        $storeOrders = [
            ['patient_id' => $patients[0]->id, 'order_number' => 'ORD001', 'branch_id' => 1, 'order_date' => now(), 'subtotal' => 25.00, 'tax_amount' => 0.00, 'delivery_fee' => 0.00, 'total_amount' => 25.00, 'status' => 'pending', 'delivery_address' => '123 Main Street, Accra', 'delivery_method' => 'delivery', 'payment_method' => 'cash', 'created_by' => 1],
            ['patient_id' => $patients[1]->id, 'order_number' => 'ORD002', 'branch_id' => 1, 'order_date' => now()->subDays(1), 'subtotal' => 45.00, 'tax_amount' => 0.00, 'delivery_fee' => 0.00, 'total_amount' => 45.00, 'status' => 'delivered', 'delivery_address' => '456 Oak Avenue, Accra', 'delivery_method' => 'pickup', 'payment_method' => 'card', 'created_by' => 1],
        ];
        foreach ($storeOrders as $storeOrderData) {
            StoreOrder::create($storeOrderData);
        }
        $this->command->info('✅ Created ' . count($storeOrders) . ' store orders');
    }

    private function createScans()
    {
        $this->command->info('📸 Creating scans...');
        $doctors = User::role('doctor')->get();
        $patients = Patient::all();
        $consultations = Consultation::all();
        $scans = [
            ['patient_id' => $patients[0]->id, 'consultation_id' => $consultations[0]->id, 'doctor_id' => $doctors[0]->id, 'branch_id' => 1, 'scan_type' => 'X-Ray', 'scan_description' => 'Chest X-Ray', 'priority' => 'routine', 'status' => 'completed', 'report' => 'No abnormalities detected'],
            ['patient_id' => $patients[1]->id, 'consultation_id' => $consultations[1]->id, 'doctor_id' => $doctors[1]->id, 'branch_id' => 1, 'scan_type' => 'MRI', 'scan_description' => 'Brain MRI', 'priority' => 'routine', 'status' => 'pending', 'report' => 'Results pending'],
        ];
        foreach ($scans as $scanData) {
            Scan::create($scanData);
        }
        $this->command->info('✅ Created ' . count($scans) . ' scans');
    }
}
