<?php

namespace Database\Seeders;

use App\Services\PermissionSyncService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RefineDoctorPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder refines doctor permissions to ensure they only see
     * menu items and functionality appropriate for their clinical role.
     */
    public function run(): void
    {
        $this->command->info('🔧 Refining Doctor Permissions...');

        app(PermissionSyncService::class)->sync();

        // Get the doctor role
        $doctor = Role::where('name', 'doctor')->first();
        
        if (!$doctor) {
            $this->command->error('❌ Doctor role not found!');
            return;
        }

        // Curated doctor permissions — sync replaces all prior doctor grants (not additive).
        $doctorPermissions = self::doctorPermissionNames();

        // Sync permissions (remove all old, add only these)
        $permissions = Permission::whereIn('name', $doctorPermissions)->get();
        $doctor->syncPermissions($permissions);

        $this->command->info('✅ Doctor permissions refined successfully!');
        $this->command->info("   Total permissions: " . $doctor->permissions->count());
        
        $this->command->info("\n📋 Synced permission names:");
        foreach ($doctor->permissions->sortBy('name') as $perm) {
            $this->command->line("  • {$perm->name}");
        }

        // Pharmacist grants: RefinePharmacistPermissionsSeeder (do not assign here).
        $this->updateAdminPermissions();
        $this->updateLabTechPermissions();
        $this->updateRadiologistPermissions();
        
        $this->command->info("\n✅ All role permissions updated successfully!");
    }

    /**
     * Canonical doctor permission list used by RefineDoctorPermissionsSeeder and DoctorPermissionsFixSeeder.
     *
     * @return list<string>
     */
    public static function doctorPermissionNames(): array
    {
        return [
            'view_dashboard',

            // Patients — view/search/edit clinical notes; no create
            'view_patients',
            'search_patients',
            'edit_patients',

            // Appointments — own scope enforced in controllers; no manage_appointments
            'view_appointments',
            'create_appointments',
            'edit_appointments',

            // Doctor schedules — own scope enforced in controllers
            'view_doctor_schedules',
            'create_doctor_schedules',
            'edit_doctor_schedules',

            // Consultations — assigned scope enforced in controllers
            'view_consultations',
            'create_consultations',
            'edit_consultations',
            'manage_consultations',

            // Visits — view only (no create_visits / edit_visits)
            'view_visits',

            'view_vitals',

            'view_prescriptions',
            'create_prescriptions',
            'edit_prescriptions',

            // Pharmacy — formulary read-only for consultation prescribing only (no /pharmacy/* routes)
            'view_drug_formulary',
            'view_drugs',

            // Laboratory — order tests and view results; no setup/verify/approve
            'view_lab_requests',
            'create_lab_requests',
            'view_lab_results',
            'print_lab_results',

            // Radiology — order via consultations + view own-patient results; no /radiology/* admin routes
            'create_radiology_requests',
            'view_radiology_results',

            // Wards / ICU / blood bank routes (view only)
            'view_wards',

            // Queues — OPD/emergency view + serve; no branch-wide queue admin
            'view_queues',
            'view_opd_queue',
            'view_emergency_queue',
            'call_patients',
            'serve_patients',

            // Emergency module — view access
            'view_emergency_visits',

            'view_surgery_schedules',

            // Teleconsultations — own scope; no delete
            'teleconsultation.view',
            'teleconsultation.create',
            'teleconsultation.edit',

            // Reports hub — clinical/operational cards only (no view_reports / financial / regulatory)

            'view_notifications',
            'mark_notification_read',
            'view_messages',
            'send_messages',
        ];
    }

    private function updateAdminPermissions()
    {
        $admin = Role::where('name', 'admin')->first();
        $superAdmin = Role::where('name', 'super_admin')->first();

        foreach ([$admin, $superAdmin] as $role) {
            if (!$role) continue;
            
            $role->givePermissionTo([
                'view_drug_formulary',
                'manage_pharmacy_inventory',
                'view_pharmacy_analytics',
                'manage_lab_setup',
                'manage_radiology_setup',
                'manage_billing',
                'view_revenue_analytics',
                'manage_users',
                'manage_roles',
                'manage_branches',
                'view_settings',
                'manage_settings',
                'manage_service_pricing',
                'view_insurance',
                'manage_insurance_providers',
                'manage_insurance_policies',
                'manage_insurance_claims',
                'view_insurance_analytics',
                'view_walk_ins',
                'manage_walk_ins',
                'view_complaints',
                'manage_complaints',
                'view_store_items',
                'manage_store_items',
                'view_debtors',
                'manage_debtors',
            ]);
        }
        
        $this->command->info('  ✓ Admin permissions updated');
    }

    private function updateLabTechPermissions()
    {
        $labTech = Role::where('name', 'lab_technician')->first();
        if (!$labTech) return;

        $labTech->givePermissionTo([
            'perform_lab_tests',
        ]);
        
        $this->command->info('  ✓ Lab Technician permissions updated');
    }

    private function updateRadiologistPermissions()
    {
        $radiologist = Role::where('name', 'radiologist')->first();
        if (!$radiologist) return;

        $radiologist->givePermissionTo([
            'perform_radiology_studies',
        ]);
        
        $this->command->info('  ✓ Radiologist permissions updated');
    }
}

