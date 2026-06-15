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
        
        // Add a temporary column to store the new string IDs
        Schema::table('branches', function (Blueprint $table) {
            $table->string('temp_id')->nullable()->after('id');
        });
        
        // Copy existing data to temp_id with string format
        DB::statement("UPDATE branches SET temp_id = CONCAT('HWC/BRN/', LPAD(id, 3, '0'))");
        
        // Drop the old id column
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('id');
        });
        
        // Rename temp_id to id and make it primary
        Schema::table('branches', function (Blueprint $table) {
            $table->string('temp_id')->primary()->change();
        });
        
        Schema::table('branches', function (Blueprint $table) {
            $table->renameColumn('temp_id', 'id');
        });
        
        // Update foreign key references in patients table
        DB::statement("UPDATE patients SET branch_id = CONCAT('HWC/BRN/', LPAD(branch_id, 3, '0'))");
        
        // Recreate foreign key constraint
        Schema::table('patients', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // Drop the string id column
            $table->dropColumn('id');
        });
        
        Schema::table('branches', function (Blueprint $table) {
            // Add back the auto-increment id column
            $table->id()->first();
        });
    }
};