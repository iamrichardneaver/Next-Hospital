<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Debtor;
use App\Models\RevenueTransaction;
use App\Services\DebtorService;
use Illuminate\Support\Facades\DB;

class FixFinancialDataIntegrity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:fix-integrity {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix financial data integrity issues (invoices, payments, debtors)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('🔍 Starting Financial Data Integrity Check...');
        $this->info($dryRun ? '⚠️  DRY RUN MODE - No changes will be made' : '✅ LIVE MODE - Changes will be applied');
        $this->newLine();
        
        // 1. Fix invoices with incorrect paid_amount
        $this->fixInvoicePaidAmounts($dryRun);
        
        // 2. Fix invoices with incorrect balance_amount
        $this->fixInvoiceBalanceAmounts($dryRun);
        
        // 3. Fix invoice payment_status
        $this->fixInvoicePaymentStatus($dryRun);
        
        // 4. Update debtor records
        $this->updateDebtorRecords($dryRun);
        
        // 5. Create missing revenue transactions
        $this->createMissingRevenueTransactions($dryRun);
        
        $this->newLine();
        $this->info('✅ Financial data integrity check complete!');
    }

    /**
     * Fix invoices with incorrect paid_amount.
     */
    protected function fixInvoicePaidAmounts($dryRun)
    {
        $this->info('1. Checking invoice paid_amount accuracy...');
        
        $invoices = Invoice::all();
        $fixed = 0;
        
        foreach ($invoices as $invoice) {
            $actualPaid = Payment::where('invoice_id', $invoice->id)
                ->where('status', 'completed')
                ->sum('amount');
            
            if (abs($invoice->paid_amount - $actualPaid) > 0.01) {
                $this->warn("   Invoice #{$invoice->invoice_number}: paid_amount={$invoice->paid_amount}, actual={$actualPaid}");
                
                if (!$dryRun) {
                    $invoice->paid_amount = $actualPaid;
                    $invoice->saveQuietly(); // Don't trigger observers yet
                }
                $fixed++;
            }
        }
        
        $this->info($dryRun 
            ? "   Found {$fixed} invoices with incorrect paid_amount (would fix)" 
            : "   Fixed {$fixed} invoices with incorrect paid_amount");
        $this->newLine();
    }

    /**
     * Fix invoices with incorrect balance_amount.
     */
    protected function fixInvoiceBalanceAmounts($dryRun)
    {
        $this->info('2. Checking invoice balance_amount accuracy...');
        
        $invoices = Invoice::all();
        $fixed = 0;
        
        foreach ($invoices as $invoice) {
            $correctBalance = $invoice->total_amount - $invoice->paid_amount;
            
            if (abs($invoice->balance_amount - $correctBalance) > 0.01) {
                $this->warn("   Invoice #{$invoice->invoice_number}: balance={$invoice->balance_amount}, should be={$correctBalance}");
                
                if (!$dryRun) {
                    $invoice->balance_amount = $correctBalance;
                    $invoice->saveQuietly();
                }
                $fixed++;
            }
        }
        
        $this->info($dryRun 
            ? "   Found {$fixed} invoices with incorrect balance_amount (would fix)" 
            : "   Fixed {$fixed} invoices with incorrect balance_amount");
        $this->newLine();
    }

    /**
     * Fix invoice payment_status.
     */
    protected function fixInvoicePaymentStatus($dryRun)
    {
        $this->info('3. Checking invoice payment_status accuracy...');
        
        $invoices = Invoice::all();
        $fixed = 0;
        
        foreach ($invoices as $invoice) {
            $correctStatus = $this->calculateCorrectPaymentStatus($invoice);
            
            if ($invoice->payment_status !== $correctStatus) {
                $this->warn("   Invoice #{$invoice->invoice_number}: status={$invoice->payment_status}, should be={$correctStatus}");
                
                if (!$dryRun) {
                    $invoice->payment_status = $correctStatus;
                    
                    // Also update main status if paid
                    if ($correctStatus === 'paid') {
                        $invoice->status = 'paid';
                    } elseif ($correctStatus === 'partial') {
                        $invoice->status = 'partial';
                    }
                    
                    $invoice->saveQuietly();
                }
                $fixed++;
            }
        }
        
        $this->info($dryRun 
            ? "   Found {$fixed} invoices with incorrect payment_status (would fix)" 
            : "   Fixed {$fixed} invoices with incorrect payment_status");
        $this->newLine();
    }

    /**
     * Calculate correct payment status.
     */
    protected function calculateCorrectPaymentStatus($invoice)
    {
        $paidAmount = $invoice->paid_amount ?? 0;
        $totalAmount = $invoice->total_amount ?? 0;
        
        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            return 'paid';
        } elseif ($paidAmount > 0 && $paidAmount < $totalAmount) {
            return 'partial';
        } elseif ($invoice->due_date && $invoice->due_date < now()->toDateString() && $paidAmount == 0) {
            return 'overdue';
        } else {
            return 'unpaid';
        }
    }

    /**
     * Update debtor records.
     */
    protected function updateDebtorRecords($dryRun)
    {
        $this->info('4. Updating debtor records...');
        
        if (!$dryRun) {
            $debtorService = app(DebtorService::class);
            $count = $debtorService->updateAllDebtors();
            $this->info("   Updated {$count} debtor records");
        } else {
            $count = Debtor::where('is_active', true)->count();
            $this->info("   Would update {$count} debtor records");
        }
        
        $this->newLine();
    }

    /**
     * Create missing revenue transactions.
     */
    protected function createMissingRevenueTransactions($dryRun)
    {
        $this->info('5. Creating missing revenue transactions...');
        
        // Check invoices without revenue transactions
        $invoicesWithoutRevenue = Invoice::whereDoesntHave('revenueTransactions', function($query) {
            $query->where('source_type', Invoice::class);
        })->count();
        
        // Check payments without revenue transactions
        $paymentsWithoutRevenue = Payment::whereDoesntHave('revenueTransactions', function($query) {
            $query->where('source_type', Payment::class);
        })->where('status', 'completed')->count();
        
        $this->info("   Found {$invoicesWithoutRevenue} invoices without revenue transactions");
        $this->info("   Found {$paymentsWithoutRevenue} payments without revenue transactions");
        
        if (!$dryRun && ($invoicesWithoutRevenue > 0 || $paymentsWithoutRevenue > 0)) {
            $this->warn('   Note: These will be created automatically by observers on next update');
            $this->info('   Recommend running: php artisan tinker');
            $this->info('   Then: Invoice::chunk(100, fn($invoices) => $invoices->each->touch())');
        }
        
        $this->newLine();
    }
}

