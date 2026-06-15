<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('patient_number', 255);
            $table->string('first_name', 255);
            $table->string('other_names', 255)->nullable();
            $table->string('last_name', 255);
            $table->enum('gender', ['Male', 'Female']);
            $table->date('date_of_birth')->nullable();
            $table->string('phone', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('password', 255)->nullable();
            $table->enum('account_status', ['pending', 'active', 'suspended', 'rejected'])->default('pending');
            $table->timestamp('account_activated_at')->nullable();
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('address')->nullable();
            $table->string('nhis_number', 255)->nullable();
            $table->string('ghana_card_number', 255)->nullable();
            $table->string('emergency_contact_name', 255)->nullable();
            $table->string('emergency_contact_phone', 255)->nullable();
            $table->string('emergency_contact_relationship', 255)->nullable();
            $table->string('photo', 255)->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->enum('registration_source', ['web', 'mobile_app', 'api', 'system'])->nullable()->default('web');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['patient_number'], 'idx_patients_patient_number');
            $table->index(['branch_id'], 'patients_branch_id_foreign');
            $table->index(['created_by'], 'patients_created_by_foreign');
            $table->unique(['patient_number']);
            $table->index(['registration_source']);
            $table->index(['updated_by'], 'patients_updated_by_foreign');
            $table->index(['user_id']);
$table->foreign('activated_by', 'patients_activated_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
