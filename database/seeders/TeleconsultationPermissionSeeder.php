<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TeleconsultationPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create teleconsultation permissions
        $permissions = [
            // Teleconsultation management
            'teleconsultation.view',
            'teleconsultation.create',
            'teleconsultation.edit',
            'teleconsultation.delete',
            'teleconsultation.start',
            'teleconsultation.end',
            'teleconsultation.cancel',
            
            // Teleconsultation chat
            'teleconsultation.chat.view',
            'teleconsultation.chat.send',
            'teleconsultation.chat.edit',
            'teleconsultation.chat.delete',
            
            // Teleconsultation files
            'teleconsultation.files.view',
            'teleconsultation.files.upload',
            'teleconsultation.files.download',
            'teleconsultation.files.delete',
            'teleconsultation.files.consent',
            
            // Teleconsultation statistics
            'teleconsultation.statistics.view',
            
            // Teleconsultation consent
            'teleconsultation.consent.give',
            'teleconsultation.consent.revoke',
            
            // Teleconsultation recording
            'teleconsultation.recording.start',
            'teleconsultation.recording.stop',
            'teleconsultation.recording.download',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    private function assignPermissionsToRoles(): void
    {
        // Super Admin - All permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - All teleconsultation permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'teleconsultation.view',
            'teleconsultation.create',
            'teleconsultation.edit',
            'teleconsultation.delete',
            'teleconsultation.start',
            'teleconsultation.end',
            'teleconsultation.cancel',
            'teleconsultation.chat.view',
            'teleconsultation.chat.send',
            'teleconsultation.chat.edit',
            'teleconsultation.chat.delete',
            'teleconsultation.files.view',
            'teleconsultation.files.upload',
            'teleconsultation.files.download',
            'teleconsultation.files.delete',
            'teleconsultation.files.consent',
            'teleconsultation.statistics.view',
            'teleconsultation.consent.give',
            'teleconsultation.consent.revoke',
            'teleconsultation.recording.start',
            'teleconsultation.recording.stop',
            'teleconsultation.recording.download',
        ]);

        // Doctor teleconsult grants are synced by RefineDoctorPermissionsSeeder (additive grants here are overwritten).
        $doctor = Role::firstOrCreate(['name' => 'doctor']);
        $doctor->givePermissionTo([
            'teleconsultation.view',
            'teleconsultation.create',
            'teleconsultation.edit',
            'teleconsultation.start',
            'teleconsultation.end',
            'teleconsultation.cancel',
            'teleconsultation.chat.view',
            'teleconsultation.chat.send',
            'teleconsultation.chat.edit',
            'teleconsultation.files.view',
            'teleconsultation.files.upload',
            'teleconsultation.files.download',
            'teleconsultation.files.consent',
            'teleconsultation.consent.give',
            'teleconsultation.recording.start',
            'teleconsultation.recording.stop',
        ]);

        // Nurse teleconsultation grants: RefineNursePermissionsSeeder (no teleconsultation access).

        // Receptionist teleconsultation grants: RefineReceptionistPermissionsSeeder (no teleconsultation access).

        // Patient - Limited teleconsultation access
        $patient = Role::firstOrCreate(['name' => 'patient']);
        $patient->givePermissionTo([
            'teleconsultation.view',
            'teleconsultation.chat.view',
            'teleconsultation.chat.send',
            'teleconsultation.files.view',
            'teleconsultation.files.download',
            'teleconsultation.consent.give',
        ]);

        // Lab Technician - Limited access
        $labTechnician = Role::firstOrCreate(['name' => 'lab-technician']);
        $labTechnician->givePermissionTo([
            'teleconsultation.view',
            'teleconsultation.files.view',
            'teleconsultation.files.upload',
        ]);

        // Pharmacist - Limited access
        $pharmacist = Role::firstOrCreate(['name' => 'pharmacist']);
        $pharmacist->givePermissionTo([
            'teleconsultation.view',
            'teleconsultation.files.view',
            'teleconsultation.files.upload',
        ]);
    }
}