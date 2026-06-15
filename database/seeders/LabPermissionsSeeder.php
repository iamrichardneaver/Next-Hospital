<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LabPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create lab management permissions
        $permissions = [
            // Lab Requests (existing - keep for compatibility)
            'view_lab_requests',
            'create_lab_requests',
            'edit_lab_requests',
            'delete_lab_requests',
            
            // Lab Results
            'enter_lab_results',
            'verify_lab_results',
            'approve_lab_results',
            'view_lab_results',
            
            // Lab Management Setup
            'manage_lab_setup',        // Master permission for all below
            'manage_lab_categories',
            'manage_lab_templates',
            'manage_lab_tests',
            'manage_lab_parameters',
            'manage_lab_reference_ranges',
            
            // Lab Reports
            'generate_lab_reports',
            'print_lab_results',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        
        // Super Admin gets everything
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // Lab Technician
        $labTech = Role::where('name', 'lab_technician')->first();
        if (!$labTech) {
            $labTech = Role::create(['name' => 'lab_technician']);
        }
        $labTech->givePermissionTo([
            'view_lab_requests',
            'create_lab_requests',
            'edit_lab_requests',
            'enter_lab_results',
            'view_lab_results',
            'print_lab_results',
        ]);

        // Lab Supervisor (can verify and approve)
        $labSupervisor = Role::where('name', 'lab_supervisor')->first();
        if (!$labSupervisor) {
            $labSupervisor = Role::create(['name' => 'lab_supervisor']);
        }
        $labSupervisor->givePermissionTo([
            'view_lab_requests',
            'create_lab_requests',
            'edit_lab_requests',
            'enter_lab_results',
            'verify_lab_results',
            'approve_lab_results',
            'view_lab_results',
            'generate_lab_reports',
            'print_lab_results',
        ]);

        // Lab Manager (can manage setup)
        $labManager = Role::where('name', 'lab_manager')->first();
        if (!$labManager) {
            $labManager = Role::create(['name' => 'lab_manager']);
        }
        $labManager->givePermissionTo([
            'view_lab_requests',
            'create_lab_requests',
            'edit_lab_requests',
            'delete_lab_requests',
            'enter_lab_results',
            'verify_lab_results',
            'approve_lab_results',
            'view_lab_results',
            'manage_lab_setup',
            'manage_lab_categories',
            'manage_lab_templates',
            'manage_lab_tests',
            'manage_lab_parameters',
            'manage_lab_reference_ranges',
            'generate_lab_reports',
            'print_lab_results',
        ]);

        // Doctor (can view and create requests - already assigned in RolePermissionSeeder)
        $doctor = Role::where('name', 'doctor')->first();
        if ($doctor) {
            // Only add lab-specific permissions that aren't already assigned
            $doctor->givePermissionTo([
                'print_lab_results',
            ]);
        }

        // Nurse (can view and create requests - already assigned in RolePermissionSeeder)
        $nurse = Role::where('name', 'nurse')->first();
        if ($nurse) {
            // Nurses don't need additional lab permissions beyond what's in RolePermissionSeeder
        }

        $this->command->info('Lab permissions created and assigned successfully!');
    }
}

