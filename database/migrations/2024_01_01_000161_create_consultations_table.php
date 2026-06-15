<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->string('consultation_number', 255)->nullable();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->unsignedBigInteger('workflow_instance_id')->nullable();
            $table->date('consultation_date');
            $table->time('consultation_time');
            $table->enum('consultation_type', ['in-person', 'teleconsultation'])->default('in-person');
            $table->text('chief_complaint')->nullable();
            $table->longText('presenting_complaints')->nullable();
            $table->text('history_of_present_illness')->nullable();
            $table->text('past_medical_history')->nullable();
            $table->text('family_history')->nullable();
            $table->text('social_history')->nullable();
            $table->text('drug_history')->nullable();
            $table->text('allergy_history')->nullable();
            $table->longText('past_medical_history_details')->nullable();
            $table->text('past_medical_history_others')->nullable();
            $table->longText('current_medications')->nullable();
            $table->longText('drug_allergies')->nullable();
            $table->text('past_drug_usage')->nullable();
            $table->longText('social_history_details')->nullable();
            $table->text('on_direct_questioning')->nullable();
            $table->text('physical_examination')->nullable();
            $table->text('general_examination')->nullable();
            $table->decimal('blood_pressure_systolic', 5, 2)->nullable();
            $table->decimal('blood_pressure_diastolic', 5, 2)->nullable();
            $table->integer('pulse_rate')->nullable();
            $table->decimal('temperature', 4, 2)->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->integer('oxygen_saturation')->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('bmi', 4, 2)->nullable();
            $table->text('cardiovascular_examination')->nullable();
            $table->text('respiratory_examination')->nullable();
            $table->text('abdominal_examination')->nullable();
            $table->text('neurological_examination')->nullable();
            $table->longText('vitals')->nullable();
            $table->longText('diagnoses')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('medications_prescribed')->nullable();
            $table->text('investigations_ordered')->nullable();
            $table->text('referrals_made')->nullable();
            $table->longText('attached_files')->nullable();
            $table->text('follow_up_instructions')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->longText('workflow_steps')->nullable();
            $table->string('next_stage', 255)->nullable();
            $table->date('next_appointment_date')->nullable();
            $table->text('next_appointment_notes')->nullable();
            $table->string('icd_10_code', 20)->nullable();
            $table->longText('icd_10_codes')->nullable();
            $table->enum('severity', ['mild', 'moderate', 'severe'])->nullable();
            $table->enum('urgency', ['routine', 'urgent', 'critical'])->nullable()->default('routine');
            $table->enum('consultation_status', ['ongoing', 'completed', 'cancelled'])->default('ongoing');
            $table->boolean('is_draft')->default(0);
            $table->string('template_used', 255)->nullable();
            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('assessment')->nullable();
            $table->text('doctors_impression')->nullable();
            $table->text('plan')->nullable();
            $table->boolean('nhis_eligible')->default(0);
            $table->boolean('requires_referral')->default(0);
            $table->text('referral_notes')->nullable();
            $table->string('referral_specialty', 100)->nullable();
            $table->string('referral_reason', 500)->nullable();
            $table->longText('reception_notes')->nullable();
            $table->longText('doctor_remarks')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('amended_at')->nullable();
            $table->unsignedBigInteger('amended_by')->nullable();
            $table->text('amendment_notes')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->unsignedBigInteger('called_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('completion_notes')->nullable();
            $table->string('completion_type', 50)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->enum('billing_status', ['pending', 'billed', 'paid', 'partial'])->default('pending');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->decimal('billing_amount', 10, 2)->nullable();
            $table->timestamp('billed_at')->nullable();
            $table->index(['branch_id'], 'consultations_branch_id_foreign');
            $table->index(['called_by']);
            $table->unique(['consultation_number']);
            $table->index(['created_by'], 'consultations_created_by_foreign');
            $table->index(['updated_by'], 'consultations_updated_by_foreign');
            $table->index(['visit_id']);
            $table->index(['workflow_instance_id']);
            $table->index(['consultation_date'], 'idx_consultations_date');
$table->foreign('called_by', 'consultations_called_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
$table->foreign('doctor_id', 'consultations_doctor_id_foreign')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
$table->foreign('invoice_id', 'consultations_invoice_id_foreign')->references('id')->on('invoices')->onDelete('set null')->onUpdate('restrict');
$table->foreign('patient_id', 'consultations_patient_id_foreign')->references('id')->on('patients')->onDelete('cascade')->onUpdate('cascade');
$table->foreign('workflow_instance_id', 'consultations_workflow_instance_id_foreign')->references('id')->on('workflow_instances')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
