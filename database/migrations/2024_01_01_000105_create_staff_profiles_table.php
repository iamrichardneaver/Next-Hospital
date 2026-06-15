<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('employee_id', 255);
            $table->string('first_name', 255);
            $table->string('last_name', 255);
            $table->string('contact', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->string('department', 255)->nullable();
            $table->string('specialization', 255)->nullable();
            $table->string('license_number', 255)->nullable();
            $table->string('emergency_contact', 255)->nullable();
            $table->date('license_expiry')->nullable();
            $table->enum('online_status', ['online', 'offline'])->default('offline');
            $table->boolean('is_active')->default(1);
            $table->string('photo', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id'], 'staff_profiles_branch_id_foreign');
            $table->unique(['employee_id']);
            $table->index(['user_id'], 'staff_profiles_user_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};
