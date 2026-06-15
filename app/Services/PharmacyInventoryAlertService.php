<?php

namespace App\Services;

use App\Models\DrugStock;
use App\Models\Notification;
use App\Models\StoreItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PharmacyInventoryAlertService
{
    public function lowStockThreshold(): int
    {
        return (int) env('PHARMACY_LOW_STOCK_THRESHOLD', 10);
    }

    public function expiryWarningDays(): int
    {
        return (int) env('PHARMACY_EXPIRY_WARNING_DAYS', 30);
    }

    /**
     * Base query for active drug stock records, optionally scoped to a branch.
     */
    protected function baseStockQuery(?int $branchId = null): Builder
    {
        $query = DrugStock::query()
            ->where('is_active', true)
            ->with(['drug', 'branch']);

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    /**
     * Drug stocks where current_stock <= reorder_level (or env threshold when reorder_level is null).
     */
    public function checkLowStock(?int $branchId = null): Collection
    {
        $threshold = $this->lowStockThreshold();

        return $this->baseStockQuery($branchId)
            ->where(function (Builder $query) use ($threshold) {
                $query->where(function (Builder $q) {
                    $q->whereNotNull('reorder_level')
                        ->whereColumn('current_stock', '<=', 'reorder_level');
                })->orWhere(function (Builder $q) use ($threshold) {
                    $q->whereNull('reorder_level')
                        ->where('current_stock', '<=', $threshold);
                });
            })
            ->get();
    }

    /**
     * Drug stocks expiring within PHARMACY_EXPIRY_WARNING_DAYS (not yet expired).
     */
    public function checkExpiringSoon(?int $branchId = null): Collection
    {
        $days = $this->expiryWarningDays();

        return $this->baseStockQuery($branchId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays($days))
            ->get();
    }

    /**
     * Drug stocks past expiry date.
     */
    public function checkExpired(?int $branchId = null): Collection
    {
        return $this->baseStockQuery($branchId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now())
            ->get();
    }

    /**
     * Out-of-stock drug_stocks (current_stock = 0).
     */
    public function checkOutOfStock(?int $branchId = null): Collection
    {
        return $this->baseStockQuery($branchId)
            ->where('current_stock', 0)
            ->get();
    }

    /**
     * Store items at or below minimum_stock.
     */
    public function checkStoreItemsLowStock(): Collection
    {
        return StoreItem::query()
            ->where('is_active', true)
            ->whereRaw('stock_quantity <= minimum_stock')
            ->get();
    }

    /**
     * Create notifications for pharmacist and admin users; dedupe per drug_stock_id per day.
     */
    public function createNotificationsForInventoryAlerts(?int $branchId = null): array
    {
        $recipients = $this->getAlertRecipients();
        $created = 0;
        $skipped = 0;

        $alertSets = [
            'pharmacy_low_stock' => [
                'items' => $this->checkLowStock($branchId)->filter(fn ($s) => $s->current_stock > 0),
                'priority' => 'high',
                'title' => 'Low Stock Alert',
            ],
            'pharmacy_out_of_stock' => [
                'items' => $this->checkOutOfStock($branchId),
                'priority' => 'urgent',
                'title' => 'Out of Stock Alert',
            ],
            'pharmacy_expiring_soon' => [
                'items' => $this->checkExpiringSoon($branchId),
                'priority' => 'high',
                'title' => 'Expiring Soon Alert',
            ],
            'pharmacy_expired' => [
                'items' => $this->checkExpired($branchId),
                'priority' => 'urgent',
                'title' => 'Expired Stock Alert',
            ],
        ];

        foreach ($alertSets as $type => $config) {
            foreach ($config['items'] as $stock) {
                $message = $this->buildStockMessage($type, $stock);

                foreach ($recipients as $recipient) {
                    if ($this->notificationExistsToday($recipient->id, $type, $stock->id)) {
                        $skipped++;
                        continue;
                    }

                    Notification::create([
                        'recipient_id' => $recipient->id,
                        'type' => $type,
                        'title' => $config['title'],
                        'message' => $message,
                        'priority' => $config['priority'],
                        'data' => [
                            'drug_stock_id' => $stock->id,
                            'drug_id' => $stock->drug_id,
                            'branch_id' => $stock->branch_id,
                            'current_stock' => $stock->current_stock,
                            'reorder_level' => $stock->reorder_level,
                            'expiry_date' => $stock->expiry_date?->toDateString(),
                            'drug_name' => $stock->drug?->name,
                        ],
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);

                    $created++;
                }
            }
        }

        foreach ($this->checkStoreItemsLowStock() as $item) {
            $type = 'store_item_low_stock';
            $message = "Store item \"{$item->name}\" is low on stock ({$item->stock_quantity} remaining, minimum {$item->minimum_stock}).";

            foreach ($recipients as $recipient) {
                if ($this->notificationExistsToday($recipient->id, $type, $item->id, 'store_item_id')) {
                    $skipped++;
                    continue;
                }

                Notification::create([
                    'recipient_id' => $recipient->id,
                    'type' => $type,
                    'title' => 'Store Item Low Stock',
                    'message' => $message,
                    'priority' => 'medium',
                    'data' => [
                        'store_item_id' => $item->id,
                        'drug_id' => $item->drug_id,
                        'stock_quantity' => $item->stock_quantity,
                        'minimum_stock' => $item->minimum_stock,
                        'item_name' => $item->name,
                    ],
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                $created++;
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'recipients' => $recipients->count(),
        ];
    }

    protected function getAlertRecipients(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', function (Builder $query) {
                $query->whereIn('name', ['pharmacist', 'admin', 'super_admin']);
            })
            ->get();
    }

    protected function notificationExistsToday(
        int $recipientId,
        string $type,
        int $entityId,
        string $entityKey = 'drug_stock_id'
    ): bool {
        return Notification::query()
            ->where('recipient_id', $recipientId)
            ->where('type', $type)
            ->whereDate('created_at', today())
            ->where("data->{$entityKey}", $entityId)
            ->exists();
    }

    protected function buildStockMessage(string $type, DrugStock $stock): string
    {
        $drugName = $stock->drug?->name ?? 'Unknown drug';
        $branchName = $stock->branch?->name ?? 'branch';

        return match ($type) {
            'pharmacy_low_stock' => "{$drugName} at {$branchName} is low on stock ({$stock->current_stock} remaining, reorder level " . ($stock->reorder_level ?? $this->lowStockThreshold()) . ').',
            'pharmacy_out_of_stock' => "{$drugName} at {$branchName} is out of stock.",
            'pharmacy_expiring_soon' => "{$drugName} at {$branchName} expires on {$stock->expiry_date->format('M d, Y')} ({$stock->getDaysUntilExpiry()} days left).",
            'pharmacy_expired' => "{$drugName} at {$branchName} expired on {$stock->expiry_date->format('M d, Y')}.",
            default => "{$drugName} requires attention at {$branchName}.",
        };
    }

    /**
     * Summary counts for dashboards and commands.
     */
    public function getAlertCounts(?int $branchId = null): array
    {
        $lowStock = $this->checkLowStock($branchId);
        $outOfStock = $this->checkOutOfStock($branchId);

        return [
            'low_stock' => $lowStock->where('current_stock', '>', 0)->count(),
            'out_of_stock' => $outOfStock->count(),
            'expiring_soon' => $this->checkExpiringSoon($branchId)->count(),
            'expired' => $this->checkExpired($branchId)->count(),
            'store_items_low_stock' => $this->checkStoreItemsLowStock()->count(),
            'total' => $lowStock->count() + $outOfStock->count()
                + $this->checkExpiringSoon($branchId)->count()
                + $this->checkExpired($branchId)->count(),
        ];
    }
}
