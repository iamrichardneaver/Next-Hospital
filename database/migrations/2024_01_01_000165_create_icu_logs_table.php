<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icu_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->unsignedBigInteger('bed_id')->nullable();
            $table->timestamp('admission_time')->useCurrent();
            $table->timestamp('discharge_time')->nullable();
            $table->enum('admission_type', ['elective', 'emergency', 'transfer'])->default('emergency');
            $table->text('admission_diagnosis')->nullable();
            $table->text('chief_complaint')->nullable();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->integer('heart_rate')->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->integer('blood_pressure_systolic')->nullable();
            $table->integer('blood_pressure_diastolic')->nullable();
            $table->integer('oxygen_saturation')->nullable();
            $table->decimal('glucose_level', 5, 2)->nullable();
            $table->boolean('on_ventilator')->default(0);
            $table->string('ventilator_mode', 255)->nullable();
            $table->integer('ventilator_rate')->nullable();
            $table->integer('fio2')->nullable();
            $table->integer('peep')->nullable();
            $table->boolean('on_dialysis')->default(0);
            $table->string('dialysis_type', 255)->nullable();
            $table->boolean('on_vasopressors')->default(0);
            $table->text('vasopressor_details')->nullable();
            $table->text('fluid_intake')->nullable();
            $table->text('fluid_output')->nullable();
            $table->decimal('fluid_balance', 8, 2)->nullable();
            $table->integer('gcs_eye')->nullable();
            $table->integer('gcs_verbal')->nullable();
            $table->integer('gcs_motor')->nullable();
            $table->integer('gcs_total')->nullable();
            $table->text('medications')->nullable();
            $table->text('procedures_performed')->nullable();
            $table->text('interventions')->nullable();
            $table->text('nursing_notes')->nullable();
            $table->text('doctor_notes')->nullable();
            $table->text('progress_notes')->nullable();
            $table->unsignedBigInteger('attending_doctor_id')->nullable();
            $table->unsignedBigInteger('assigned_nurse_id')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->enum('patient_condition', ['stable', 'critical', 'improving', 'deteriorating'])->default('critical');
            $table->enum('status', ['active', 'discharged', 'transferred', 'deceased'])->default('active');
            $table->text('discharge_notes')->nullable();
            $table->string('discharge_destination', 255)->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['admission_time']);
            $table->index(['patient_id']);
            $table->index(['recorded_at']);
            $table->index(['status']);
$table->foreign('assigned_nurse_id', 'icu_logs_assigned_nurse_id_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
$table->foreign('attending_doctor_id', 'icu_logs_attending_doctor_id_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
$table->foreign('bed_id', 'icu_logs_bed_id_foreign')->references('id')->on('beds')->onDelete('set null')->onUpdate('restrict');
$table->foreign('branch_id', 'icu_logs_branch_id_foreign')->references('id')->on('branches')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('patient_id', 'icu_logs_patient_id_foreign')->references('id')->on('patients')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('recorded_by', 'icu_logs_recorded_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
$table->foreign('visit_id', 'icu_logs_visit_id_foreign')->references('id')->on('visits')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_logs');
    }
};
