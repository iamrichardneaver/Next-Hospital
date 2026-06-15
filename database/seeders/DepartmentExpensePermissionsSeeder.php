<?php

namespace Database\Seeders;

use App\Services\PermissionSyncService;
use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DepartmentExpensePermissionsSeeder extends Seeder
{
    /**
     * Create department expense permissions and grant to operational staff roles.
     *
     * php artisan db:seed --class=DepartmentExpensePermissionsSeeder
     */
    public function run(): void
    {
        $this->command?->info('Seeding department expense permissions...');

        app(PermissionSyncService::class)->sync();

        foreach (PermissionRegistry::moduleNames('expenses') as $name) {
            $this->command?->line("  Permission: {$name}");
        }

        $staffSubmit = ['create_expenses', 'view_own_expenses'];
        $staffRoles = [
            'pharmacist',
            'lab_scientist',
            'lab_technician',
            'radiologist',
            'radiology_technician',
            'receptionist',
            'nurse',
            'cashier',
        ];

        foreach ($staffRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($staffSubmit);
                $this->command?->info("  Granted create_expenses + view_own_expenses to {$roleName}");
            }
        }

        foreach (['accountant', 'admin', 'super_admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo('approve_expenses');
                $this->command?->info("  Granted approve_expenses to {$roleName}");
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('Department expense permissions seeded.');
    }

    /**
     * @return list<string>
     */
    public static function staffExpensePermissionNames(): array
    {
        return ['create_expenses', 'view_own_expenses'];
    }

    /**
     * @return list<string>
     */
    public static function approverExpensePermissionNames(): array
    {
        return ['approve_expenses'];
    }
}
