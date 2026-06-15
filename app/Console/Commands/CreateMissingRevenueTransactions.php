<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RevenueTransaction;
use Illuminate\Support\Facades\DB;

class CreateMissingRevenueTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:create-missing-revenue-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create missing revenue transactions for existing invoices and payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Creating missing revenue transactions...');
        $this->newLine();
        
        // Get invoices without revenue transactions
        $invoicesWithoutRevenue = Invoice::whereDoesntHave('revenueTransactions', function($query) {
            $query->where('source_type', Invoice::class);
        })->get();
        
        $this->info("Found {$invoicesWithoutRevenue->count()} invoices without revenue transactions");
        
        $invoiceCount = 0;
        foreach ($invoicesWithoutRevenue as $invoice) {
            try {
                // Manually create revenue transaction for invoice
                $serviceType = $this->determineServiceType($invoice);
                
                RevenueTransaction::create([
                    'patient_id' => $invoice->patient_id,
                    'branch_id' => $invoice->branch_id,
                    'source_type' => Invoice::class,
                    'source_id' => $invoice->id,
                    'service_type' => $serviceType,
                    'amount' => $invoice->total_amount,
                    'payment_method' => $invoice->payment_method,
                    'transaction_date' => $invoice->invoice_date ?? now()->toDateString(),
                    'status' => $invoice->payment_status === 'paid' ? 'completed' : 'pending',
                    'metadata' => [
                        'invoice_number' => $invoice->invoice_number,
                        'created_via' => 'manual_fix'
                    ],
                    'recorded_by' => $invoice->created_by ?? 1
                ]);
                
                $invoiceCount++;
                $this->info("  ✓ Created revenue transaction for Invoice #{$invoice->invoice_number}");
                
            } catch (\Exception $e) {
                $this->error("  ✗ Failed for Invoice #{$invoice->invoice_number}: " . $e->getMessage());
            }
        }
        
        // Get payments without revenue transactions
        $paymentsWithoutRevenue = Payment::whereDoesntHave('revenueTransactions', function($query) {
            $query->where('source_type', Payment::class);
        })->where('status', 'completed')->get();
        
        $this->newLine();
        $this->info("Found {$paymentsWithoutRevenue->count()} payments without revenue transactions");
        
        $paymentCount = 0;
        foreach ($paymentsWithoutRevenue as $payment) {
            try {
                $invoice = $payment->invoice;
                $serviceType = $invoice ? $this->determineServiceType($invoice) : 'other';
                
                RevenueTransaction::create([
                    'patient_id' => $payment->patient_id,
                    'branch_id' => $payment->branch_id,
                    'source_type' => Payment::class,
                    'source_id' => $payment->id,
                    'service_type' => $serviceType,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'transaction_date' => $payment->payment_date ?? now()->toDateString(),
                    'status' => 'completed',
                    'metadata' => [
                        'payment_reference' => $payment->payment_reference,
                        'invoice_number' => $invoice->invoice_number ?? 'N/A',
                        'created_via' => 'manual_fix'
                    ],
                    'recorded_by' => $payment->processed_by ?? 1
                ]);
                
                $paymentCount++;
                $this->info("  ✓ Created revenue transaction for Payment #{$payment->payment_reference}");
                
            } catch (\Exception $e) {
                $this->error("  ✗ Failed for Payment #{$payment->id}: " . $e->getMessage());
            }
        }
        
        $this->newLine();
        $this->info("✅ Created {$invoiceCount} invoice revenue transactions");
        $this->info("✅ Created {$paymentCount} payment revenue transactions");
        $this->info("🎉 Total: " . ($invoiceCount + $paymentCount) . " revenue transactions created");
    }

    /**
     * Determine service type from invoice.
     */
    protected function determineServiceType($invoice)
    {
        if (!is_array($invoice->items) || empty($invoice->items)) {
            return 'other';
        }
        
        $firstItem = $invoice->items[0] ?? [];
        $serviceType = $firstItem['service_type'] ?? null;
        
        $serviceTypeMap = [
            'consultation' => 'consultation',
            'lab_test' => 'lab',
            'lab' => 'lab',
            'imaging' => 'imaging',
            'pharmacy' => 'pharmacy',
            'drug' => 'pharmacy',
            'ward' => 'ward',
            'bed' => 'ward',
            'surgery' => 'surgery',
            'procedure' => 'surgery',
            'ecommerce' => 'ecommerce',
        ];
        
        return $serviceTypeMap[strtolower($serviceType ?? '')] ?? 'other';
    }
}

