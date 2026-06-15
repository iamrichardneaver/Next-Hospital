<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UpdateReceptionistCashierPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔧 Updating nurse walk-ins permissions (receptionist/cashier: Refine* seeders)...');

        // Receptionist grants: RefineReceptionistPermissionsSeeder (do not assign here).

        // Cashier grants: RefineCashierPermissionsSeeder (do not assign here).

        // Update Nurse (for vitals recording)
        $nurse = Role::where('name', 'nurse')->first();
        if ($nurse) {
            $nurse->givePermissionTo([
                'manage_walk_ins',  // Nurses also manage walk-ins
            ]);
            $this->command->info('✅ Nurse permissions updated');
        } else {
            $this->command->warn('⚠️  Nurse role not found');
        }

        $this->command->info('✅ All role permissions updated successfully!');
    }
}

