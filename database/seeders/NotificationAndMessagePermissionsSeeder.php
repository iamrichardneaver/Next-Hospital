<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class NotificationAndMessagePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for notifications
        $notificationPermissions = [
            'view_notifications',
            'mark_notification_read',
            'delete_notifications',
        ];

        foreach ($notificationPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create permissions for messages
        $messagePermissions = [
            'view_messages',
            'send_messages',
            'delete_messages',
        ];

        foreach ($messagePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign all notification and message permissions to Super Admin
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($notificationPermissions);
            $superAdmin->givePermissionTo($messagePermissions);
        }

        // Assign notification and message permissions to all staff roles
        $staffRoles = [
            'Admin',
            'Doctor',
            'Nurse',
            'Receptionist',
            'Lab Technician',
            'Pharmacist',
            'Radiologist',
            'Accountant',
            'Cashier',
        ];

        foreach ($staffRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                // All staff can view notifications and messages
                $role->givePermissionTo([
                    'view_notifications',
                    'mark_notification_read',
                    'view_messages',
                    'send_messages',
                ]);
            }
        }

        // Admins can delete notifications and messages
        $adminRoles = ['Admin', 'Super Admin'];
        foreach ($adminRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo([
                    'delete_notifications',
                    'delete_messages',
                ]);
            }
        }

        $this->command->info('✅ Notification and Message permissions seeded successfully!');
    }
}
