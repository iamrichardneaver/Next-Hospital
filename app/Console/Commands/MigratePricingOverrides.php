<?php

namespace App\Console\Commands;

use App\Models\ServicePricing;
use Illuminate\Console\Command;

class MigratePricingOverrides extends Command
{
    protected $signature = 'pricing:migrate-overrides
                            {--dry-run : List overrides and suggested module fees without changing data}
                            {--branch= : Limit to a branch ID}';

    protected $description = 'Report legacy item_override pricing rows and suggest additive module-fee migration paths';

    public function handle(): int
    {
        $query = ServicePricing::where('pricing_type', ServicePricing::PRICING_TYPE_ITEM_OVERRIDE);

        if ($branch = $this->option('branch')) {
            $query->where('branch_id', (int) $branch);
        }

        $overrides = $query->orderBy('branch_id')->orderBy('service_id')->get();

        if ($overrides->isEmpty()) {
            $this->info('No item_override pricing rows found.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d item_override row(s).', $overrides->count()));
        $this->newLine();

        $rows = [];
        foreach ($overrides as $override) {
            $suggestion = $this->suggestModuleFee($override->service_id, $override->service_type);
            $rows[] = [
                $override->id,
                $override->branch_id,
                $override->service_id,
                $override->service_name,
                number_format((float) $override->base_price, 2),
                $suggestion['module'] ?? '—',
                $suggestion['note'],
            ];
        }

        $this->table(
            ['ID', 'Branch', 'Service ID', 'Name', 'Price', 'Module', 'Guidance'],
            $rows
        );

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->comment('Dry run only — no records were modified.');
            $this->comment('Item overrides continue to replace item prices (backward compatible).');
            $this->comment('To adopt additive fees: create module_fee rows (e.g. module_fee_lab) with applies_on=order_created.');
        }

        return self::SUCCESS;
    }

    private function suggestModuleFee(string $serviceId, string $serviceType): array
    {
        if (str_starts_with($serviceId, 'lab_test_') || $serviceType === 'lab_test') {
            return [
                'module' => 'lab',
                'note' => 'Keep override for test price OR use test type cost + module_fee_lab (order_created)',
            ];
        }

        if (str_starts_with($serviceId, 'drug_') || $serviceType === 'drug') {
            return [
                'module' => 'pharmacy',
                'note' => 'Keep override for drug price OR use stock price + module_fee_pharmacy',
            ];
        }

        if (str_starts_with($serviceId, 'radiology_') || str_starts_with($serviceId, 'IMG-') || in_array($serviceType, ['imaging', 'radiology'], true)) {
            return [
                'module' => 'radiology',
                'note' => 'Keep override for study price OR use modality base_cost + module_fee_radiology',
            ];
        }

        return [
            'module' => null,
            'note' => 'Review manually — may remain as standalone/item override',
        ];
    }
}
