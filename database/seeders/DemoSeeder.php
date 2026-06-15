<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Consultation;
use App\Models\Drug;
use App\Models\Invoice;
use App\Models\LabRequest;
use App\Models\LabTestResult;
use App\Models\OrderItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\StoreItem;
use App\Models\StoreOrder;
use App\Models\User;
use App\Models\Visit;
use App\Models\Vital;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('en_GH');

        $branch = Branch::where('code', 'MAIN')->first() ?? Branch::first();
        if (!$branch) {
            $this->command?->error('No branches found. Demo seeding cannot continue.');
            return;
        }

        $superAdmin = User::where('email', 'admin@nexthospital.com')->first();
        $doctor = User::where('email', 'doctor@nexthospital.com')->first();
        $nurse = User::where('email', 'nurse@nexthospital.com')->first();
        $pharmacist = User::where('email', 'pharmacist@nexthospital.com')->first();
        $receptionist = User::where('email', 'receptionist@nexthospital.com')->first();
        $labTech = User::where('email', 'lab@nexthospital.com')->first();

        if (!$doctor || !$nurse || !$receptionist || !$labTech || !$pharmacist || !$superAdmin) {
            $this->command?->error('Missing demo staff users. Ensure UserSeeder ran successfully.');
            return;
        }

        $createdBy = $superAdmin->id;

        $this->seedDrugsAndStoreItems($faker, $createdBy);

        $patients = collect();
        $this->command?->info('Creating demo patients...');
        for ($i = 0; $i < 30; $i++) {
            $gender = $faker->randomElement(['Male', 'Female']);
            $firstName = $faker->firstName($gender === 'Male' ? 'male' : 'female');
            $lastName = $faker->lastName();

            $patients->push(Patient::create([
                'first_name' => $firstName,
                'other_names' => $faker->optional(0.3)->firstName(),
                'last_name' => $lastName,
                'gender' => $gender,
                'date_of_birth' => $faker->dateTimeBetween('-75 years', '-1 years'),
                'phone' => $faker->phoneNumber,
                'email' => $faker->optional(0.7)->safeEmail,
                'address' => $faker->streetAddress . ', ' . $faker->city,
                'nhis_number' => $faker->optional(0.5)->numerify('NHIS-####-####-####'),
                'ghana_card_number' => $faker->optional(0.7)->numerify('GHA-########-####-####'),
                'emergency_contact_name' => $faker->name,
                'emergency_contact_phone' => $faker->phoneNumber,
                'emergency_contact_relationship' => $faker->randomElement(['Father', 'Mother', 'Spouse', 'Sibling', 'Child', 'Friend']),
                'branch_id' => $branch->id,
                'registration_source' => 'web',
                'created_by' => $receptionist->id,
            ]));
        }

        $this->seedVisitsConsultationsVitalsLabsBillingAndPharmacy($faker, $patients, $branch, $doctor, $nurse, $labTech, $pharmacist, $receptionist, $createdBy);
        $this->seedDoctorQueueDemo($faker, $patients, $branch, $doctor, $receptionist);
        $this->seedAppointments($faker, $patients, $branch, $doctor, $receptionist);
        $this->seedStoreOrders($faker, $patients, $branch, $createdBy);
    }

    private function seedDrugsAndStoreItems($faker, int $createdBy): void
    {
        if (Drug::count() > 0) {
            return;
        }

        $this->command?->info('Creating demo drugs + store items...');

        $drugCatalog = [
            ['Paracetamol 500mg', 'Acetaminophen', 'Analgesic', 'Tablet', '500mg', false],
            ['Ibuprofen 400mg', 'Ibuprofen', 'NSAID', 'Tablet', '400mg', false],
            ['Amoxicillin 500mg', 'Amoxicillin', 'Antibiotic', 'Capsule', '500mg', true],
            ['Ciprofloxacin 500mg', 'Ciprofloxacin', 'Antibiotic', 'Tablet', '500mg', true],
            ['Loratadine 10mg', 'Loratadine', 'Antihistamine', 'Tablet', '10mg', false],
            ['Omeprazole 20mg', 'Omeprazole', 'Gastrointestinal', 'Capsule', '20mg', false],
            ['Metformin 500mg', 'Metformin', 'Antidiabetic', 'Tablet', '500mg', true],
            ['Amlodipine 5mg', 'Amlodipine', 'Antihypertensive', 'Tablet', '5mg', true],
            ['Salbutamol Inhaler', 'Salbutamol', 'Respiratory', 'Inhaler', '100mcg', true],
            ['ORS Sachet', 'Oral Rehydration Salts', 'Rehydration', 'Sachet', '1 sachet', false],
        ];

        foreach ($drugCatalog as $idx => $d) {
            [$name, $generic, $category, $form, $strength, $rx] = $d;
            $cost = $faker->randomFloat(2, 1, 20);
            $sell = (float) ($cost * $faker->randomFloat(2, 1.15, 1.6));

            $drug = Drug::create([
                'name' => $name,
                'generic_name' => $generic,
                'drug_code' => 'D' . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT),
                'category' => $category,
                'dosage_form' => $form,
                'strength' => $strength,
                'unit' => 'unit',
                'manufacturer' => $faker->company,
                'description' => $faker->sentence(12),
                'indications' => $faker->sentence(10),
                'contraindications' => $faker->optional(0.6)->sentence(10),
                'side_effects' => $faker->optional(0.7)->sentence(10),
                'dosage_instructions' => $faker->sentence(10),
                'storage_conditions' => 'Store in a cool, dry place',
                'requires_prescription' => $rx,
                'controlled_substance' => false,
                'nhis_covered' => $faker->boolean(60),
                'cost_price' => $cost,
                'selling_price' => $sell,
                'nhis_price' => $faker->boolean(50) ? (float) ($sell * 0.85) : null,
                'is_active' => true,
                'created_by' => $createdBy,
            ]);

            StoreItem::create([
                'drug_id' => $drug->id,
                'name' => $drug->name,
                'description' => $drug->description,
                'category' => $drug->category,
                'price' => $drug->selling_price,
                'stock_quantity' => $faker->numberBetween(25, 250),
                'minimum_stock' => 10,
                'image_url' => null,
                'is_active' => true,
                'is_available' => true,
                'prescription_required' => (bool) $drug->requires_prescription,
                'dosage_instructions' => $drug->dosage_instructions,
                'side_effects' => $drug->side_effects,
                'contraindications' => $drug->contraindications,
                'manufacturer' => $drug->manufacturer,
                'batch_number' => strtoupper($faker->bothify('BATCH-####??')),
                'expiry_date' => Carbon::now()->addMonths($faker->numberBetween(6, 24))->toDateString(),
                'cost_price' => $drug->cost_price,
                'selling_price' => $drug->selling_price,
                'sku' => strtoupper($faker->bothify('SKU-????-####')),
                'metadata' => ['source' => 'demo_seeder'],
                'created_by' => $createdBy,
            ]);
        }
    }

    private function seedVisitsConsultationsVitalsLabsBillingAndPharmacy($faker, $patients, Branch $branch, User $doctor, User $nurse, User $labTech, User $pharmacist, User $receptionist, int $createdBy): void
    {
        $this->command?->info('Creating demo visits, consultations, vitals, labs, prescriptions, invoices...');

        $chiefComplaints = [
            'Fever and chills',
            'Headache',
            'Persistent cough',
            'Abdominal pain',
            'Joint pain',
            'Fatigue and dizziness',
            'Skin rash',
            'Sore throat',
            'Back pain',
            'Chest discomfort',
        ];

        $drugs = Drug::limit(6)->get();
        $now = Carbon::now();

        foreach ($patients->take(20) as $i => $patient) {
            $visitType = $faker->randomElement(['OPD', 'IPD', 'LabOnly', 'PharmacyOnly']);
            $checkIn = $now->copy()->subDays($faker->numberBetween(0, 20))->setTime($faker->numberBetween(8, 16), $faker->randomElement([0, 15, 30, 45]));

            $visit = Visit::create([
                'patient_id' => $patient->id,
                'visit_type' => $visitType,
                'status' => $faker->randomElement(['active', 'completed']),
                'check_in_time' => $checkIn,
                'assigned_doctor_id' => $doctor->id,
                'assigned_nurse_id' => $nurse->id,
                'chief_complaint' => $faker->randomElement($chiefComplaints),
                'priority' => $faker->randomElement(['routine', 'urgent']),
                'branch_id' => $branch->id,
                'created_by' => $receptionist->id,
            ]);

            // Consultation (doctor). We create one per visit so vitals can be linked safely.
            $consultationDate = $checkIn->copy()->addMinutes($faker->numberBetween(30, 180));
            $consultation = Consultation::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'branch_id' => $branch->id,
                'visit_id' => $visit->id,
                'consultation_date' => $consultationDate->format('Y-m-d'),
                'consultation_time' => $consultationDate->format('H:i:s'),
                'consultation_type' => $faker->randomElement(['in-person', 'teleconsultation']),
                'chief_complaint' => $visit->chief_complaint,
                'history_of_present_illness' => $faker->paragraph,
                'consultation_status' => $faker->randomElement(['ongoing', 'completed']),
                'created_by' => $doctor->id,
            ]);

            // Vitals (nurse) linked to consultation (DB requires consultation_id).
            $height = $faker->numberBetween(150, 195);
            $weight = $faker->numberBetween(45, 110);
            $bmi = $weight / (($height / 100) ** 2);

            Vital::create([
                'consultation_id' => $consultation->id,
                'recorded_by' => $nurse->id,
                'recorded_at' => $checkIn->copy()->addMinutes($faker->numberBetween(5, 60)),
                'blood_pressure_systolic' => $faker->numberBetween(95, 170),
                'blood_pressure_diastolic' => $faker->numberBetween(60, 115),
                'pulse_rate' => $faker->numberBetween(60, 120),
                'respiratory_rate' => $faker->numberBetween(12, 28),
                'temperature' => $faker->randomFloat(1, 36.0, 39.5),
                'oxygen_saturation' => $faker->numberBetween(94, 100),
                'height' => $height,
                'weight' => $weight,
                'bmi' => round($bmi, 1),
            ]);

            // Lab request (+ results)
            if ($faker->boolean(60)) {
                $labRequest = LabRequest::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'branch_id' => $branch->id,
                    'test_type' => $faker->randomElement(['Malaria Test', 'Full Blood Count', 'Blood Sugar', 'Urinalysis']),
                    'test_description' => $faker->sentence(10),
                    'clinical_notes' => $faker->optional(0.6)->sentence(12),
                    'priority' => $faker->randomElement(['routine', 'urgent']),
                    'specimen_type' => $faker->randomElement(['Blood', 'Urine']),
                    'status' => $faker->randomElement(['pending', 'in_progress', 'completed']),
                    'overall_status' => 'pending',
                    'technician_id' => $labTech->id,
                    'created_by' => $doctor->id,
                ]);

                if ($labRequest->status === 'completed') {
                    LabTestResult::create([
                        'lab_request_id' => $labRequest->id,
                        'test_name' => $labRequest->test_type,
                        'result_value' => (string) $faker->randomFloat(2, 0, 50),
                        'reference_range' => $faker->randomElement(['0-10', '10-20', '20-30']),
                        'unit' => $faker->randomElement(['mg/dL', 'g/L', '%']),
                        'result_status' => $faker->randomElement(['normal', 'abnormal']),
                        'verified_by' => $labTech->id,
                        'verified_at' => $checkIn->copy()->addHours($faker->numberBetween(2, 24)),
                        'notes' => $faker->optional(0.4)->sentence(10),
                    ]);
                }
            }

            // Prescription (doctor -> pharmacist workflow)
            if ($consultation && $drugs->count() > 0 && $faker->boolean(75)) {
                $drug = $drugs->random();
                Prescription::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'pharmacist_id' => $pharmacist->id,
                    'consultation_id' => $consultation->id,
                    'drug_id' => $drug->id,
                    'medication_name' => $drug->name,
                    'dosage' => $faker->randomElement(['1 tablet', '2 tablets', '1 capsule', '5ml']),
                    'frequency' => $faker->randomElement(['Once daily', 'Twice daily', 'Three times daily']),
                    'duration' => $faker->randomElement(['3 days', '5 days', '7 days', '2 weeks']),
                    'instructions' => $faker->sentence(8),
                    'status' => $faker->randomElement(['pending', 'dispensed', 'completed']),
                    'branch_id' => $branch->id,
                    'created_by' => $doctor->id,
                ]);
            }

            // Invoice + payment
            if ($faker->boolean(65)) {
                $subtotal = $faker->randomFloat(2, 50, 650);
                $tax = round($subtotal * 0.15, 2);
                $total = $subtotal + $tax;
                $paid = $faker->boolean(55) ? $total : $faker->randomFloat(2, 0, $total - 1);
                $balance = max(0, $total - $paid);
                $isPaid = $balance <= 0.01;

                $invoice = Invoice::create([
                    'patient_id' => $patient->id,
                    'branch_id' => $branch->id,
                    'invoice_date' => $checkIn->copy()->toDateString(),
                    'due_date' => $checkIn->copy()->addDays(7)->toDateString(),
                    'items' => [
                        [
                            'name' => $faker->randomElement(['Consultation', 'Lab Test', 'Medication', 'Procedure']),
                            'description' => $faker->sentence(8),
                            'quantity' => 1,
                            'unit_price' => $subtotal,
                            'total' => $subtotal,
                        ],
                    ],
                    'subtotal' => $subtotal,
                    'tax_amount' => $tax,
                    'discount_amount' => 0,
                    'total_amount' => $total,
                    'paid_amount' => $paid,
                    'balance_amount' => $balance,
                    'status' => $isPaid ? 'paid' : 'pending',
                    'payment_status' => $isPaid ? 'paid' : 'partial',
                    'payment_method' => $faker->randomElement(['cash', 'momo', 'card', 'insurance']),
                    'source_platform' => 'web',
                    'created_by' => $createdBy,
                ]);

                if ($paid > 0) {
                    Payment::create([
                        'invoice_id' => $invoice->id,
                        'amount' => $paid,
                        'payment_method' => $invoice->payment_method,
                        'transaction_id' => strtoupper($faker->bothify('TXN####??')),
                        'status' => $isPaid ? 'completed' : 'partial',
                        'processed_by' => $receptionist->id,
                        'processed_at' => $checkIn->copy()->addMinutes($faker->numberBetween(5, 180)),
                    ]);
                }
            }
        }
    }

    private function seedAppointments($faker, $patients, Branch $branch, User $doctor, User $receptionist): void
    {
        $this->command?->info('Creating demo appointments...');

        $reasons = [
            'Review and follow-up',
            'General consultation',
            'Blood pressure check',
            'Diabetes review',
            'Headache review',
            'Teleconsultation follow-up',
        ];

        foreach ($patients->take(18) as $patient) {
            $date = Carbon::now()->addDays($faker->numberBetween(0, 14));
            Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'appointment_date' => $date->toDateString(),
                'appointment_time' => $faker->randomElement(['09:00:00', '09:30:00', '10:00:00', '14:00:00', '15:30:00']),
                'reason' => $faker->randomElement($reasons),
                'status' => $faker->randomElement(['scheduled', 'completed', 'cancelled', 'no-show']),
                'appointment_type' => $faker->randomElement(['in-person', 'teleconsultation']),
                'branch_id' => $branch->id,
                'created_by' => $receptionist->id,
            ]);
        }
    }

    private function seedStoreOrders($faker, $patients, Branch $branch, int $createdBy): void
    {
        $this->command?->info('Creating demo store orders...');

        $items = StoreItem::where('is_active', true)->limit(6)->get();
        if ($items->isEmpty()) return;

        foreach ($patients->take(10) as $patient) {
            if (!$faker->boolean(45)) continue;

            $order = StoreOrder::create([
                'patient_id' => $patient->id,
                'branch_id' => $branch->id,
                'order_date' => Carbon::now()->subDays($faker->numberBetween(0, 10))->toDateString(),
                'subtotal' => 0,
                'tax_amount' => 0,
                'delivery_fee' => 0,
                'total_amount' => 0,
                'status' => $faker->randomElement(['pending', 'processing', 'delivered']),
                'delivery_address' => $faker->streetAddress . ', ' . $faker->city,
                'delivery_method' => $faker->randomElement(['delivery', 'pickup']),
                'payment_method' => $faker->randomElement(['cash', 'momo', 'card']),
                'created_by' => $createdBy,
            ]);

            $count = $faker->numberBetween(1, 3);
            $subtotal = 0.0;

            for ($i = 0; $i < $count; $i++) {
                $item = $items->random();
                $qty = $faker->numberBetween(1, 3);
                $line = (float) $item->price * $qty;
                $subtotal += $line;

                OrderItem::create([
                    'order_id' => $order->id,
                    'store_item_id' => $item->id,
                    'quantity' => $qty,
                    'unit_price' => $item->price,
                    'total_price' => $line,
                ]);
            }

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'delivery_fee' => $order->delivery_method === 'delivery' ? 10 : 0,
                'total_amount' => $subtotal + ($order->delivery_method === 'delivery' ? 10 : 0),
            ]);
        }
    }

    /**
     * Seed active OPD visits and draft consultations for doctor queue testing.
     */
    private function seedDoctorQueueDemo($faker, $patients, Branch $branch, User $doctor, User $receptionist): void
    {
        $this->command?->info('Creating doctor queue demo entries...');

        $queuePatients = $patients->take(5);

        foreach ($queuePatients->take(3) as $patient) {
            Visit::create([
                'patient_id' => $patient->id,
                'visit_type' => 'OPD',
                'status' => 'active',
                'check_in_time' => now()->subMinutes($faker->numberBetween(10, 90)),
                'assigned_doctor_id' => $doctor->id,
                'chief_complaint' => $faker->randomElement(['Fever', 'Headache', 'Cough', 'Abdominal pain']),
                'priority' => $faker->randomElement(['routine', 'urgent']),
                'branch_id' => $branch->id,
                'created_by' => $receptionist->id,
            ]);
        }

        foreach ($queuePatients->skip(3)->take(2) as $patient) {
            $visit = Visit::create([
                'patient_id' => $patient->id,
                'visit_type' => 'OPD',
                'status' => 'active',
                'check_in_time' => now()->subMinutes($faker->numberBetween(5, 45)),
                'assigned_doctor_id' => $doctor->id,
                'chief_complaint' => 'Awaiting doctor review',
                'priority' => 'routine',
                'branch_id' => $branch->id,
                'created_by' => $receptionist->id,
            ]);

            Consultation::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'branch_id' => $branch->id,
                'visit_id' => $visit->id,
                'consultation_date' => now()->toDateString(),
                'consultation_time' => now()->format('H:i:s'),
                'consultation_type' => 'in-person',
                'chief_complaint' => $visit->chief_complaint,
                'consultation_status' => 'ongoing',
                'is_draft' => true,
                'urgency' => 'routine',
                'created_by' => $receptionist->id,
            ]);
        }
    }
}

