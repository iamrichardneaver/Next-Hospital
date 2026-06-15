<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'name' => 'NextHospital Main Branch',
                'code' => 'MAIN',
                'address' => '123 Hospital Street, Accra, Ghana',
                'phone' => '+233-123-456-789',
                'email' => 'main@nexthospital.com',
                'timezone' => 'Africa/Accra',
                'is_active' => true,
                'settings' => [
                    'currency' => 'GHS',
                    'working_hours' => '24/7',
                    'emergency_contact' => '+233-123-456-789',
                ],
            ],
            [
                'name' => 'NextHospital East Branch',
                'code' => 'EAST',
                'address' => '456 East Avenue, Tema, Ghana',
                'phone' => '+233-123-456-790',
                'email' => 'east@nexthospital.com',
                'timezone' => 'Africa/Accra',
                'is_active' => true,
                'settings' => [
                    'currency' => 'GHS',
                    'working_hours' => '6:00 AM - 10:00 PM',
                    'emergency_contact' => '+233-123-456-790',
                ],
            ],
            [
                'name' => 'NextHospital West Branch',
                'code' => 'WEST',
                'address' => '789 West Road, Kumasi, Ghana',
                'phone' => '+233-123-456-791',
                'email' => 'west@nexthospital.com',
                'timezone' => 'Africa/Accra',
                'is_active' => true,
                'settings' => [
                    'currency' => 'GHS',
                    'working_hours' => '6:00 AM - 10:00 PM',
                    'emergency_contact' => '+233-123-456-791',
                ],
            ],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
}