<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, drop foreign key constraints that reference branches.id
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });
        
        // Drop the existing branches table
        Schema::dropIfExists('branches');
        
        // Recreate branches table with string ID
        Schema::create('branches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('branch_number')->unique()->nullable();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('address');
            $table->string('phone');
            $table->string('email');
            $table->string('timezone')->default('Africa/Accra');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
        
        // Recreate foreign key constraint
        Schema::table('patients', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
        
        // Repopulate with existing data using string IDs
        DB::table('branches')->insert([
            [
                'id' => 'HWC/BRN/001',
                'branch_number' => 'HWC/BRN/001',
                'name' => 'NextHospital Main Branch',
                'code' => 'MAIN',
                'address' => '123 Hospital Street, Accra, Ghana',
                'phone' => '+233-123-456-789',
                'email' => 'main@nexthospital.com',
                'timezone' => 'Africa/Accra',
                'is_active' => true,
                'settings' => json_encode([
                    'currency' => 'GHS',
                    'working_hours' => '24/7',
                    'emergency_contact' => '+233-123-456-789'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'HWC/BRN/002',
                'branch_number' => 'HWC/BRN/002',
                'name' => 'NextHospital East Branch',
                'code' => 'EAST',
                'address' => '456 East Avenue, Tema, Ghana',
                'phone' => '+233-123-456-790',
                'email' => 'east@nexthospital.com',
                'timezone' => 'Africa/Accra',
                'is_active' => true,
                'settings' => json_encode([
                    'currency' => 'GHS',
                    'working_hours' => '6:00 AM - 10:00 PM',
                    'emergency_contact' => '+233-123-456-790'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'HWC/BRN/003',
                'branch_number' => 'HWC/BRN/003',
                'name' => 'NextHospital West Branch',
                'code' => 'WEST',
                'address' => '789 West Road, Kumasi, Ghana',
                'phone' => '+233-123-456-791',
                'email' => 'west@nexthospital.com',
                'timezone' => 'Africa/Accra',
                'is_active' => true,
                'settings' => json_encode([
                    'currency' => 'GHS',
                    'working_hours' => '6:00 AM - 10:00 PM',
                    'emergency_contact' => '+233-123-456-791'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        
        // Update patients table to use new branch IDs
        DB::statement("UPDATE patients SET branch_id = 'HWC/BRN/001' WHERE branch_id = '1'");
        DB::statement("UPDATE patients SET branch_id = 'HWC/BRN/002' WHERE branch_id = '2'");
        DB::statement("UPDATE patients SET branch_id = 'HWC/BRN/003' WHERE branch_id = '3'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });
        
        // Drop the branches table
        Schema::dropIfExists('branches');
        
        // Recreate with original structure
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('address');
            $table->string('phone');
            $table->string('email');
            $table->string('timezone')->default('Africa/Accra');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
        
        // Recreate foreign key constraint
        Schema::table('patients', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
    }
};