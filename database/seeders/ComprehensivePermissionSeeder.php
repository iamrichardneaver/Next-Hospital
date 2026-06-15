<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * @deprecated Prefer RolePermissionSeeder for permission records plus Refine* seeders for role sync.
 *             Running assignRolePermissions() after RefineDoctorPermissionsSeeder or
 *             RefinePharmacistPermissionsSeeder will overwrite curated clinical/pharmacy grants.
 *
 * @see DatabaseSeeder Permission seeding documentation
 */
class ComprehensivePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating comprehensive permissions...');
        
        // Create all permissions
        $this->createPermissions();
        
        // Assign permissions to roles
        $this->assignRolePermissions();
        
        $this->command->info('Comprehensive permission seeding completed!');
    }
    
    private function createPermissions()
    {
        $permissions = [
            // Dashboard
            'view_dashboard',
            
            // Patient Management
            'view_patients', 'create_patients', 'edit_patients', 'delete_patients', 'search_patients',
            'view_own_appointments', 'view_own_invoices', 'view_own_prescriptions', 'view_own_lab_requests',
            
            // Appointment Management
            'view_appointments', 'create_appointments', 'edit_appointments', 'delete_appointments', 'manage_appointments',
            'view_doctor_schedules', 'create_doctor_schedules', 'edit_doctor_schedules', 'delete_doctor_schedules',
            'view_appointment_slots', 'create_appointment_slots', 'edit_appointment_slots', 'delete_appointment_slots',
            'view_appointment_fees', 'create_appointment_fees', 'edit_appointment_fees', 'delete_appointment_fees',
            
            // Consultation Management
            'view_consultations', 'create_consultations', 'edit_consultations', 'delete_consultations', 'manage_consultations',
            
            // Pharmacy Management
            'view_drugs', 'create_drugs', 'edit_drugs', 'delete_drugs', 'manage_inventory', 'dispense_drugs',
            'view_prescriptions', 'create_prescriptions', 'edit_prescriptions', 'delete_prescriptions',
            
            // Laboratory Management
            'view_lab_requests', 'create_lab_requests', 'edit_lab_requests', 'delete_lab_requests',
            'enter_lab_results', 'verify_lab_results', 'approve_lab_results', 'view_lab_results', 'print_lab_results',
            'manage_lab_setup', 'manage_lab_categories', 'manage_lab_templates', 'manage_lab_tests',
            'manage_lab_parameters', 'manage_lab_reference_ranges', 'generate_lab_reports',
            
            // Radiology Management
            'view_radiology_requests', 'create_radiology_requests', 'edit_radiology_requests', 'delete_radiology_requests',
            'view_radiology_results', 'create_radiology_results', 'edit_radiology_results', 'delete_radiology_results',
            'manage_radiology_setup', 'generate_radiology_reports',
            
            // Ward & Bed Management
            'view_wards', 'create_wards', 'edit_wards', 'delete_wards', 'manage_wards',
            'view_beds', 'create_beds', 'edit_beds', 'delete_beds', 'manage_beds',
            
            // Visit Management
            'view_visits', 'create_visits', 'edit_visits', 'delete_visits', 'manage_visits',
            
            // Queue Management
            'view_queues', 'manage_queues', 'call_patients', 'serve_patients', 'complete_services',
            
            // Emergency Management
            'view_emergency', 'create_emergency', 'edit_emergency', 'delete_emergency',
            'view_emergency_visits', 'create_emergency_visits', 'edit_emergency_visits', 'delete_emergency_visits',
            'view_emergency_alerts', 'create_emergency_alerts', 'edit_emergency_alerts', 'delete_emergency_alerts',
            'acknowledge_alerts', 'resolve_alerts', 'triage_patients',
            
            // Surgery Management
            'view_surgery', 'create_surgery', 'edit_surgery', 'delete_surgery',
            'view_surgery_schedules', 'create_surgery_schedules', 'edit_surgery_schedules', 'delete_surgery_schedules',
            
            // Billing & Financial Management
            'view_invoices', 'create_invoices', 'edit_invoices', 'delete_invoices',
            'view_payments', 'create_payments', 'edit_payments', 'delete_payments',
            'view_debtors', 'create_debtors', 'edit_debtors', 'delete_debtors',
            'view_revenue_analytics', 'export_revenue_analytics',
            'view_service_pricing', 'create_service_pricing', 'edit_service_pricing', 'delete_service_pricing',
            
            // Insurance Management
            'view_insurance', 'create_insurance', 'edit_insurance', 'delete_insurance',
            'view_insurance_providers', 'create_insurance_providers', 'edit_insurance_providers', 'delete_insurance_providers',
            'view_insurance_policies', 'create_insurance_policies', 'edit_insurance_policies', 'delete_insurance_policies',
            'view_insurance_claims', 'create_insurance_claims', 'edit_insurance_claims', 'delete_insurance_claims',
            'view_pre_authorizations', 'create_pre_authorizations', 'edit_pre_authorizations', 'delete_pre_authorizations',
            'view_coverage_policies', 'create_coverage_policies', 'edit_coverage_policies', 'delete_coverage_policies',
            'calculate_insurance_coverage', 'process_insurance_claims', 'manage_insurance_reports',
            
            // User & Role Management
            'view_users', 'create_users', 'edit_users', 'delete_users',
            'manage_roles', 'manage_permissions',
            
            // Branch Management
            'view_branches', 'create_branches', 'edit_branches', 'delete_branches', 'manage_branches',
            
            // Notification Management
            'view_notifications', 'create_notifications', 'edit_notifications', 'delete_notifications',
            
            // Settings Management
            'view_settings', 'view_system_settings', 'manage_system_settings', 'view_audit_logs', 'manage_backups',
            'manage_data_cleanup',
            
            // Walk-ins Management
            'view_walk_ins', 'create_walk_ins', 'edit_walk_ins', 'delete_walk_ins', 'export_walk_ins',
            
            // E-Commerce Management
            'view_store_items', 'create_store_items', 'edit_store_items', 'delete_store_items', 'manage_store_orders',
            
            // Teleconsultation Management
            'teleconsultation.view', 'teleconsultation.create', 'teleconsultation.edit', 'teleconsultation.delete',
            
            // Reports
            'view_reports', 'generate_reports', 'export_reports',
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        
        $this->command->info('Created ' . count($permissions) . ' permissions');
    }
    
    private function assignRolePermissions()
    {
        // Super Admin - Gets ALL permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $allPermissions = Permission::all();
        $superAdmin->syncPermissions($allPermissions);
        $this->command->info('Super Admin: ' . $superAdmin->permissions->count() . ' permissions');
        
        // Admin - Almost all permissions except some super admin exclusives
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $adminPermissions = Permission::whereNotIn('name', ['manage_data_cleanup'])->get();
        $admin->syncPermissions($adminPermissions);
        $this->command->info('Admin: ' . $admin->permissions->count() . ' permissions');
        
        // Doctor: use RefineDoctorPermissionsSeeder — skipped here to avoid conflicting grants.
        Role::firstOrCreate(['name' => 'doctor']);
        $this->command->info('Doctor: skipped (use RefineDoctorPermissionsSeeder)');

        // Nurse: use RefineNursePermissionsSeeder — skipped here to avoid conflicting grants.
        Role::firstOrCreate(['name' => 'nurse']);
        $this->command->info('Nurse: skipped (use RefineNursePermissionsSeeder)');
        
        // Pharmacist: use RefinePharmacistPermissionsSeeder — skipped here to avoid conflicting grants.
        Role::firstOrCreate(['name' => 'pharmacist']);
        $this->command->info('Pharmacist: skipped (use RefinePharmacistPermissionsSeeder)');

        // Lab Technician - Lab results processing
        $labTechnician = Role::firstOrCreate(['name' => 'lab_technician']);
        $labTechnicianPermissions = Permission::whereIn('name', [
            'view_dashboard', 'view_lab_requests', 'create_lab_requests', 'edit_lab_requests',
            'enter_lab_results', 'view_lab_results', 'print_lab_results',
            'view_patients', 'search_patients', 'view_queues', 'call_patients', 'serve_patients'
        ])->get();
        $labTechnician->syncPermissions($labTechnicianPermissions);
        $this->command->info('Lab Technician: ' . $labTechnician->permissions->count() . ' permissions');
        
        // Lab Supervisor - Lab quality control
        $labSupervisor = Role::firstOrCreate(['name' => 'lab_supervisor']);
        $labSupervisorPermissions = Permission::whereIn('name', [
            'view_dashboard', 'view_lab_requests', 'create_lab_requests', 'edit_lab_requests',
            'enter_lab_results', 'verify_lab_results', 'approve_lab_results', 'view_lab_results', 'print_lab_results',
            'view_patients', 'search_patients', 'view_queues', 'call_patients', 'serve_patients',
            'generate_lab_reports'
        ])->get();
        $labSupervisor->syncPermissions($labSupervisorPermissions);
        $this->command->info('Lab Supervisor: ' . $labSupervisor->permissions->count() . ' permissions');
        
        // Lab Manager - Lab administration
        $labManager = Role::firstOrCreate(['name' => 'lab_manager']);
        $labManagerPermissions = Permission::whereIn('name', [
            'view_dashboard', 'view_lab_requests', 'create_lab_requests', 'edit_lab_requests', 'delete_lab_requests',
            'enter_lab_results', 'verify_lab_results', 'approve_lab_results', 'view_lab_results', 'print_lab_results',
            'manage_lab_setup', 'manage_lab_categories', 'manage_lab_templates', 'manage_lab_tests',
            'manage_lab_parameters', 'manage_lab_reference_ranges', 'generate_lab_reports',
            'view_patients', 'search_patients', 'view_queues', 'call_patients', 'serve_patients'
        ])->get();
        $labManager->syncPermissions($labManagerPermissions);
        $this->command->info('Lab Manager: ' . $labManager->permissions->count() . ' permissions');
        
        // Receptionist: use RefineReceptionistPermissionsSeeder — skipped here to avoid conflicting grants.
        Role::firstOrCreate(['name' => 'receptionist']);
        $this->command->info('Receptionist: skipped (use RefineReceptionistPermissionsSeeder)');
        
        // Accountant - Financial management (permissions managed by RefineAccountantPermissionsSeeder)
        $accountant = Role::firstOrCreate(['name' => 'accountant']);
        // Note: Accountant permissions are managed by RefineAccountantPermissionsSeeder for consistency
        $this->command->info('Accountant: Permissions managed by RefineAccountantPermissionsSeeder');
        
        // Emergency Staff - Emergency care
        $emergencyStaff = Role::firstOrCreate(['name' => 'emergency_staff']);
        $emergencyStaffPermissions = Permission::whereIn('name', [
            'view_dashboard', 'view_patients', 'search_patients', 'create_patients',
            'view_emergency_visits', 'create_emergency_visits', 'edit_emergency_visits', 'delete_emergency_visits',
            'view_emergency_alerts', 'acknowledge_alerts', 'resolve_alerts',
            'view_consultations', 'create_consultations', 'edit_consultations',
            'view_lab_requests', 'create_lab_requests', 'view_lab_results',
            'view_radiology_requests', 'create_radiology_requests', 'view_radiology_results',
            'view_wards', 'view_visits', 'create_visits', 'edit_visits',
            'view_queues', 'call_patients', 'serve_patients', 'complete_services',
            'view_invoices', 'create_invoices', 'view_prescriptions', 'create_prescriptions'
        ])->get();
        $emergencyStaff->syncPermissions($emergencyStaffPermissions);
        $this->command->info('Emergency Staff: ' . $emergencyStaff->permissions->count() . ' permissions');
        
        // Surgery Staff - Surgery support
        $surgeryStaff = Role::firstOrCreate(['name' => 'surgery_staff']);
        $surgeryStaffPermissions = Permission::whereIn('name', [
            'view_dashboard', 'view_patients', 'search_patients',
            'view_surgery_schedules', 'create_surgery_schedules', 'edit_surgery_schedules', 'delete_surgery_schedules',
            'view_consultations', 'view_lab_requests', 'view_lab_results',
            'view_radiology_requests', 'view_radiology_results',
            'view_wards', 'view_visits', 'create_visits', 'edit_visits',
            'view_queues', 'call_patients', 'serve_patients',
            'view_invoices', 'view_prescriptions'
        ])->get();
        $surgeryStaff->syncPermissions($surgeryStaffPermissions);
        $this->command->info('Surgery Staff: ' . $surgeryStaff->permissions->count() . ' permissions');
        
        // Patient - Self-service access
        $patient = Role::firstOrCreate(['name' => 'patient']);
        $patientPermissions = Permission::whereIn('name', [
            'view_dashboard', 'view_own_appointments', 'create_appointments', 'view_own_invoices',
            'view_own_prescriptions', 'view_own_lab_requests'
        ])->get();
        $patient->syncPermissions($patientPermissions);
        $this->command->info('Patient: ' . $patient->permissions->count() . ' permissions');
    }
}
