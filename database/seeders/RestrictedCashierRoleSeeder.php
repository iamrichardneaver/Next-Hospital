<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RestrictedCashierRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating RESTRICTED Cashier role with minimal required permissions...');
        
        // Create the restricted Cashier role
        $cashierRole = Role::firstOrCreate(['name' => 'cashier']);
        
        // Define ONLY essential cashier permissions (no admin/settings access)
        $cashierPermissions = [
            // Dashboard access
            'view_dashboard',
            
            // Patient management (minimal - only search and view)
            'view_patients',
            'search_patients',
            
            // Walk-ins management (needed for walk-in patient payments)
            'view_walk_ins',
            
            // Visit management (minimal - only view)
            'view_visits',
            
            // Appointment management (minimal - only view)
            'view_appointments',
            
            // Consultation management (minimal - only view)
            'view_consultations',
            
            // Lab management (minimal - only view)
            'view_lab_requests',
            'view_lab_results',
            
            // Prescription management (minimal - only view)
            'view_prescriptions',
            'view_drugs', // To see drug pricing
            
            // Radiology management (minimal - only view)
            'view_radiology_requests',
            'view_radiology_results',
            
            // Billing and payment permissions (CORE CASHIER FUNCTIONALITY)
            'view_invoices',
            'create_invoices',
            'edit_invoices',
            'view_payments',
            'create_payments',
            'edit_payments',
            'process_payments', // Main cashier permission
            
            // Service pricing (needed to understand service costs)
            'view_service_pricing',
            
            // Insurance management (minimal - only view and basic processing)
            'view_insurance',
            'view_insurance_providers',
            'view_insurance_policies',
            'view_insurance_claims',
            'create_insurance_claims',
            'edit_insurance_claims',
            'calculate_insurance_coverage',
            'process_insurance_claims',
            
            // Debtor management (minimal - only view)
            'view_debtors',
            
            // Queue management (minimal - only view)
            'view_queues',
            'view_lab_queue', // Specific queue permissions
            'view_opd_queue',
            'view_pharmacy_queue',
            
            // Emergency management (minimal - only view)
            'view_emergency_visits',
            
            // Reports (minimal - only view basic reports)
            'view_reports',
            
            // Store management (minimal - only view for e-commerce payments)
            'view_store_items',
            'view_store_orders',
            
            // Branch management (minimal - only view own branch)
            'view_branches',
            
            // NO USER MANAGEMENT PERMISSIONS
            // NO SETTINGS PERMISSIONS
            // NO ADMIN PERMISSIONS
        ];
        
        // Create any missing permissions first
        foreach ($cashierPermissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
        
        // Assign ONLY the restricted permissions to the cashier role
        $cashierRole->syncPermissions($cashierPermissions);
        
        $this->command->info('✓ RESTRICTED Cashier role updated with ' . count($cashierPermissions) . ' permissions');
        $this->command->info('✓ Removed admin/settings permissions from cashier role');
        
        $this->command->info('Restricted Cashier role setup completed successfully!');
    }
}
