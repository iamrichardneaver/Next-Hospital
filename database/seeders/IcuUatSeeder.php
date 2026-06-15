<?php

namespace Database\Seeders;

use App\Models\Bed;
use App\Models\Branch;
use App\Models\Ward;
use Illuminate\Database\Seeder;

class IcuUatSeeder extends Seeder
{
    /**
     * Seed one ICU ward and two beds when none exist (UAT).
     */
    public function run(): void
    {
        $branch = Branch::where('is_active', true)->first();
        if (!$branch) {
            return;
        }

        $existing = Ward::where('branch_id', $branch->id)
            ->where(function ($q) {
                $q->where('type', 'icu')->orWhere('name', 'like', '%ICU%');
            })
            ->exists();

        if ($existing) {
            return;
        }

        $ward = Ward::create([
            'branch_id' => $branch->id,
            'name' => 'Intensive Care Unit',
            'code' => 'ICU',
            'type' => 'icu',
            'total_beds' => 2,
            'description' => 'ICU ward for UAT',
            'is_active' => true,
        ]);

        foreach (['ICU-01', 'ICU-02'] as $bedNumber) {
            Bed::create([
                'ward_id' => $ward->id,
                'bed_number' => $bedNumber,
                'status' => 'available',
                'bed_type' => 'icu',
            ]);
        }
    }
}
