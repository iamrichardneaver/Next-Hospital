<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add drug_number to drugs table
        if (!Schema::hasColumn('drugs', 'drug_number')) {
            Schema::table('drugs', function (Blueprint $table) {
                $table->string('drug_number')->unique()->nullable()->after('id');
            });
        }

        // Add ward_number to wards table
        if (!Schema::hasColumn('wards', 'ward_number')) {
            Schema::table('wards', function (Blueprint $table) {
                $table->string('ward_number')->unique()->nullable()->after('id');
            });
        }

        // Add bed_number to beds table
        if (!Schema::hasColumn('beds', 'bed_number')) {
            Schema::table('beds', function (Blueprint $table) {
                $table->string('bed_number')->unique()->nullable()->after('id');
            });
        }

        // Add branch_number to branches table
        if (!Schema::hasColumn('branches', 'branch_number')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->string('branch_number')->unique()->nullable()->after('id');
            });
        }

        // Add lab_result_number to lab_results table (if it exists)
        if (Schema::hasTable('lab_results') && !Schema::hasColumn('lab_results', 'lab_result_number')) {
            Schema::table('lab_results', function (Blueprint $table) {
                $table->string('lab_result_number')->unique()->nullable()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drugs', function (Blueprint $table) {
            $table->dropColumn('drug_number');
        });

        Schema::table('wards', function (Blueprint $table) {
            $table->dropColumn('ward_number');
        });

        Schema::table('beds', function (Blueprint $table) {
            $table->dropColumn('bed_number');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('branch_number');
        });

        if (Schema::hasTable('lab_results')) {
            Schema::table('lab_results', function (Blueprint $table) {
                $table->dropColumn('lab_result_number');
            });
        }
    }
};