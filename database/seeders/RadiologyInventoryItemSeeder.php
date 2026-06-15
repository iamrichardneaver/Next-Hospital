<?php

namespace Database\Seeders;

use App\Models\RadiologyInventoryItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class RadiologyInventoryItemSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'admin']))->value('id')
            ?? User::value('id');

        $items = [
            ['name' => 'Iohexol 350mg/ml Contrast', 'sku' => 'RAD-CON-001', 'category' => 'contrast', 'unit' => 'vial', 'reorder_level' => 10],
            ['name' => 'Gadolinium Contrast Agent', 'sku' => 'RAD-CON-002', 'category' => 'contrast', 'unit' => 'vial', 'reorder_level' => 5],
            ['name' => '14x17 X-Ray Film', 'sku' => 'RAD-FLM-001', 'category' => 'film', 'unit' => 'box', 'reorder_level' => 3],
            ['name' => '8x10 X-Ray Film', 'sku' => 'RAD-FLM-002', 'category' => 'film', 'unit' => 'box', 'reorder_level' => 3],
            ['name' => 'Lead Apron Covers', 'sku' => 'RAD-CNS-001', 'category' => 'consumable', 'unit' => 'pack', 'reorder_level' => 5],
            ['name' => 'IV Cannula 20G', 'sku' => 'RAD-CNS-002', 'category' => 'consumable', 'unit' => 'box', 'reorder_level' => 10],
            ['name' => 'Ultrasound Gel 5L', 'sku' => 'RAD-SUP-001', 'category' => 'supply', 'unit' => 'bottle', 'reorder_level' => 2],
            ['name' => 'Marker Labels (L/R)', 'sku' => 'RAD-SUP-002', 'category' => 'supply', 'unit' => 'pack', 'reorder_level' => 5],
        ];

        foreach ($items as $item) {
            RadiologyInventoryItem::updateOrCreate(
                ['sku' => $item['sku']],
                array_merge($item, [
                    'is_active' => true,
                    'created_by' => $userId,
                ])
            );
        }

        $this->command?->info('Radiology inventory catalog seeded: ' . count($items) . ' items');
    }
}
