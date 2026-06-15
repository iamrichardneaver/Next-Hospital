<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for the Codecanyon demo.
     *
     * Permission order matters: base permissions → module seeders → Refine* sync (last wins per role).
     *
     * @see DatabaseSeeder Permission seeding documentation
     */
    public function run(): void
    {
        $this->call([
            // 0. Canonical permission records from config/permissions.php (safe to re-run)
            PermissionsSyncSeeder::class,

            // 1. Base permission records and roles (doctor/pharmacist grants deferred to Refine*)
            RolePermissionSeeder::class,

            // 2. Module permission definitions (avoid re-running role sync blocks in deprecated seeders)
            WalkInsPermissionsSeeder::class,
            VitalsPermissionsSeeder::class,
            RadiologyPermissionsSeeder::class,
            QueuePermissionsSeeder::class,
            TeleconsultationPermissionSeeder::class,

            // 3. Canonical role sync — must run after legacy seeders that might add conflicting grants
            RefineDoctorPermissionsSeeder::class,
            RefinePharmacistPermissionsSeeder::class,
            RefineLabScientistPermissionsSeeder::class,
            RefineNursePermissionsSeeder::class,
            RefineReceptionistPermissionsSeeder::class,
            RefineCashierPermissionsSeeder::class,
            AccountantPermissionsSeeder::class,
            DepartmentExpensePermissionsSeeder::class,
            RefineRadiologistPermissionsSeeder::class,
            InventorySupplierPermissionsSeeder::class,
            RefineAdminPermissionsSeeder::class,

            BranchSeeder::class,
            SettingsSeeder::class,
            IdPrefixSettingsSeeder::class,
            WorkflowSeeder::class,
            UserSeeder::class,

            // 4. Idempotent doctor re-sync + demo branch alignment (safe to re-run)
            DoctorPermissionsFixSeeder::class,

            DoctorScheduleSeeder::class,
            AppointmentSlotSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
