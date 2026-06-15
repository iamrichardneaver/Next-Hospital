<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FixLabManagerPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure all lab permissions exist
        $allLabPermissions = [
            // Lab Requests
            'view_lab_requests',
            'create_lab_requests',
            'edit_lab_requests',
            'delete_lab_requests',
            
            // Lab Results
            'enter_lab_results',
            'verify_lab_results',
            'approve_lab_results',
            'view_lab_results',
            'print_lab_results',
            
            // Lab Management Setup
            'manage_lab_setup',
            'manage_lab_categories',
            'manage_lab_templates',
            'manage_lab_tests',
            'manage_lab_parameters',
            'manage_lab_reference_ranges',
            
            // Lab Reports
            'generate_lab_reports',
            
            // Lab Queue
            'view_lab_queue',

            // Lab inventory & supplies purchases
            'view_lab_inventory',
            'view_lab_purchases',
            'create_lab_purchases',
            'receive_lab_purchases',
            'manage_lab_test_consumables',
        ];

        foreach ($allLabPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Essential permissions that all lab staff need
        $essentialPermissions = [
            'view_dashboard',
            'view_patients',
            'search_patients',
            'view_invoices',
            'view_queues',
            'call_patients',
            'serve_patients',
            'view_workflow_dashboard',
            'view_queue_statistics',
        ];

        // Lab Manager - Full access to all lab operations + essential permissions
        $labManager = Role::firstOrCreate(['name' => 'lab_manager']);
        $labManager->givePermissionTo(array_merge($essentialPermissions, $allLabPermissions));
        $this->command->info('Lab Manager: ' . (count($allLabPermissions) + count($essentialPermissions)) . ' permissions assigned');

        // Lab Supervisor - Can verify, approve, and enter results + essential permissions
        $labSupervisor = Role::firstOrCreate(['name' => 'lab_supervisor']);
        $supervisorPermissions = [
            'view_lab_requests',
            'create_lab_requests',
            'edit_lab_requests',
            'enter_lab_results',
            'verify_lab_results',
            'approve_lab_results',
            'view_lab_results',
            'print_lab_results',
            'generate_lab_reports',
            'view_lab_queue',
        ];
        $labSupervisor->givePermissionTo(array_merge($essentialPermissions, $supervisorPermissions));
        $this->command->info('Lab Supervisor: ' . (count($supervisorPermissions) + count($essentialPermissions)) . ' permissions assigned');

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        $this->command->info('Lab Manager and Lab Supervisor permissions fixed successfully!');
        $this->command->info('Please have users log out and log back in for changes to take effect.');
    }
}

