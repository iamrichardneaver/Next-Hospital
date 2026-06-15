<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ECommercePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // E-Commerce & Store Permissions
        $ecommercePermissions = [
            // Store Items
            'view_store_items',
            'create_store_items',
            'edit_store_items',
            'delete_store_items',
            
            // Orders
            'view_store_orders',
            'create_store_orders',
            'edit_store_orders',
            'delete_store_orders',
            'process_store_orders',
            'cancel_store_orders',
            
            // Deliveries
            'view_deliveries',
            'manage_deliveries',
            'assign_deliveries',
            'update_delivery_status',
            
            // Delivery Riders
            'view_delivery_riders',
            'create_delivery_riders',
            'edit_delivery_riders',
            'delete_delivery_riders',
            'manage_delivery_riders',
        ];

        // Create permissions
        foreach ($ecommercePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->command->info('E-Commerce permissions created successfully!');

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    /**
     * Assign permissions to appropriate roles
     */
    private function assignPermissionsToRoles(): void
    {
        // Super Admin - gets ALL permissions (ALWAYS FIRST)
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - gets most permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'view_store_items',
            'create_store_items',
            'edit_store_items',
            'view_store_orders',
            'edit_store_orders',
            'process_store_orders',
            'cancel_store_orders',
            'view_deliveries',
            'manage_deliveries',
            'assign_deliveries',
            'update_delivery_status',
            'view_delivery_riders',
            'create_delivery_riders',
            'edit_delivery_riders',
            'manage_delivery_riders',
        ]);

        // Store Manager - manages store items and orders
        $storeManager = Role::firstOrCreate(['name' => 'store_manager']);
        $storeManager->givePermissionTo([
            'view_store_items',
            'create_store_items',
            'edit_store_items',
            'view_store_orders',
            'create_store_orders',
            'edit_store_orders',
            'process_store_orders',
            'cancel_store_orders',
            'view_deliveries',
            'manage_deliveries',
            'assign_deliveries',
            'update_delivery_status',
            'view_delivery_riders',
        ]);

        // Dispatch Manager - manages deliveries and riders
        $dispatchManager = Role::firstOrCreate(['name' => 'dispatch_manager']);
        $dispatchManager->givePermissionTo([
            'view_store_orders',
            'view_deliveries',
            'manage_deliveries',
            'assign_deliveries',
            'update_delivery_status',
            'view_delivery_riders',
            'create_delivery_riders',
            'edit_delivery_riders',
            'manage_delivery_riders',
        ]);

        // Delivery Rider - view and update their own deliveries
        $deliveryRider = Role::firstOrCreate(['name' => 'delivery_rider']);
        $deliveryRider->givePermissionTo([
            'view_deliveries',
            'update_delivery_status',
        ]);

        // Pharmacist - can view and create store orders
        $pharmacist = Role::firstOrCreate(['name' => 'pharmacist']);
        if ($pharmacist->hasPermissionTo('view_drugs')) {
            $pharmacist->givePermissionTo([
                'view_store_items',
                'view_store_orders',
                'create_store_orders',
            ]);
        }

        // Receptionist - can view store items and orders
        $receptionist = Role::firstOrCreate(['name' => 'receptionist']);
        if ($receptionist->exists) {
            $receptionist->givePermissionTo([
                'view_store_items',
                'view_store_orders',
                'create_store_orders',
            ]);
        }

        $this->command->info('Permissions assigned to roles successfully!');
    }
}
