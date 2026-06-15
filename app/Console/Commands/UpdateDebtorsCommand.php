<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DebtorService;

class UpdateDebtorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debtors:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all debtor records and recalculate outstanding amounts and days overdue';

    protected $debtorService;

    public function __construct(DebtorService $debtorService)
    {
        parent::__construct();
        $this->debtorService = $debtorService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating all debtor records...');
        
        $count = $this->debtorService->updateAllDebtors();
        
        $this->info("Successfully updated {$count} debtor records!");
        
        return Command::SUCCESS;
    }
}
