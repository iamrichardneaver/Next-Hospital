<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Debtor;
use App\Models\Patient;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;

class DebtorTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first branch
        $branch = Branch::first();
        if (!$branch) {
            $this->command->error('No branches found. Please run BranchSeeder first.');
            return;
        }

        // Get some patients
        $patients = Patient::where('branch_id', $branch->id)->take(5)->get();
        
        if ($patients->isEmpty()) {
            $this->command->error('No patients found. Please run PatientSeeder first.');
            return;
        }

        // Get a user for created_by
        $user = User::first();
        if (!$user) {
            $this->command->error('No users found. Please run UserSeeder first.');
            return;
        }

        foreach ($patients as $index => $patient) {
            // Create some invoices for each patient
            $invoice1 = Invoice::create([
                'patient_id' => $patient->id,
                'branch_id' => $branch->id,
                'invoice_number' => 'TEST' . time() . '-' . ($index + 1) . '-1',
                'invoice_date' => now()->subDays(rand(10, 60))->toDateString(),
                'subtotal' => rand(100, 500),
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => rand(100, 500),
                'status' => 'pending',
                'created_by' => $user->id,
                'items' => json_encode([
                    [
                        'description' => 'Consultation Fee',
                        'quantity' => 1,
                        'unit_price' => rand(50, 200),
                        'total' => rand(50, 200)
                    ]
                ])
            ]);

            // Create a second invoice for some patients
            if ($index % 2 == 0) {
                $invoice2 = Invoice::create([
                    'patient_id' => $patient->id,
                    'branch_id' => $branch->id,
                    'invoice_number' => 'TEST' . time() . '-' . ($index + 1) . '-2',
                    'invoice_date' => now()->subDays(rand(5, 30))->toDateString(),
                    'subtotal' => rand(50, 300),
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total_amount' => rand(50, 300),
                    'status' => 'pending',
                    'created_by' => $user->id,
                    'items' => json_encode([
                        [
                            'description' => 'Lab Test Fee',
                            'quantity' => 1,
                            'unit_price' => rand(30, 150),
                            'total' => rand(30, 150)
                        ]
                    ])
                ]);
            }

            // Create partial payment for some invoices
            if ($index % 3 == 0) {
                $payment = Payment::create([
                    'invoice_id' => $invoice1->id,
                    'amount' => $invoice1->total_amount * 0.5, // 50% payment
                    'payment_method' => 'cash',
                    'payment_date' => now()->subDays(rand(1, 10))->toDateString(),
                    'reference_number' => 'PAY' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
                    'status' => 'completed',
                    'processed_by' => $user->id,
                    'processed_at' => now()->subDays(rand(1, 10))
                ]);

                // Update invoice status to paid (since we don't have partial status)
                $invoice1->update([
                    'status' => 'paid'
                ]);
            }

            // Create debtor record (without ID prefix)
            $debtor = new Debtor();
            $debtor->patient_id = $patient->id;
            $debtor->branch_id = $branch->id;
            $debtor->total_outstanding = 0; // Will be calculated
            $debtor->total_paid = 0; // Will be calculated
            $debtor->total_invoiced = 0; // Will be calculated
            $debtor->outstanding_invoices_count = 0;
            $debtor->overdue_invoices_count = 0;
            $debtor->last_payment_date = null;
            $debtor->last_invoice_date = null;
            $debtor->first_outstanding_date = null;
            $debtor->debt_status = 'current';
            $debtor->days_overdue = 0;
            $debtor->largest_outstanding_amount = 0;
            $debtor->notes = 'Test debtor record';
            $debtor->is_active = true;
            $debtor->created_by = $user->id;
            $debtor->save();

            // Calculate debtor amounts
            $debtor->calculateOutstanding();
            $debtor->updateStatus();

            $this->command->info("Created debtor for patient: {$patient->first_name} {$patient->last_name}");
        }

        $this->command->info('Debtor test data created successfully!');
    }
}
