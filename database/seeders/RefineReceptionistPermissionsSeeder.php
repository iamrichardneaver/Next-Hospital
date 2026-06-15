<?php

namespace Database\Seeders;

use App\Services\PermissionSyncService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RefineReceptionistPermissionsSeeder extends Seeder
{
    /**
     * Sync curated receptionist permissions (replaces all prior receptionist grants).
     *
     * Front-desk scope: register patients, check-in, visits, walk-ins register, OPD queue,
     * appointments booking, patient complaints (view/create/respond). No clinical admin,
     * pharmacy, lab, radiology, or billing admin.
     */
    public function run(): void
    {
        $this->command?->info('Refining receptionist permissions...');

        app(PermissionSyncService::class)->sync();

        $receptionist = Role::where('name', 'receptionist')->first();
        if (!$receptionist) {
            $this->command?->error('Receptionist role not found.');
            return;
        }

        $permissions = Permission::whereIn('name', self::receptionistPermissionNames())->get();
        $missing = array_diff(self::receptionistPermissionNames(), $permissions->pluck('name')->all());
        if ($missing !== []) {
            $this->command?->warn('Missing permission records: ' . implode(', ', $missing));
        }

        $receptionist->syncPermissions($permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('Receptionist permissions refined: ' . $receptionist->permissions->count() . ' total');
        foreach ($receptionist->permissions->sortBy('name') as $perm) {
            $this->command?->line("  • {$perm->name}");
        }
    }

    /**
     * Permissions that may not exist in RolePermissionSeeder — ensure records exist.
     *
     * @return list<string>
     */
    public static function ensurePermissionNames(): array
    {
        return [
            'view_walk_ins_register',
            'manage_walk_ins',
            'view_notifications',
            'mark_notification_read',
        ];
    }

    /**
     * Canonical receptionist permission list.
     *
     * @return list<string>
     */
    public static function receptionistPermissionNames(): array
    {
        return [
            'view_dashboard',

            // Patients — register, 360 view, demographics edit, portal provisioning (via edit_patients routes)
            'view_patients',
            'create_patients',
            'edit_patients',
            'search_patients',

            // Appointments — front-desk booking
            'view_appointments',
            'create_appointments',
            'edit_appointments',

            // Visits — OPD/IPD check-in
            'view_visits',
            'create_visits',
            'edit_visits',

            // Walk-ins register (web canonical names)
            'view_walk_ins_register',
            'manage_walk_ins',

            // OPD queue — call patients after check-in
            'view_queues',
            'view_opd_queue',
            'manage_opd_queue',
            'call_patients',

            // Communications
            'view_notifications',
            'mark_notification_read',

            // Complaints — front desk receives and responds (mirrors ComplaintsPermissionsSeeder front_desk)
            'view_complaints',
            'create_complaints',
            'edit_complaints',

            // Department operational expenses
            'create_expenses',
            'view_own_expenses',
        ];
    }
}
