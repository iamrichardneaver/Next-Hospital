<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchSetting;
use Illuminate\Database\Seeder;

class BranchSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command?->warn('No branches found — skipping branch_settings seed.');
            return;
        }

        $defaults = [
            'timezone' => ['Africa/Accra', 'string'],
            'currency' => ['GHS', 'string'],
            'appointment_reminder_hours' => ['24', 'integer'],
            'lab_result_notification' => ['true', 'boolean'],
            'receipt_footer' => ['Thank you for choosing our facility.', 'string'],
        ];

        foreach ($branches as $branch) {
            foreach ($defaults as $key => [$value, $type]) {
                BranchSetting::updateOrCreate(
                    ['branch_id' => $branch->id, 'setting_key' => $key],
                    ['setting_value' => $value, 'setting_type' => $type]
                );
            }
        }

        $this->command?->info('Branch settings seeded for ' . $branches->count() . ' branch(es).');
    }
}
