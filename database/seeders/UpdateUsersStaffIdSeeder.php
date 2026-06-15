<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Services\IdPrefixService;

class UpdateUsersStaffIdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $idPrefixService = app(IdPrefixService::class);
        
        // Get all users that don't have staff_id set
        $users = User::whereNull('staff_id')->get();
        
        foreach ($users as $user) {
            // Check if user has staff roles
            if ($user->hasRole(['admin', 'doctor', 'nurse', 'pharmacist', 'lab_technician', 'receptionist', 'accountant', 'super_admin'])) {
                // Generate staff_id using IdPrefixService
                $staffId = $idPrefixService->generateId('staff');
                
                // Update the user with the generated staff_id
                $user->update(['staff_id' => $staffId]);
                
                $this->command->info("Updated user {$user->name} ({$user->email}) with staff_id: {$staffId}");
            } else {
                $this->command->info("Skipped user {$user->name} ({$user->email}) - not a staff member");
            }
        }
        
        $this->command->info("Staff ID update completed!");
    }
}