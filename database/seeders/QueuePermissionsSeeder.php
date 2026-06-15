<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class QueuePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create specific queue permissions
        $queuePermissions = [
            // General queue permissions
            'view_queues',
            'manage_queues',
            'view_queue_statistics',
            'manage_queue_priorities',
            
            // Specific queue type permissions
            'view_opd_queue',
            'manage_opd_queue',
            'view_lab_queue',
            'manage_lab_queue',
            'view_pharmacy_queue',
            'manage_pharmacy_queue',
            'view_emergency_queue',
            'manage_emergency_queue',
            'view_triage_queue',
            'manage_triage_queue',
        ];

        foreach ($queuePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles based on their responsibilities
        
        // Super Admin gets everything
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($queuePermissions);
        }

        // Lab Technician - Only lab queue access
        $labTech = Role::where('name', 'lab_technician')->first();
        if ($labTech) {
            $labTech->givePermissionTo([
                'view_queues',
                'view_lab_queue',
                'manage_lab_queue',
            ]);
        }

        // Lab Supervisor - Lab queue + some general queue access
        $labSupervisor = Role::where('name', 'lab_supervisor')->first();
        if ($labSupervisor) {
            $labSupervisor->givePermissionTo([
                'view_queues',
                'view_lab_queue',
                'manage_lab_queue',
                'view_queue_statistics',
            ]);
        }

        // Lab Manager - Lab queue + general queue management
        $labManager = Role::where('name', 'lab_manager')->first();
        if ($labManager) {
            $labManager->givePermissionTo([
                'view_queues',
                'manage_queues',
                'view_lab_queue',
                'manage_lab_queue',
                'view_queue_statistics',
                'manage_queue_priorities',
            ]);
        }

        // Doctor queue grants are synced by RefineDoctorPermissionsSeeder (additive grants here are overwritten).
        $doctor = Role::where('name', 'doctor')->first();
        if ($doctor) {
            $doctor->givePermissionTo([
                'view_queues',
                'view_opd_queue',
                'manage_opd_queue',
                'view_emergency_queue',
                'manage_emergency_queue',
                'view_triage_queue',
                'manage_triage_queue',
                'view_lab_queue', // Doctors can view lab queue since they order lab tests
                'manage_lab_queue', // Doctors can manage lab queue to start/complete services for their patients
            ]);
        }

        // Nurse queue grants: RefineNursePermissionsSeeder (additive grants here are overwritten).

        // Pharmacist - Pharmacy queue only
        $pharmacist = Role::where('name', 'pharmacist')->first();
        if ($pharmacist) {
            $pharmacist->givePermissionTo([
                'view_queues',
                'view_pharmacy_queue',
                'manage_pharmacy_queue',
            ]);
        }

        // Receptionist queue grants: RefineReceptionistPermissionsSeeder (additive grants here are overwritten).

        // Accountant queue permissions are managed by RefineAccountantPermissionsSeeder for consistency
        $this->command->info('✓ Accountant queue permissions managed by RefineAccountantPermissionsSeeder');

        // Admin - All queue management
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo([
                'view_queues',
                'manage_queues',
                'view_opd_queue',
                'manage_opd_queue',
                'view_lab_queue',
                'manage_lab_queue',
                'view_pharmacy_queue',
                'manage_pharmacy_queue',
                'view_emergency_queue',
                'manage_emergency_queue',
                'view_triage_queue',
                'manage_triage_queue',
                'view_queue_statistics',
                'manage_queue_priorities',
            ]);
        }
    }
}
