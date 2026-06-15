<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * @deprecated Role-permission grants for doctor and pharmacist are maintained by
 *             RefineDoctorPermissionsSeeder and RefinePharmacistPermissionsSeeder.
 *             This seeder only creates permission records and assigns non-refined roles.
 *             Do not re-run on production after Refine* seeders without expecting overwrites.
 *
 * @see DatabaseSeeder Permission seeding documentation
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Dashboard
            'view_dashboard',
            
            // Patient Management
            'view_patients',
            'create_patients',
            'edit_patients',
            'delete_patients',
            'search_patients',
            
            // Appointment Management
            'view_appointments',
            'create_appointments',
            'edit_appointments',
            'delete_appointments',
            'manage_appointments',
            
            // Doctor Schedules
            'view_doctor_schedules',
            'create_doctor_schedules',
            'edit_doctor_schedules',
            'delete_doctor_schedules',
            
            // Appointment Slots
            'view_appointment_slots',
            'create_appointment_slots',
            'edit_appointment_slots',
            'delete_appointment_slots',
            
            // Appointment Fees
            'view_appointment_fees',
            'create_appointment_fees',
            'edit_appointment_fees',
            'delete_appointment_fees',
            
            // Consultation Management
            'view_consultations',
            'create_consultations',
            'edit_consultations',
            'delete_consultations',
            'manage_consultations',
            
            // Pharmacy Management
            'view_drugs',
            'create_drugs',
            'edit_drugs',
            'delete_drugs',
            'manage_inventory',
            'dispense_drugs',
            
            // Prescriptions
            'view_prescriptions',
            'create_prescriptions',
            'edit_prescriptions',
            'delete_prescriptions',
            
            // Lab Management
            'view_lab_requests',
            'create_lab_requests',
            'edit_lab_requests',
            'delete_lab_requests',
            'manage_lab_results',
            
            // Radiology Management
            'view_radiology_requests',
            'create_radiology_requests',
            'edit_radiology_requests',
            'delete_radiology_requests',
            'manage_radiology_results',
            'view_radiology_results',
            
            // Ward Management
            'view_wards',
            'create_wards',
            'edit_wards',
            'delete_wards',
            'manage_wards',
            
            // Billing Management
            'view_invoices',
            'create_invoices',
            'edit_invoices',
            'delete_invoices',
            'process_payments',
            'view_financial_reports',
            
            // Pricing Management
            'view_service_pricing',
            'create_service_pricing',
            'edit_service_pricing',
            'delete_service_pricing',
            
            // Revenue Analytics
            'view_revenue_analytics',
            'export_revenue_analytics',
            
            // Insurance Management
            'view_insurance',
            'create_insurance',
            'edit_insurance',
            'delete_insurance',
            'view_insurance_providers',
            'create_insurance_providers',
            'edit_insurance_providers',
            'delete_insurance_providers',
            'view_insurance_policies',
            'create_insurance_policies',
            'edit_insurance_policies',
            'delete_insurance_policies',
            'view_insurance_claims',
            'create_insurance_claims',
            'edit_insurance_claims',
            'delete_insurance_claims',
            
            // User Management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'manage_roles',
            'manage_permissions',
            
            // Branch Management
            'view_branches',
            'create_branches',
            'edit_branches',
            'delete_branches',
            'manage_branches',
            
            // Reports
            'view_reports',
            'generate_reports',
            'export_data',
            
            // System Administration & Settings
            'view_settings',
            'view_system_settings',
            'manage_system_settings',
            'view_audit_logs',
            'manage_backups',
            
            // Visit Management
            'view_visits',
            'create_visits',
            'edit_visits',
            'delete_visits',
            'manage_visits',
            
            // Queue Management
            'view_queues',
            'manage_queues',
            'call_patients',
            'serve_patients',
            'complete_services',
            
            // Emergency Management
            'view_emergency',
            'manage_emergency',
            'view_emergency_visits',
            'create_emergency_visits',
            'edit_emergency_visits',
            'delete_emergency_visits',
            'triage_patients',
            'acknowledge_alerts',
            'resolve_alerts',
            
            // Surgery Management
            'view_surgery_schedules',
            'create_surgery_schedules',
            'edit_surgery_schedules',
            'delete_surgery_schedules',
            
            // E-Commerce/Store
            'view_store_items',
            'create_store_items',
            'edit_store_items',
            'delete_store_items',
            'manage_store_orders',
            
            // Inventory Management
            'view_inventory',
            'create_inventory',
            'edit_inventory',
            'delete_inventory',
            'manage_inventory_stock',
            
            // Complaints Management
            'view_complaints',
            'create_complaints',
            'edit_complaints',
            'delete_complaints',
            'resolve_complaints',
            
            // Workflow Dashboard
            'view_workflow_dashboard',
            'manage_workflow_settings',
            'view_queue_statistics',
            'manage_queue_priorities',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'view_dashboard',
            'view_patients', 'create_patients', 'edit_patients', 'delete_patients', 'search_patients',
            'view_appointments', 'create_appointments', 'edit_appointments', 'delete_appointments', 'manage_appointments',
            'view_doctor_schedules', 'create_doctor_schedules', 'edit_doctor_schedules', 'delete_doctor_schedules',
            'view_appointment_slots', 'create_appointment_slots', 'edit_appointment_slots', 'delete_appointment_slots',
            'view_appointment_fees', 'create_appointment_fees', 'edit_appointment_fees', 'delete_appointment_fees',
            'view_consultations', 'create_consultations', 'edit_consultations', 'delete_consultations', 'manage_consultations',
            'view_drugs', 'create_drugs', 'edit_drugs', 'delete_drugs', 'manage_inventory', 'dispense_drugs',
            'view_prescriptions', 'create_prescriptions', 'edit_prescriptions', 'delete_prescriptions',
            'view_lab_requests', 'create_lab_requests', 'edit_lab_requests', 'delete_lab_requests', 'manage_lab_results',
            'view_radiology_requests', 'create_radiology_requests', 'edit_radiology_requests', 'delete_radiology_requests', 'manage_radiology_results',
            'view_wards', 'create_wards', 'edit_wards', 'delete_wards', 'manage_wards',
            'view_invoices', 'create_invoices', 'edit_invoices', 'delete_invoices', 'process_payments', 'view_financial_reports',
            'view_service_pricing', 'create_service_pricing', 'edit_service_pricing', 'delete_service_pricing',
            'view_revenue_analytics', 'export_revenue_analytics',
            'view_insurance', 'create_insurance', 'edit_insurance', 'delete_insurance',
            'view_insurance_providers', 'create_insurance_providers', 'edit_insurance_providers', 'delete_insurance_providers',
            'view_insurance_policies', 'create_insurance_policies', 'edit_insurance_policies', 'delete_insurance_policies',
            'view_insurance_claims', 'create_insurance_claims', 'edit_insurance_claims', 'delete_insurance_claims',
            'view_users', 'create_users', 'edit_users', 'delete_users',
            'view_branches', 'create_branches', 'edit_branches', 'delete_branches', 'manage_branches',
            'view_reports', 'generate_reports', 'export_data',
            'view_settings', 'view_system_settings', 'manage_system_settings', 'view_audit_logs', 'manage_backups',
            'view_visits', 'create_visits', 'edit_visits', 'delete_visits', 'manage_visits',
            'view_queues', 'manage_queues', 'call_patients', 'serve_patients', 'complete_services',
            'view_emergency', 'manage_emergency', 'view_emergency_visits', 'create_emergency_visits', 'edit_emergency_visits', 'delete_emergency_visits',
            'triage_patients', 'acknowledge_alerts', 'resolve_alerts',
            'view_surgery_schedules', 'create_surgery_schedules', 'edit_surgery_schedules', 'delete_surgery_schedules',
            'view_store_items', 'create_store_items', 'edit_store_items', 'delete_store_items', 'manage_store_orders',
            'view_inventory', 'create_inventory', 'edit_inventory', 'delete_inventory', 'manage_inventory_stock',
            'view_complaints', 'create_complaints', 'edit_complaints', 'delete_complaints', 'resolve_complaints',
            'view_workflow_dashboard', 'manage_workflow_settings', 'view_queue_statistics', 'manage_queue_priorities',
        ]);

        // Doctor: canonical list in RefineDoctorPermissionsSeeder — do not assign here.
        Role::firstOrCreate(['name' => 'doctor']);

        // Nurse: canonical list in RefineNursePermissionsSeeder — do not assign here.
        Role::firstOrCreate(['name' => 'nurse']);

        // Pharmacist: canonical list in RefinePharmacistPermissionsSeeder — do not assign here.
        Role::firstOrCreate(['name' => 'pharmacist']);

        // Receptionist: canonical list in RefineReceptionistPermissionsSeeder — do not assign here.
        Role::firstOrCreate(['name' => 'receptionist']);

        $labTechnician = Role::firstOrCreate(['name' => 'lab_technician']);
        $labTechnician->givePermissionTo([
            'view_dashboard',
            'view_patients', 'search_patients',
            'view_lab_requests', 'create_lab_requests', 'edit_lab_requests', 'manage_lab_results',
            'enter_lab_results', 'view_lab_results', 'print_lab_results',
            'view_invoices',
            'view_queues', 'call_patients', 'serve_patients',
            'view_workflow_dashboard', 'view_queue_statistics',
        ]);
        
        // Create lab supervisor role (can verify and approve results)
        $labSupervisor = Role::firstOrCreate(['name' => 'lab_supervisor']);
        $labSupervisor->givePermissionTo([
            'view_dashboard',
            'view_patients', 'search_patients',
            'view_lab_requests', 'create_lab_requests', 'edit_lab_requests', 'manage_lab_results',
            'enter_lab_results', 'verify_lab_results', 'approve_lab_results',
            'view_lab_results', 'print_lab_results', 'generate_lab_reports',
            'view_invoices',
            'view_queues', 'call_patients', 'serve_patients',
            'view_workflow_dashboard', 'view_queue_statistics',
        ]);

        // Create lab manager role (can manage lab setup)
        $labManager = Role::firstOrCreate(['name' => 'lab_manager']);
        $labManager->givePermissionTo([
            'view_dashboard',
            'view_patients', 'search_patients',
            'view_lab_requests', 'create_lab_requests', 'edit_lab_requests', 'delete_lab_requests',
            'manage_lab_results', 'enter_lab_results', 'verify_lab_results', 'approve_lab_results',
            'view_lab_results', 'print_lab_results', 'generate_lab_reports',
            'manage_lab_setup', 'manage_lab_categories', 'manage_lab_templates',
            'manage_lab_tests', 'manage_lab_parameters', 'manage_lab_reference_ranges',
            'view_invoices',
            'view_queues', 'call_patients', 'serve_patients',
            'view_workflow_dashboard', 'view_queue_statistics',
        ]);

        // Create accountant role (permissions will be set by RefineAccountantPermissionsSeeder)
        $accountant = Role::firstOrCreate(['name' => 'accountant']);
        // Note: Accountant permissions are managed by RefineAccountantPermissionsSeeder for consistency

        // Create cashier role (permissions will be set by RefineCashierPermissionsSeeder)
        Role::firstOrCreate(['name' => 'cashier']);
        // Note: Cashier permissions are managed by RefineCashierPermissionsSeeder for consistency

        // Create patient role with limited permissions (web portal; see RefinePatientPortalPermissionsSeeder)
        $patient = Role::firstOrCreate(['name' => 'patient']);
        $patient->syncPermissions([
            'view_dashboard',
            'view_appointments',
            'create_appointments',
            'view_invoices',
            'view_prescriptions',
            'view_consultations',
            'view_vitals',
            'view_complaints',
            'create_complaints',
        ]);
    }
}