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
        Schema::create('emergency_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('visit_number')->unique();
            $table->timestamp('arrival_time');
            $table->text('chief_complaint');
            $table->enum('arrival_mode', ['ambulance', 'walk-in', 'private_vehicle', 'police', 'other']);
            $table->string('accompanied_by')->nullable();
            $table->string('referral_source')->nullable();
            $table->json('vital_signs');
            $table->integer('triage_level')->comment('1=Critical, 2=Urgent, 3=Less Urgent, 4=Non-urgent, 5=Minor');
            $table->text('triage_notes')->nullable();
            $table->unsignedBigInteger('assigned_doctor_id')->nullable();
            $table->enum('priority', ['critical', 'urgent', 'stable', 'non_urgent']);
            $table->enum('status', ['active', 'discharged', 'transferred', 'deceased'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamp('discharge_time')->nullable();
            $table->text('discharge_diagnosis')->nullable();
            $table->text('discharge_instructions')->nullable();
            $table->string('transfer_destination')->nullable();
            $table->text('transfer_reason')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('assigned_doctor_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_visits');
    }
};
