<?php

namespace Database\Seeders;

use App\Services\PermissionSyncService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RefinePatientPortalPermissionsSeeder extends Seeder
{
    /**
     * Sync web patient role permissions for the patient portal and mobile API.
     */
    public function run(): void
    {
        $portalPermissions = [
            'view_dashboard',
            'view_appointments',
            'create_appointments',
            'create_doctor_reviews',
            'view_doctor_reviews',
            'view_invoices',
            'view_own_invoices',
            'view_payments',
            'create_payments',
            'view_prescriptions',
            'view_consultations',
            'view_vitals',
            'view_complaints',
            'create_complaints',
            'view_lab_results',
            'view_store_items',
            'view_ecommerce',
            'create_store_orders',
            'view_store_orders',
            'view_notifications',
            'view_messages',
            'create_messages',
            'view_radiology_results',
            'view_radiology_reports',
            'teleconsultation.view',
            'teleconsultation.chat.view',
            'teleconsultation.chat.send',
            'teleconsultation.files.view',
            'teleconsultation.files.download',
            'teleconsultation.consent.give',
        ];

        app(PermissionSyncService::class)->sync();

        $permissions = Permission::where('guard_name', 'web')
            ->whereIn('name', $portalPermissions)
            ->get();

        $webPatientRole = Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
        $webPatientRole->syncPermissions($permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('Patient portal permissions synced for web guard (' . $permissions->count() . ' permissions).');
    }
}
