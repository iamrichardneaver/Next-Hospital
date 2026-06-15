<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number', 255)->nullable();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('slot_id')->nullable();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->text('reason')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no-show'])->default('scheduled');
            $table->enum('billing_status', ['pending', 'billed', 'paid', 'waived'])->default('pending');
            $table->enum('appointment_type', ['in-person', 'teleconsultation'])->default('in-person');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->boolean('is_teleconsultation')->default(0);
            $table->unsignedBigInteger('teleconsultation_id')->nullable();
            $table->string('meeting_url', 255)->nullable();
            $table->string('meeting_password', 255)->nullable();
            $table->unique(['appointment_number']);
            $table->index(['created_by'], 'appointments_created_by_foreign');
            $table->index(['is_teleconsultation', 'appointment_date']);
            $table->index(['teleconsultation_id'], 'appointments_teleconsultation_id_foreign');
            $table->index(['updated_by'], 'appointments_updated_by_foreign');
$table->foreign('branch_id', 'appointments_branch_id_foreign')->references('id')->on('branches')->onDelete('restrict')->onUpdate('cascade');
$table->foreign('doctor_id', 'appointments_doctor_id_foreign')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
$table->foreign('patient_id', 'appointments_patient_id_foreign')->references('id')->on('patients')->onDelete('restrict')->onUpdate('cascade');
$table->foreign('slot_id', 'appointments_slot_id_foreign')->references('id')->on('appointment_slots')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
