<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EmergencyAlertPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create emergency alert permissions
        $permissions = [
            'view_emergency_alerts',
            'create_emergency_alerts',
            'edit_emergency_alerts',
            'delete_emergency_alerts',
            'acknowledge_emergency_alerts',
            'resolve_emergency_alerts',
            'view_emergency_visits',
            'create_emergency_visits',
            'edit_emergency_visits',
            'delete_emergency_visits',
            'triage_patients',
            'call_patients',
            'serve_patients',
            'acknowledge_alerts',
            'resolve_alerts'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    private function assignPermissionsToRoles(): void
    {
        // Super Admin gets all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin gets most permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $adminPermissions = [
            'view_emergency_alerts',
            'create_emergency_alerts',
            'edit_emergency_alerts',
            'delete_emergency_alerts',
            'acknowledge_emergency_alerts',
            'resolve_emergency_alerts',
            'view_emergency_visits',
            'create_emergency_visits',
            'edit_emergency_visits',
            'delete_emergency_visits',
            'triage_patients',
            'call_patients',
            'serve_patients',
            'acknowledge_alerts',
            'resolve_alerts'
        ];
        $admin->givePermissionTo($adminPermissions);

        // Doctor gets emergency-related permissions
        $doctor = Role::firstOrCreate(['name' => 'doctor']);
        $doctorPermissions = [
            'view_emergency_alerts',
            'create_emergency_alerts',
            'acknowledge_emergency_alerts',
            'resolve_emergency_alerts',
            'view_emergency_visits',
            'create_emergency_visits',
            'edit_emergency_visits',
            'triage_patients',
            'call_patients',
            'serve_patients',
            'acknowledge_alerts',
            'resolve_alerts'
        ];
        $doctor->givePermissionTo($doctorPermissions);

        // Nurse gets emergency care permissions
        $nurse = Role::firstOrCreate(['name' => 'nurse']);
        $nursePermissions = [
            'view_emergency_alerts',
            'create_emergency_alerts',
            'acknowledge_emergency_alerts',
            'view_emergency_visits',
            'create_emergency_visits',
            'triage_patients',
            'call_patients',
            'serve_patients',
            'acknowledge_alerts'
        ];
        $nurse->givePermissionTo($nursePermissions);

        // Receptionist gets basic emergency permissions
        $receptionist = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionistPermissions = [
            'view_emergency_alerts',
            'create_emergency_alerts',
            'view_emergency_visits',
            'create_emergency_visits',
            'call_patients'
        ];
        $receptionist->givePermissionTo($receptionistPermissions);

        // Emergency Staff gets specialized permissions
        $emergencyStaff = Role::firstOrCreate(['name' => 'emergency_staff']);
        $emergencyStaffPermissions = [
            'view_emergency_alerts',
            'create_emergency_alerts',
            'acknowledge_emergency_alerts',
            'resolve_emergency_alerts',
            'view_emergency_visits',
            'create_emergency_visits',
            'edit_emergency_visits',
            'triage_patients',
            'call_patients',
            'serve_patients',
            'acknowledge_alerts',
            'resolve_alerts'
        ];
        $emergencyStaff->givePermissionTo($emergencyStaffPermissions);
    }
}
