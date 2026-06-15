<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class StandardizeRoleNamesSeeder extends Seeder
{
    /**
     * Standardize all role names to lowercase with underscores
     * Convention: super_admin, lab_technician, store_manager (NOT "Super Admin", "Lab Technician", "Store Manager")
     * 
     * Run: php artisan db:seed --class=StandardizeRoleNamesSeeder
     */
    public function run(): void
    {
        $this->command->info('🔧 Standardizing role names to lowercase_with_underscores convention...');
        
        // Define role name mappings (old name => new name)
        $roleMapping = [
            // E-commerce roles with spaces
            'Store Manager' => 'store_manager',
            'Dispatch Manager' => 'dispatch_manager',
            'Delivery Rider' => 'delivery_rider',
            
            // Potential variations with spaces
            'Super Admin' => 'super_admin',
            'Admin' => 'admin',
            'Lab Technician' => 'lab_technician',
            'Lab Manager' => 'lab_manager',
            'Lab Supervisor' => 'lab_supervisor',
            'Emergency Staff' => 'emergency_staff',
            'Surgery Staff' => 'surgery_staff',
            
            // Already correct format (will be skipped if they don't exist)
            'super admin' => 'super_admin',
            'lab technician' => 'lab_technician',
            'emergency staff' => 'emergency_staff',
            'surgery staff' => 'surgery_staff',
        ];
        
        DB::beginTransaction();
        
        try {
            $changedCount = 0;
            
            foreach ($roleMapping as $oldName => $newName) {
                $oldRole = Role::where('name', $oldName)->first();
                
                if ($oldRole) {
                    // Check if target role already exists
                    $existingNewRole = Role::where('name', $newName)->first();
                    
                    if ($existingNewRole) {
                        $this->command->warn("⚠️  Target role '{$newName}' already exists. Merging '{$oldName}' into it...");
                        
                        // Transfer all permissions from old role to new role
                        $oldPermissions = $oldRole->permissions;
                        foreach ($oldPermissions as $permission) {
                            $existingNewRole->givePermissionTo($permission);
                        }
                        
                        // Transfer all users from old role to new role
                        $usersWithOldRole = $oldRole->users;
                        foreach ($usersWithOldRole as $user) {
                            // Remove old role
                            $user->removeRole($oldRole);
                            // Assign new role if not already assigned
                            if (!$user->hasRole($newName)) {
                                $user->assignRole($newName);
                            }
                        }
                        
                        // Delete old role
                        $oldRole->delete();
                        
                        $this->command->info("✅ Merged '{$oldName}' into '{$newName}'");
                        $changedCount++;
                    } else {
                        // Simply rename the role
                        $oldRole->name = $newName;
                        $oldRole->save();
                        
                        $this->command->info("✅ Renamed '{$oldName}' to '{$newName}'");
                        $changedCount++;
                    }
                }
            }
            
            DB::commit();
            
            if ($changedCount > 0) {
                $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                $this->command->info("✅ Successfully standardized {$changedCount} role name(s)");
            } else {
                $this->command->info("✅ All role names already follow the correct convention!");
            }
            
            // Display current roles
            $this->displayCurrentRoles();
            
        } catch (\Exception $e) {
            DB::rollback();
            $this->command->error("❌ Error standardizing roles: " . $e->getMessage());
        }
    }
    
    /**
     * Display all current roles
     */
    private function displayCurrentRoles(): void
    {
        $this->command->info('');
        $this->command->info('📋 Current Roles (Standardized):');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $roles = Role::orderBy('id')->get();
        
        foreach ($roles as $role) {
            $userCount = $role->users()->count();
            $permCount = $role->permissions()->count();
            
            $status = $this->isCorrectFormat($role->name) ? '✅' : '⚠️';
            $this->command->info("{$status} {$role->name} ({$userCount} users, {$permCount} permissions)");
        }
        
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('✅ = Correct format (lowercase_with_underscores)');
        $this->command->info('⚠️ = Incorrect format (needs standardization)');
    }
    
    /**
     * Check if role name follows correct convention
     */
    private function isCorrectFormat(string $name): bool
    {
        // Correct format: all lowercase, words separated by underscores, no spaces
        return preg_match('/^[a-z]+(_[a-z]+)*$/', $name);
    }
}
