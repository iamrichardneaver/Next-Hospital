<?php

namespace App\Console\Commands;

use App\Services\PharmacyInventoryAlertService;
use Illuminate\Console\Command;

class CheckInventoryAlertsCommand extends Command
{
    protected $signature = 'inventory:check-alerts {--branch= : Optional branch ID to scope alerts}';

    protected $description = 'Check pharmacy inventory for low stock and expiry alerts, then notify pharmacists and admins';

    public function handle(PharmacyInventoryAlertService $alertService): int
    {
        $branchId = $this->option('branch') ? (int) $this->option('branch') : null;

        $this->info('Checking pharmacy inventory alerts...');

        $counts = $alertService->getAlertCounts($branchId);

        $this->table(
            ['Alert Type', 'Count'],
            [
                ['Low Stock', $counts['low_stock']],
                ['Out of Stock', $counts['out_of_stock']],
                ['Expiring Soon', $counts['expiring_soon']],
                ['Expired', $counts['expired']],
                ['Store Items Low Stock', $counts['store_items_low_stock']],
                ['Total Drug Stock Alerts', $counts['total']],
            ]
        );

        $result = $alertService->createNotificationsForInventoryAlerts($branchId);

        $this->info("Notifications created: {$result['created']}");
        $this->info("Notifications skipped (deduped): {$result['skipped']}");
        $this->info("Recipients notified: {$result['recipients']}");

        return Command::SUCCESS;
    }
}
