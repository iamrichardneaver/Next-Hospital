<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class CashierRoleSeeder extends Seeder
{
    /**
     * Create cashier role shell and demo user.
     *
     * Permissions are synced by RefineCashierPermissionsSeeder (canonical list).
     */
    public function run(): void
    {
        $this->command->info('Creating Cashier role (permissions via RefineCashierPermissionsSeeder)...');

        Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);

        $this->createSampleCashier();

        $this->command->info('Cashier role shell ready — run RefineCashierPermissionsSeeder to sync permissions.');
    }

    /**
     * Create a sample cashier user for testing
     */
    private function createSampleCashier(): void
    {
        $cashierRole = Role::where('name', 'cashier')->first();

        $existingCashier = \App\Models\User::where('email', 'cashier@nexthospital.com')->first();
        if ($existingCashier) {
            if ($cashierRole && !$existingCashier->hasRole('cashier')) {
                $existingCashier->assignRole($cashierRole);
            }
            $this->command->info('Sample cashier user already exists');
            return;
        }

        $cashier = \App\Models\User::create([
            'name' => 'Cashier User',
            'email' => 'cashier@nexthospital.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $cashier->assignRole($cashierRole);

        $defaultBranch = \App\Models\Branch::first();
        if ($defaultBranch) {
            $cashier->branches()->syncWithoutDetaching([$defaultBranch->id]);
            if (\App\Models\StaffProfile::where('user_id', $cashier->id)->doesntExist()) {
                \App\Models\StaffProfile::create([
                    'user_id' => $cashier->id,
                    'branch_id' => $defaultBranch->id,
                    'employee_id' => 'EMP-CASHIER',
                    'first_name' => 'Cashier',
                    'last_name' => 'User',
                    'phone' => '+233-123-456-795',
                    'specialization' => 'Cashier / Billing',
                    'online_status' => 'online',
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Sample cashier credentials:');
        $this->command->info('Email: cashier@nexthospital.com');
        $this->command->info('Password: password');
    }
}
