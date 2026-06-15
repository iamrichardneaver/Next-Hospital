<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\LabEquipment;
use App\Models\LabReagent;
use App\Models\LabConsumable;
use App\Models\User;

class LabInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user as creator
        $user = User::first();
        if (!$user) {
            $this->command->error('No users found. Please run user seeders first.');
            return;
        }

        // Create suppliers
        $suppliers = [
            [
                'name' => 'MedLab Supplies Ltd',
                'contact_person' => 'John Smith',
                'email' => 'john@medlabsupplies.com',
                'phone' => '+233 24 123 4567',
                'address' => '123 Lab Street, Accra',
                'city' => 'Accra',
                'country' => 'Ghana',
                'supplier_type' => 'general',
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ],
            [
                'name' => 'Scientific Equipment Co',
                'contact_person' => 'Dr. Sarah Johnson',
                'email' => 'sarah@scientificequip.com',
                'phone' => '+233 24 234 5678',
                'address' => '456 Science Avenue, Kumasi',
                'city' => 'Kumasi',
                'country' => 'Ghana',
                'supplier_type' => 'equipment',
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ],
            [
                'name' => 'Chemical Solutions Inc',
                'contact_person' => 'Michael Brown',
                'email' => 'michael@chemicalsolutions.com',
                'phone' => '+233 24 345 6789',
                'address' => '789 Chemistry Road, Tema',
                'city' => 'Tema',
                'country' => 'Ghana',
                'supplier_type' => 'reagent',
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]
        ];

        $createdSuppliers = [];
        foreach ($suppliers as $supplierData) {
            $supplier = Supplier::create($supplierData);
            $createdSuppliers[] = $supplier;
        }

        // Create lab equipment
        $equipment = [
            [
                'name' => 'Hematology Analyzer',
                'model' => 'Sysmex XN-1000',
                'manufacturer' => 'Sysmex',
                'serial_number' => 'SYM-2024-001',
                'equipment_type' => 'analyzer',
                'location' => 'Lab Room 1',
                'department' => 'Hematology',
                'installation_date' => now()->subMonths(6),
                'next_maintenance_date' => now()->addMonths(3),
                'status' => 'operational',
                'specifications' => [
                    'max_samples_per_hour' => 100,
                    'parameters' => ['WBC', 'RBC', 'HGB', 'HCT', 'PLT'],
                    'sample_volume' => '20μL'
                ],
                'warranty_expiry' => now()->addYears(2),
                'supplier_id' => $createdSuppliers[1]->id,
                'purchase_date' => now()->subMonths(6),
                'purchase_cost' => 150000.00,
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ],
            [
                'name' => 'Microscope',
                'model' => 'Olympus CX23',
                'manufacturer' => 'Olympus',
                'serial_number' => 'OLY-2024-002',
                'equipment_type' => 'microscope',
                'location' => 'Lab Room 2',
                'department' => 'Microbiology',
                'installation_date' => now()->subMonths(3),
                'next_maintenance_date' => now()->addMonths(9),
                'status' => 'operational',
                'specifications' => [
                    'magnification' => '40x-1000x',
                    'illumination' => 'LED',
                    'objectives' => ['4x', '10x', '40x', '100x']
                ],
                'warranty_expiry' => now()->addYears(1),
                'supplier_id' => $createdSuppliers[1]->id,
                'purchase_date' => now()->subMonths(3),
                'purchase_cost' => 25000.00,
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ],
            [
                'name' => 'Centrifuge',
                'model' => 'Eppendorf 5424',
                'manufacturer' => 'Eppendorf',
                'serial_number' => 'EPP-2024-003',
                'equipment_type' => 'centrifuge',
                'location' => 'Lab Room 1',
                'department' => 'Chemistry',
                'installation_date' => now()->subMonths(1),
                'next_maintenance_date' => now()->addMonths(11),
                'status' => 'operational',
                'specifications' => [
                    'max_rpm' => 15000,
                    'max_rcf' => 20800,
                    'capacity' => '24 x 1.5/2.0mL tubes'
                ],
                'warranty_expiry' => now()->addYears(2),
                'supplier_id' => $createdSuppliers[1]->id,
                'purchase_date' => now()->subMonths(1),
                'purchase_cost' => 12000.00,
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]
        ];

        foreach ($equipment as $equipmentData) {
            LabEquipment::create($equipmentData);
        }

        // Create lab reagents
        $reagents = [
            [
                'name' => 'Complete Blood Count Reagent',
                'catalog_number' => 'CBC-001',
                'manufacturer' => 'Sysmex',
                'supplier_id' => $createdSuppliers[2]->id,
                'category' => 'Hematology',
                'subcategory' => 'CBC Reagents',
                'description' => 'Complete Blood Count reagent for automated analyzers',
                'unit_of_measure' => 'bottles',
                'current_stock' => 15,
                'minimum_stock' => 5,
                'maximum_stock' => 50,
                'reorder_level' => 8,
                'unit_cost' => 250.00,
                'expiry_date' => now()->addMonths(12),
                'batch_number' => 'BATCH-2024-001',
                'storage_requirements' => ['temperature' => '2-8°C', 'humidity' => '<70%'],
                'storage_temperature' => 4.0,
                'storage_humidity' => 60.0,
                'light_sensitive' => true,
                'hazardous' => false,
                'safety_notes' => 'Store in refrigerator, avoid direct sunlight',
                'usage_instructions' => 'Use within 30 days of opening',
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ],
            [
                'name' => 'Glucose Test Reagent',
                'catalog_number' => 'GLU-002',
                'manufacturer' => 'Roche',
                'supplier_id' => $createdSuppliers[2]->id,
                'category' => 'Chemistry',
                'subcategory' => 'Glucose Testing',
                'description' => 'Glucose oxidase reagent for blood glucose testing',
                'unit_of_measure' => 'bottles',
                'current_stock' => 8,
                'minimum_stock' => 10,
                'maximum_stock' => 30,
                'reorder_level' => 12,
                'unit_cost' => 180.00,
                'expiry_date' => now()->addMonths(8),
                'batch_number' => 'BATCH-2024-002',
                'storage_requirements' => ['temperature' => '2-8°C'],
                'storage_temperature' => 4.0,
                'light_sensitive' => true,
                'hazardous' => false,
                'safety_notes' => 'Store in refrigerator',
                'usage_instructions' => 'Use within 14 days of opening',
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ],
            [
                'name' => 'Urine Test Strips',
                'catalog_number' => 'UTS-003',
                'manufacturer' => 'Siemens',
                'supplier_id' => $createdSuppliers[2]->id,
                'category' => 'Urinalysis',
                'subcategory' => 'Test Strips',
                'description' => '10-parameter urine test strips',
                'unit_of_measure' => 'boxes',
                'current_stock' => 25,
                'minimum_stock' => 10,
                'maximum_stock' => 100,
                'reorder_level' => 15,
                'unit_cost' => 45.00,
                'expiry_date' => now()->addMonths(18),
                'batch_number' => 'BATCH-2024-003',
                'storage_requirements' => ['temperature' => '15-30°C', 'humidity' => '<60%'],
                'storage_temperature' => 22.0,
                'storage_humidity' => 45.0,
                'light_sensitive' => false,
                'hazardous' => false,
                'safety_notes' => 'Store in dry place',
                'usage_instructions' => 'Use within 6 months of opening',
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]
        ];

        foreach ($reagents as $reagentData) {
            LabReagent::create($reagentData);
        }

        // Create lab consumables
        $consumables = [
            [
                'name' => 'Blood Collection Tubes',
                'catalog_number' => 'BCT-001',
                'manufacturer' => 'BD',
                'supplier_id' => $createdSuppliers[0]->id,
                'category' => 'Collection',
                'subcategory' => 'Blood Tubes',
                'description' => 'Vacutainer blood collection tubes with EDTA',
                'unit_of_measure' => 'boxes',
                'current_stock' => 50,
                'minimum_stock' => 20,
                'maximum_stock' => 200,
                'reorder_level' => 25,
                'unit_cost' => 12.00,
                'expiry_date' => now()->addYears(3),
                'batch_number' => 'BATCH-2024-004',
                'storage_requirements' => ['temperature' => '15-30°C'],
                'disposable' => true,
                'sterile' => true,
                'single_use' => true,
                'usage_instructions' => 'Use within 2 years of manufacture date',
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ],
            [
                'name' => 'Microscope Slides',
                'catalog_number' => 'MS-002',
                'manufacturer' => 'Thermo Scientific',
                'supplier_id' => $createdSuppliers[0]->id,
                'category' => 'Microscopy',
                'subcategory' => 'Slides',
                'description' => 'Glass microscope slides 75x25mm',
                'unit_of_measure' => 'boxes',
                'current_stock' => 30,
                'minimum_stock' => 15,
                'maximum_stock' => 100,
                'reorder_level' => 20,
                'unit_cost' => 8.50,
                'expiry_date' => null,
                'batch_number' => 'BATCH-2024-005',
                'storage_requirements' => ['temperature' => '15-30°C'],
                'disposable' => false,
                'sterile' => false,
                'single_use' => false,
                'usage_instructions' => 'Clean and reuse as needed',
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ],
            [
                'name' => 'Disposable Gloves',
                'catalog_number' => 'DG-003',
                'manufacturer' => 'Ansell',
                'supplier_id' => $createdSuppliers[0]->id,
                'category' => 'PPE',
                'subcategory' => 'Gloves',
                'description' => 'Nitrile examination gloves, powder-free',
                'unit_of_measure' => 'boxes',
                'current_stock' => 12,
                'minimum_stock' => 20,
                'maximum_stock' => 50,
                'reorder_level' => 25,
                'unit_cost' => 15.00,
                'expiry_date' => now()->addYears(2),
                'batch_number' => 'BATCH-2024-006',
                'storage_requirements' => ['temperature' => '15-30°C'],
                'disposable' => true,
                'sterile' => false,
                'single_use' => true,
                'usage_instructions' => 'Use within 2 years of manufacture date',
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]
        ];

        foreach ($consumables as $consumableData) {
            LabConsumable::create($consumableData);
        }

        $this->command->info('Lab inventory data seeded successfully!');
    }
}