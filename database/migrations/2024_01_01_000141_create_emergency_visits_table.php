<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('visit_number', 255);
            $table->timestamp('arrival_time')->useCurrent();
            $table->text('chief_complaint');
            $table->enum('arrival_mode', ['ambulance', 'walk-in', 'private_vehicle', 'police', 'other']);
            $table->string('accompanied_by', 255)->nullable();
            $table->string('referral_source', 255)->nullable();
            $table->longText('vital_signs');
            $table->integer('triage_level');
            $table->text('triage_notes')->nullable();
            $table->unsignedBigInteger('assigned_doctor_id')->nullable();
            $table->unsignedBigInteger('assigned_nurse_id')->nullable();
            $table->enum('priority', ['critical', 'urgent', 'stable', 'non_urgent']);
            $table->enum('status', ['active', 'discharged', 'transferred', 'deceased'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamp('discharge_time')->nullable();
            $table->text('discharge_diagnosis')->nullable();
            $table->text('discharge_instructions')->nullable();
            $table->string('transfer_destination', 255)->nullable();
            $table->text('transfer_reason')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['assigned_doctor_id'], 'emergency_visits_assigned_doctor_id_foreign');
            $table->index(['branch_id'], 'emergency_visits_branch_id_foreign');
            $table->index(['created_by'], 'emergency_visits_created_by_foreign');
            $table->index(['patient_id'], 'emergency_visits_patient_id_foreign');
            $table->index(['visit_id'], 'emergency_visits_visit_id_foreign');
            $table->unique(['visit_number']);
$table->foreign('assigned_nurse_id', 'emergency_visits_assigned_nurse_id_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_visits');
    }
};
