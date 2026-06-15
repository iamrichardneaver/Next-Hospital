<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PatientRegistrationPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions for patient registration management
        $permissions = [
            'view_pending_registrations',
            'approve_patient_registrations',
            'reject_patient_registrations',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // Assign these permissions to specific roles
        $this->assignPermissionsToRoles();

        $this->command->info('Patient registration permissions created and assigned successfully!');
    }

    /**
     * Assign permissions to roles
     */
    private function assignPermissionsToRoles()
    {
        // Super Admin gets all permissions
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo([
                'view_pending_registrations',
                'approve_patient_registrations',
                'reject_patient_registrations',
            ]);
        }

        // Admin gets all permissions
        $admin = Role::where('name', 'Admin')->first();
        if ($admin) {
            $admin->givePermissionTo([
                'view_pending_registrations',
                'approve_patient_registrations',
                'reject_patient_registrations',
            ]);
        }

        // Receptionist can view and approve/reject registrations
        $receptionist = Role::where('name', 'Receptionist')->first();
        if ($receptionist) {
            $receptionist->givePermissionTo([
                'view_pending_registrations',
                'approve_patient_registrations',
                'reject_patient_registrations',
            ]);
        }
    }
}
