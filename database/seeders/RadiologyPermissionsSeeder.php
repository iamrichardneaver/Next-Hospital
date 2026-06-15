<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Creates radiology permission records and assigns staff roles.
 * Doctor grants are owned by RefineDoctorPermissionsSeeder — not assigned here.
 *
 * @see DatabaseSeeder Permission seeding documentation
 */
class RadiologyPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Define all radiology permissions
        $permissions = [
            // Radiology Request Permissions
            'view_radiology_requests',
            'create_radiology_requests',
            'edit_radiology_requests',
            'delete_radiology_requests',
            'process_radiology_requests',
            
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
            'create_radiology_results',
            'edit_radiology_results',
            'delete_radiology_results',
            'upload_radiology_images',
            'view_dicom_viewer',
            'download_radiology_images',
            
            // Quality Control
            'view_radiology_qc',
            'perform_radiology_qc',
            
            // Radiology Management
            'manage_radiology_setup',
            'manage_radiology_results',
            'manage_imaging_modalities',
            'manage_radiology_equipment',
            'manage_radiology_protocols',
            'manage_radiology_technicians',
            
            // Scheduling
            'view_radiology_schedule',
            'manage_radiology_schedule',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all radiology permissions to Super Admin
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $allPermissions = Permission::whereIn('name', $permissions)->get();
            $superAdmin->syncPermissions($superAdmin->permissions->merge($allPermissions)->unique('id'));
            $this->command->info('✅ Super Admin: Radiology permissions assigned');
        }

        // Assign all radiology permissions to Admin
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $allPermissions = Permission::whereIn('name', $permissions)->get();
            $admin->syncPermissions($admin->permissions->merge($allPermissions)->unique('id'));
            $this->command->info('✅ Admin: Radiology permissions assigned');
        }

        // Assign relevant permissions to Radiologist
        $radiologist = Role::firstOrCreate(['name' => 'radiologist']);
        if ($radiologist) {
            $radiologistPermissions = Permission::whereIn('name', $permissions)->get();
            $radiologist->givePermissionTo($radiologistPermissions);
            $this->command->info('✅ Radiologist: Radiology permissions assigned (additive)');
        }

        // Doctor radiology grants: RefineDoctorPermissionsSeeder only (create_radiology_requests, view_radiology_results).

        // Nurse radiology grants: RefineNursePermissionsSeeder (no radiology access for nurses).

        // Assign imaging workflow permissions to Radiology Technician
        $technician = Role::firstOrCreate(['name' => 'radiology_technician']);
        if ($technician) {
            $technicianPermissions = Permission::whereIn('name', [
                'view_radiology_requests',
                'view_radiology_studies',
                'view_radiology_results',
                'view_radiology_reports',
                'create_radiology_studies',
                'edit_radiology_studies',
                'start_radiology_studies',
                'complete_radiology_studies',
                'process_radiology_requests',
                'upload_radiology_images',
                'view_dicom_viewer',
                'download_radiology_images',
                'view_radiology_schedule',
            ])->get();
            $technician->syncPermissions($technician->permissions->merge($technicianPermissions)->unique('id'));
            $this->command->info('✅ Radiology Technician: Imaging workflow permissions assigned');
        }

        $this->command->info('✅ Radiology permissions seeded successfully!');
        $this->command->info('📋 Total permissions: ' . count($permissions));
    }
}

