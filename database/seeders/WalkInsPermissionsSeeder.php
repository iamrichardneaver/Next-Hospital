<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Walk-ins register permissions (web canonical names).
 *
 * - view_walk_ins_register: read access (sidebar + routes)
 * - manage_walk_ins: manage entries (routes; sidebar @canany)
 * - view_walk_ins: legacy API name — not assigned here
 *
 * Doctors do not receive walk-ins access (RefineDoctorPermissionsSeeder excludes them).
 *
 * @see DatabaseSeeder Permission seeding documentation
 */
class WalkInsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view_walk_ins_register',
            'manage_walk_ins',
            'export_walk_ins_register',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $this->command->info('Walk-ins permissions created successfully!');

        $superAdmin = Role::where('name', 'super_admin')->first();
        $admin = Role::where('name', 'admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo([
                'view_walk_ins_register',
                'manage_walk_ins',
                'export_walk_ins_register',
            ]);
            $this->command->info('Permissions assigned to Super Admin');
        }

        if ($admin) {
            $admin->givePermissionTo([
                'view_walk_ins_register',
                'manage_walk_ins',
                'export_walk_ins_register',
            ]);
            $this->command->info('Permissions assigned to Admin');
        }

        // Receptionist walk-ins grants: RefineReceptionistPermissionsSeeder (additive grants here are overwritten).

        // Nurse walk-ins grants: RefineNursePermissionsSeeder (no walk-ins register access).
    }
}
