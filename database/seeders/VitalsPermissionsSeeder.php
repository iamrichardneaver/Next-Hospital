<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Creates vitals permission records. Role grants are additive (givePermissionTo).
 * Run before Refine* seeders; doctor/nurse role grants are synced by RefineDoctorPermissionsSeeder
 * and RefineNursePermissionsSeeder respectively.
 *
 * @see DatabaseSeeder Permission seeding documentation
 */
class VitalsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create vitals-specific permissions
        $vitalsPermissions = [
            'record_vitals' => 'Record patient vital signs (BP, temperature, pulse, etc.)',
            'view_vitals' => 'View patient vital signs history',
            'edit_vitals' => 'Edit previously recorded vital signs',
            'delete_vitals' => 'Delete vital signs records',
        ];

        foreach ($vitalsPermissions as $name => $description) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web'
            ]);
        }

        // Assign permissions to roles based on healthcare workflow

        // Nurse vitals grants: RefineNursePermissionsSeeder (record + view + edit).

        // Doctor vitals grants: RefineDoctorPermissionsSeeder (view only).

        // Receptionist vitals grants: RefineReceptionistPermissionsSeeder (no vitals access).

        // 👨‍💼 ADMIN ROLE - Full access to vitals management
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo([
                'record_vitals',  // ✅ Admins can record vitals
                'view_vitals',    // ✅ Admins can view vitals
                'edit_vitals',    // ✅ Admins can edit vitals
                'delete_vitals',  // ✅ Admins can delete vitals (for corrections)
            ]);
        }

        // 🔴 SUPER ADMIN ROLE - Already has all permissions automatically
        // No need to explicitly assign

        $this->command->info('✅ Vitals permissions created');
        $this->command->info('   Nurse/doctor grants: RefineNursePermissionsSeeder / RefineDoctorPermissionsSeeder');
        $this->command->info('   Receptionist: RefineReceptionistPermissionsSeeder; admins: assigned here');
    }
}
