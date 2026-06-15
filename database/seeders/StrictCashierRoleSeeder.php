<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class StrictCashierRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder creates a STRICTLY LIMITED cashier role with:
     * - Full access to cashier module only
     * - Read-only access to patient information (no edit/delete)
     * - Read-only access to billing, revenue, and financial reports
     * - NO access to any other modules
     */
    public function run(): void
    {
        $this->command->info('Creating STRICTLY LIMITED Cashier role...');
        
        // Get or create the Cashier role
        $cashierRole = Role::firstOrCreate(['name' => 'cashier']);
        
        // Define ONLY the absolutely essential cashier permissions
        $cashierPermissions = [
            // ========================================
            // DASHBOARD ACCESS (Minimal)
            // ========================================
            'view_dashboard',
            
            // ========================================
            // PATIENT MANAGEMENT (READ-ONLY - No edit/delete)
            // ========================================
            'view_patients',          // View patient details
            'search_patients',        // Search for patients
            // NO create_patients
            // NO edit_patients
            // NO delete_patients
            
            // ========================================
            // CASHIER MODULE (FULL ACCESS)
            // ========================================
            'view_invoices',
            'create_invoices',
            'edit_invoices',
            'view_payments',
            'create_payments',
            'edit_payments',
            'process_payments',       // Core cashier permission
            'generate_receipts',
            
            // ========================================
            // BILLING & REVENUE (READ-ONLY)
            // ========================================
            'view_service_pricing',   // View pricing information
            'view_revenue_analytics', // View revenue reports
            'view_debtors',           // View debtor list
            // NO edit_debtors
            // NO delete_debtors
            
            // ========================================
            // FINANCIAL REPORTS (READ-ONLY)
            // ========================================
            'view_reports',           // View financial reports
            'view_revenue_reports',   // Specific revenue reports
            'view_payment_reports',   // Payment history reports
            
            // ========================================
            // INSURANCE (READ-ONLY for billing context)
            // ========================================
            'view_insurance',
            'view_insurance_providers',
            'view_insurance_policies',
            'view_insurance_claims',
            'create_insurance_claims', // Need to create claims for patient payments
            'calculate_insurance_coverage',
            
            // ========================================
            // NO MODULE SIDEBAR ACCESS
            // Cashier should ONLY access billing information through the cashier module
            // All billing context is available within the cashier payment interface
            // ========================================
            // Removed: view_visits, view_consultations, view_lab_requests, view_prescriptions, view_drugs
            // These permissions were making entire modules visible in sidebar
            
            // NO ACCESS TO:
            // - User management
            // - Settings
            // - Patient registration/editing/deletion
            // - Clinical modules (beyond viewing for billing)
            // - Lab management
            // - Pharmacy management
            // - Appointment management (beyond viewing)
            // - Ward management
            // - Surgery management
            // - Radiology management
            // - Queue management (beyond viewing)
            // - Any administrative functions
        ];
        
        // Create any missing permissions first
        foreach ($cashierPermissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
        
        // Sync permissions (this will remove all other permissions)
        $cashierRole->syncPermissions($cashierPermissions);
        
        $this->command->info('✓ STRICTLY LIMITED Cashier role created/updated with ' . count($cashierPermissions) . ' permissions (was 28, now ' . count($cashierPermissions) . ')');
        $this->command->info('✓ Cashier can now ONLY:');
        $this->command->info('  - Access cashier module (full)');
        $this->command->info('  - View patient information (read-only)');
        $this->command->info('  - View billing and revenue reports (read-only)');
        $this->command->info('  - Process payments and generate receipts');
        $this->command->info('✗ Cashier CANNOT:');
        $this->command->info('  - Edit or delete patient information');
        $this->command->info('  - Access any other modules');
        $this->command->info('  - Manage users or settings');
        $this->command->info('  - Perform clinical operations');
    }
}

