<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration creates the complete database structure as it currently exists
     */
    public function up(): void
    {
        // Create users table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('staff_id')->unique()->nullable();
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('profile_picture')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // Create branches table
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_number')->unique()->nullable();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('address');
            $table->string('phone');
            $table->string('email');
            $table->string('timezone')->default('Africa/Accra');
            $table->boolean('is_active')->default(true);
            $table->longText('settings')->nullable();
            $table->timestamps();
        });

        // Create patients table
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('patient_number')->unique();
            $table->string('first_name');
            $table->string('other_names')->nullable();
            $table->string('last_name');
            $table->enum('gender', ['Male', 'Female']);
            $table->date('date_of_birth');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('nhis_number')->nullable();
            $table->string('ghana_card_number')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('photo')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
        });

        // Create visits table
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_token')->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('branch_id');
            $table->enum('visit_type', ['OPD', 'IPD', 'Emergency', 'LabOnly', 'PharmacyOnly']);
            $table->enum('status', ['active', 'completed', 'cancelled', 'transferred'])->default('active');
            $table->timestamp('check_in_time')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('check_out_time')->nullable();
            $table->unsignedBigInteger('assigned_doctor_id')->nullable();
            $table->unsignedBigInteger('assigned_nurse_id')->nullable();
            $table->text('chief_complaint')->nullable();
            $table->text('visit_notes')->nullable();
            $table->longText('vital_signs')->nullable();
            $table->enum('priority', ['routine', 'urgent', 'critical'])->default('routine');
            $table->string('referral_source')->nullable();
            $table->text('referral_notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('assigned_doctor_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('assigned_nurse_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
            $table->index('visit_type');
            $table->index('check_in_time');
        });

        // Create appointments table
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number')->unique()->nullable();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('branch_id');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->text('reason')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no-show'])->default('scheduled');
            $table->enum('appointment_type', ['in-person', 'teleconsultation'])->default('in-person');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->boolean('is_teleconsultation')->default(false);
            $table->unsignedBigInteger('teleconsultation_id')->nullable();
            $table->string('meeting_url')->nullable();
            $table->string('meeting_password')->nullable();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('teleconsultation_id')->references('id')->on('teleconsultations')->onDelete('set null');
            $table->index('is_teleconsultation');
        });

        // Create consultations table
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->string('consultation_number')->unique()->nullable();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('visit_id')->nullable();
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
            $table->longText('workflow_steps')->nullable();
            $table->string('next_stage')->nullable();
            $table->date('next_appointment_date')->nullable();
            $table->text('next_appointment_notes')->nullable();
            $table->string('icd_10_code', 20)->nullable();
            $table->longText('icd_10_codes')->nullable();
            $table->enum('severity', ['mild', 'moderate', 'severe'])->nullable();
            $table->enum('urgency', ['routine', 'urgent', 'critical'])->default('routine');
            $table->enum('consultation_status', ['ongoing', 'completed', 'cancelled'])->default('ongoing');
            $table->boolean('is_draft')->default(false);
            $table->string('template_used')->nullable();
            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('assessment')->nullable();
            $table->text('doctors_impression')->nullable();
            $table->text('plan')->nullable();
            $table->boolean('nhis_eligible')->default(false);
            $table->boolean('requires_referral')->default(false);
            $table->text('referral_notes')->nullable();
            $table->string('referral_specialty', 100)->nullable();
            $table->string('referral_reason', 500)->nullable();
            $table->longText('reception_notes')->nullable();
            $table->longText('doctor_remarks')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('visit_id')->references('id')->on('visits')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Create lab_requests table
        Schema::create('lab_requests', function (Blueprint $table) {
            $table->id();
            $table->string('lab_request_number')->unique()->nullable();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('consultation_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('test_type_id')->nullable();
            $table->unsignedBigInteger('test_category_id')->nullable();
            $table->string('test_category_name')->nullable();
            $table->string('test_type_name')->nullable();
            $table->boolean('has_multiple_templates')->default(false);
            $table->integer('total_templates')->default(0);
            $table->integer('completed_templates')->default(0);
            $table->enum('overall_status', ['pending', 'partial', 'completed', 'cancelled'])->default('pending');
            $table->string('request_number')->unique();
            $table->string('test_type');
            $table->text('test_description');
            $table->string('specimen_type')->nullable();
            $table->longText('collection_instructions')->nullable();
            $table->text('special_instructions')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->enum('priority', ['routine', 'urgent', 'stat'])->default('routine');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('technician_id')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('lab_test_templates')->onDelete('set null');
            $table->foreign('test_type_id')->references('id')->on('lab_test_types')->onDelete('set null');
            $table->foreign('test_category_id')->references('id')->on('lab_test_categories')->onDelete('set null');
            $table->foreign('technician_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->index('overall_status');
        });

        // Create lab_results table
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->string('lab_result_number')->unique()->nullable();
            $table->unsignedBigInteger('lab_request_id');
            $table->string('test_name');
            $table->text('result_value');
            $table->string('unit')->nullable();
            $table->text('reference_range')->nullable();
            $table->enum('result_status', ['normal', 'abnormal', 'critical'])->default('normal');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('lab_request_id')->references('id')->on('lab_requests')->onDelete('cascade');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
        });

        // Create invoices table
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->longText('items')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->enum('status', ['draft', 'pending', 'paid', 'cancelled', 'refunded'])->default('draft');
            $table->enum('payment_method', ['cash', 'card', 'momo', 'insurance', 'bank_transfer'])->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Create vitals table
        Schema::create('vitals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id')->nullable();
            $table->integer('blood_pressure_systolic')->nullable();
            $table->integer('blood_pressure_diastolic')->nullable();
            $table->integer('pulse_rate')->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->integer('oxygen_saturation')->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('bmi', 4, 1)->nullable();
            $table->timestamp('recorded_at')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamps();

            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vitals');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('lab_results');
        Schema::dropIfExists('lab_requests');
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('users');
    }
};
