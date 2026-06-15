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
        Schema::table('facility_users', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->after('id');
            $table->unsignedBigInteger('branch_id')->after('user_id');
            $table->unsignedBigInteger('role_id')->nullable()->after('branch_id');
            $table->boolean('is_active')->default(true)->after('role_id');
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
            
            $table->unique(['user_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facility_users', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['role_id']);
            $table->dropUnique(['user_id', 'branch_id']);
            $table->dropColumn(['user_id', 'branch_id', 'role_id', 'is_active']);
        });
    }
};