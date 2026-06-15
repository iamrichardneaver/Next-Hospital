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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('branch_id');
            $table->date('consultation_date');
            $table->time('consultation_time');
            $table->enum('consultation_type', ['in-person', 'teleconsultation', 'emergency', 'follow-up'])->default('in-person');
            $table->enum('consultation_status', ['ongoing', 'completed', 'cancelled', 'transferred'])->default('ongoing');
            
            // Chief Complaint & History
            $table->text('chief_complaint')->nullable();
            $table->text('history_of_present_illness')->nullable();
            $table->text('past_medical_history')->nullable();
            $table->text('family_history')->nullable();
            $table->text('social_history')->nullable();
            $table->text('drug_history')->nullable();
            $table->text('allergy_history')->nullable();
            
            // Physical Examination
            $table->text('general_appearance')->nullable();
            $table->text('vital_signs')->nullable();
            $table->text('cardiovascular_exam')->nullable();
            $table->text('respiratory_exam')->nullable();
            $table->text('abdominal_exam')->nullable();
            $table->text('neurological_exam')->nullable();
            $table->text('musculoskeletal_exam')->nullable();
            $table->text('dermatological_exam')->nullable();
            $table->text('other_examinations')->nullable();
            
            // SOAP Format
            $table->text('subjective')->nullable(); // Chief complaint, history
            $table->text('objective')->nullable(); // Physical examination findings
            $table->text('assessment')->nullable(); // Diagnosis, differential diagnosis
            $table->text('plan')->nullable(); // Treatment plan, follow-up
            
            // Clinical Decision Support
            $table->json('differential_diagnosis')->nullable(); // Array of possible diagnoses
            $table->json('clinical_impression')->nullable(); // Clinical impression
            $table->json('treatment_plan')->nullable(); // Detailed treatment plan
            $table->json('follow_up_plan')->nullable(); // Follow-up instructions
            
            // Ghanaian Medical Standards
            $table->string('icd_10_code')->nullable(); // Primary ICD-10 code
            $table->json('icd_10_codes')->nullable(); // Multiple ICD-10 codes
            $table->string('ghs_diagnosis_code')->nullable(); // GHS specific diagnosis code
            $table->enum('severity', ['mild', 'moderate', 'severe', 'critical'])->nullable();
            $table->enum('urgency', ['routine', 'urgent', 'emergency'])->default('routine');
            
            // Referral Information
            $table->boolean('requires_referral')->default(false);
            $table->string('referral_specialty')->nullable();
            $table->text('referral_reason')->nullable();
            $table->enum('referral_urgency', ['routine', 'urgent', 'emergency'])->nullable();
            
            // Insurance & Billing
            $table->boolean('nhis_eligible')->default(false);
            $table->string('nhis_scheme')->nullable();
            $table->string('nhis_number')->nullable();
            $table->decimal('consultation_fee', 10, 2)->nullable();
            $table->decimal('nhis_coverage', 10, 2)->nullable();
            $table->decimal('patient_co_pay', 10, 2)->nullable();
            
            // Quality Assurance
            $table->boolean('peer_reviewed')->default(false);
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            
            // Audit Trail
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes
            $table->index(['patient_id', 'consultation_date']);
            $table->index(['doctor_id', 'consultation_date']);
            $table->index(['branch_id', 'consultation_date']);
            $table->index('icd_10_code');
            $table->index('consultation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};