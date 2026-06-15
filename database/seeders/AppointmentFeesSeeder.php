<?php

namespace Database\Seeders;

use App\Models\AppointmentFee;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppointmentFeesSeeder extends Seeder
{
    public function run(): void
    {
        if (AppointmentFee::count() > 0) {
            $this->command?->info('appointment_fees already has rows — skipping.');
            return;
        }

        $branchId = Branch::query()->value('id') ?? 1;
        $now = now();

        $fees = [
            [
                'doctor_id' => null,
                'branch_id' => $branchId,
                'appointment_type' => 'in-person',
                'fee_category' => 'general',
                'base_fee' => 50.00,
                'currency' => 'GHS',
                'platform_fee' => 0,
                'tax_rate' => 0,
                'is_active' => true,
                'effective_from' => $now->toDateString(),
                'description' => 'Default in-person consultation fee',
                'created_by' => 1,
            ],
            [
                'doctor_id' => null,
                'branch_id' => $branchId,
                'appointment_type' => 'teleconsultation',
                'fee_category' => 'general',
                'base_fee' => 40.00,
                'currency' => 'GHS',
                'platform_fee' => 0,
                'tax_rate' => 0,
                'is_active' => true,
                'effective_from' => $now->toDateString(),
                'description' => 'Default teleconsultation fee',
                'created_by' => 1,
            ],
        ];

        DB::table('appointment_fees')->insert($fees);

        $this->command?->info('Seeded default appointment fees.');
    }
}
