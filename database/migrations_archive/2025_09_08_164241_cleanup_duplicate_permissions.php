<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all permissions and group them by normalized name
        $permissions = Permission::all()->groupBy(function($item) {
            return str_replace(['-', '_'], '', strtolower($item->name));
        });

        foreach ($permissions as $normalizedName => $group) {
            if ($group->count() > 1) {
                // Keep the hyphenated version (lower ID) and remove underscore versions
                $keepPermission = $group->sortBy('id')->first();
                $duplicatePermissions = $group->where('id', '>', $keepPermission->id);

                echo "Cleaning up duplicates for: {$normalizedName}\n";
                echo "Keeping: {$keepPermission->name} (ID: {$keepPermission->id})\n";

                foreach ($duplicatePermissions as $duplicate) {
                    echo "Removing: {$duplicate->name} (ID: {$duplicate->id})\n";
                    
                    // Remove from role_permissions table first
                    \DB::table('role_has_permissions')
                        ->where('permission_id', $duplicate->id)
                        ->delete();
                    
                    // Remove from model_has_permissions table
                    \DB::table('model_has_permissions')
                        ->where('permission_id', $duplicate->id)
                        ->delete();
                    
                    // Delete the duplicate permission
                    $duplicate->delete();
                }
            }
        }

        echo "Permission cleanup completed!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be reversed as it deletes data
        // If you need to reverse, you would need to recreate the deleted permissions
        echo "This migration cannot be reversed as it deletes duplicate permissions.\n";
    }
};