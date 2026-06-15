<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RadiologistRoleSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create radiologist role
        $radiologist = Role::firstOrCreate(['name' => 'radiologist']);
        
        // Define radiologist permissions
        $permissions = [
            // Radiology Request Permissions
            'view_radiology_requests',
            'create_radiology_requests',
            'edit_radiology_requests',
            'delete_radiology_requests',
            
            // Radiology Study Permissions
            'view_radiology_studies',
            'create_radiology_studies',
            'edit_radiology_studies',
            'start_radiology_studies',
            'complete_radiology_studies',
            'cancel_radiology_studies',
            
            // Radiology Report Permissions
            'view_radiology_reports',
            'create_radiology_reports',
            'edit_radiology_reports',
            'sign_radiology_reports',
            'amend_radiology_reports',
            'generate_radiology_reports',
            
            // Radiology Results/Images Permissions
            'view_radiology_results',
            'upload_radiology_images',
            'view_dicom_viewer',
            'download_radiology_images',
            
            // Quality Control
            'view_radiology_qc',
            'perform_radiology_qc',
            
            // Radiology Management (for senior radiologists)
            'manage_radiology_setup',
            'manage_imaging_modalities',
            'manage_radiology_equipment',
            'manage_radiology_protocols',
            'manage_radiology_technicians',
            
            // Scheduling
            'view_radiology_schedule',
            'manage_radiology_schedule',
            
            // Patient Access
            'view_patients',
            'view_patient_history',
            
            // Billing (view only)
            'view_invoices',
            
            // Dashboard
            'view_dashboard',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all radiology permissions to radiologist role
        $radiologistPermissions = Permission::whereIn('name', $permissions)->get();
        $radiologist->syncPermissions($radiologistPermissions);

        $this->command->info('✅ Radiologist role and permissions created successfully!');
        $this->command->info('📋 Total permissions assigned: ' . $radiologistPermissions->count());
    }
}
