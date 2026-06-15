<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Services\IdPrefixService;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Salaries & Wages', 'code' => 'SALARIES', 'description' => 'Staff payroll and benefits'],
            ['name' => 'Utilities', 'code' => 'UTILITIES', 'description' => 'Electricity, water, internet, phone'],
            ['name' => 'Medical Supplies', 'code' => 'SUPPLIES', 'description' => 'Consumables, gloves, syringes, etc.'],
            ['name' => 'Pharmacy Stock', 'code' => 'PHARM_STOCK', 'description' => 'Drug and pharmacy inventory purchases'],
            ['name' => 'Laboratory Supplies', 'code' => 'LAB_SUPPLIES', 'description' => 'Lab reagents, consumables, and supplies purchases'],
            ['name' => 'Radiology Supplies', 'code' => 'RADIOLOGY_SUPPLIES', 'description' => 'Radiology contrast, films, consumables, and supplies purchases'],
            ['name' => 'Equipment Maintenance', 'code' => 'MAINTENANCE', 'description' => 'Repairs and servicing of medical equipment'],
            ['name' => 'Rent & Facilities', 'code' => 'RENT', 'description' => 'Building rent and facility costs'],
            ['name' => 'Transport & Logistics', 'code' => 'TRANSPORT', 'description' => 'Ambulance fuel, deliveries, travel'],
            ['name' => 'Marketing & Outreach', 'code' => 'MARKETING', 'description' => 'Advertising and community programs'],
            ['name' => 'Insurance & Licenses', 'code' => 'INSURANCE', 'description' => 'Facility insurance and regulatory fees'],
            ['name' => 'Office & Admin', 'code' => 'ADMIN', 'description' => 'Stationery, software, general admin'],
            ['name' => 'Professional Services', 'code' => 'PROF_SERV', 'description' => 'Legal, audit, consulting fees'],
            ['name' => 'Petty Cash', 'code' => 'PETTY_CASH', 'description' => 'Small cash outlays — courier, snacks, minor supplies'],
            ['name' => 'Department Miscellaneous', 'code' => 'DEPT_MISC', 'description' => 'Miscellaneous department operational costs'],
            ['name' => 'Ward & Nursing Supplies', 'code' => 'WARD_SUPPLIES', 'description' => 'Ward consumables not covered by central inventory'],
            ['name' => 'Office Supplies', 'code' => 'OFFICE_SUP', 'description' => 'Stationery, printing, and desk supplies'],
            ['name' => 'Other Expenses', 'code' => 'OTHER', 'description' => 'Miscellaneous operational expenses'],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::updateOrCreate(
                ['code' => $category['code']],
                array_merge($category, ['is_active' => true])
            );
        }

        app(IdPrefixService::class)->getOrCreateSetting('expense', [
            'module_prefix' => 'EXP',
            'description' => 'ID pattern for operating expenses',
        ]);

        $this->command?->info('Expense categories seeded: ' . count($categories));
    }
}
