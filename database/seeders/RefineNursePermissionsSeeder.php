<?php

namespace Database\Seeders;

use App\Services\PermissionSyncService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RefineNursePermissionsSeeder extends Seeder
{
    /**
     * Sync curated nurse permissions (replaces all prior nurse grants).
     *
     * Clinical/ops scope only: vitals, visits, appointments, patients, queues,
     * wards/beds (view), ICU monitor (view_wards), nurse-scoped reports.
     */
    public function run(): void
    {
        $this->command?->info('Refining nurse permissions...');

        app(PermissionSyncService::class)->sync();

        $nurse = Role::where('name', 'nurse')->first();
        if (!$nurse) {
            $this->command?->error('Nurse role not found.');
            return;
        }

        $permissions = Permission::whereIn('name', self::nursePermissionNames())->get();
        $missing = array_diff(self::nursePermissionNames(), $permissions->pluck('name')->all());
        if ($missing !== []) {
            $this->command?->warn('Missing permission records (create via RolePermissionSeeder/module seeders): ' . implode(', ', $missing));
        }

        $nurse->syncPermissions($permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('Nurse permissions refined: ' . $nurse->permissions->count() . ' total');
        foreach ($nurse->permissions->sortBy('name') as $perm) {
            $this->command?->line("  • {$perm->name}");
        }
    }

    /**
     * Canonical nurse permission list.
     *
     * @return list<string>
     */
    public static function nursePermissionNames(): array
    {
        return [
            'view_dashboard',

            // Patients — view/search/update demographics during care
            'view_patients',
            'search_patients',
            'edit_patients',

            // Appointments
            'view_appointments',
            'create_appointments',
            'edit_appointments',

            // Visits — check-in and ward routing support
            'view_visits',
            'create_visits',
            'edit_visits',

            // Vitals — primary nursing responsibility
            'record_vitals',
            'view_vitals',
            'edit_vitals',

            // Queues — OPD + emergency (preserve manage_emergency_queue from queue audit)
            'view_queues',
            'view_opd_queue',
            'manage_opd_queue',
            'view_emergency_queue',
            'manage_emergency_queue',
            'view_triage_queue',
            'manage_triage_queue',
            'call_patients',
            'serve_patients',

            // Wards / beds / ICU dashboard (view only — no manage_wards)
            'view_wards',
            'view_beds',

            // Emergency report card only (not create/triage/alerts admin)
            'view_emergency_visits',

            // Communications
            'view_notifications',
            'mark_notification_read',
            'view_messages',
            'send_messages',

            // Department operational expenses
            'create_expenses',
            'view_own_expenses',
        ];
    }
}
