<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PatientMobilePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder grants all necessary permissions for patients using the mobile app
     */
    public function run()
    {
        // Find or create patient role (using web guard as per project standard)
        $patientRole = Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);

        // Permissions that patients should have for mobile app
        $patientPermissions = [
            // View own data
            'view_own_appointments',
            'view_own_invoices',
            'view_own_lab_requests',
            'view_own_prescriptions',
            
            // Appointments
            'view_appointments',      // To see available slots, doctors, etc.
            'create_appointments',    // To book appointments
            
            // Dashboard
            'view_dashboard',
            
            // Doctors
            'view_users',            // To see doctor list
            'view_doctor_schedules', // To see doctor availability
            
            // E-commerce / Pharmacy
            'view_store_items',      // To browse pharmacy items
            'view_ecommerce',        // To access e-commerce
            'create_store_orders',   // To place orders
            'view_store_orders',     // To see order history
            
            // Lab
            'view_lab_results',      // To see their test results
            
            // Prescriptions
            'view_prescriptions',    // To see their prescriptions
            
            // Consultations
            'view_consultations',    // To see their consultations
            
            // Invoices & Payments
            'view_invoices',         // To see their bills
            'view_payments',         // To see their payment history
            'create_payments',       // To make payments
            
            // Notifications
            'view_notifications',    // To receive notifications
            
            // Messaging/Chat
            'view_messages',         // To chat with doctors
            'create_messages',       // To send messages
            
            // Emergency
            'view_emergency_visits', // If they have emergency visits
            
            // Files
            'view_files',            // To view their medical documents
            
            // Visits
            'view_visits',           // To see their visit history
            
            // Vitals
            'view_vitals',           // To see their vitals
            
            // Insurance (if applicable)
            'view_insurance',        // To see their insurance info
            'view_insurance_policies', // To see their policies
            
            // Radiology
            'view_radiology_results', // To see imaging results
            'view_radiology_reports', // To see radiology reports
        ];

        // Get all permissions that exist
        $existingPermissions = Permission::whereIn('name', $patientPermissions)->get();

        // Assign permissions to patient role
        foreach ($existingPermissions as $permission) {
            if (!$patientRole->hasPermissionTo($permission)) {
                $patientRole->givePermissionTo($permission);
                $this->command->info("✅ Granted '{$permission->name}' to patient role");
            }
        }

        $this->command->info('✅ Patient mobile permissions seeder completed!');
        $this->command->info('Total permissions granted: ' . $existingPermissions->count());
    }
}

