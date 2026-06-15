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
        Schema::table('consultations', function (Blueprint $table) {
            // Add missing basic history columns first
            $table->text('past_medical_history')->nullable()->after('history_of_present_illness');
            $table->text('family_history')->nullable()->after('past_medical_history');
            $table->text('social_history')->nullable()->after('family_history');
            $table->text('drug_history')->nullable()->after('social_history');
            $table->text('allergy_history')->nullable()->after('drug_history');
            
            // Enhanced Past Medical History with checkboxes
            $table->json('past_medical_history_details')->nullable()->after('allergy_history');
            $table->text('past_medical_history_others')->nullable()->after('past_medical_history_details');
            
            // Enhanced Drug History
            $table->json('current_medications')->nullable()->after('past_medical_history_others');
            $table->json('drug_allergies')->nullable()->after('current_medications');
            $table->text('past_drug_usage')->nullable()->after('drug_allergies');
            
            // Enhanced Social History
            $table->json('social_history_details')->nullable()->after('past_drug_usage');
            
            // Examination Section - General Examination
            $table->text('general_examination')->nullable()->after('physical_examination');
            
            // Vitals Entry Fields
            $table->decimal('blood_pressure_systolic', 5, 2)->nullable()->after('general_examination');
            $table->decimal('blood_pressure_diastolic', 5, 2)->nullable()->after('blood_pressure_systolic');
            $table->integer('pulse_rate')->nullable()->after('blood_pressure_diastolic');
            $table->decimal('temperature', 4, 2)->nullable()->after('pulse_rate');
            $table->integer('respiratory_rate')->nullable()->after('temperature');
            $table->integer('oxygen_saturation')->nullable()->after('respiratory_rate');
            $table->decimal('height', 5, 2)->nullable()->after('oxygen_saturation');
            $table->decimal('weight', 5, 2)->nullable()->after('height');
            $table->decimal('bmi', 4, 2)->nullable()->after('weight');
            
            // System-specific Examinations
            $table->text('cardiovascular_examination')->nullable()->after('bmi');
            $table->text('respiratory_examination')->nullable()->after('cardiovascular_examination');
            $table->text('abdominal_examination')->nullable()->after('respiratory_examination');
            $table->text('neurological_examination')->nullable()->after('abdominal_examination');
            
            // Additional Features
            $table->boolean('is_draft')->default(false)->after('consultation_status');
            $table->string('template_used')->nullable()->after('is_draft');
            
            // Doctor's Impression and Diagnosis
            $table->text('doctors_impression')->nullable()->after('assessment');
            $table->json('icd_10_codes')->nullable()->after('icd_10_code');
            
            // Treatment Plan Details
            $table->text('medications_prescribed')->nullable()->after('treatment_plan');
            $table->text('investigations_ordered')->nullable()->after('medications_prescribed');
            $table->text('referrals_made')->nullable()->after('investigations_ordered');
            
            // File Attachments
            $table->json('attached_files')->nullable()->after('referrals_made');
            
            // Follow-up Information
            $table->text('follow_up_instructions')->nullable()->after('attached_files');
            $table->date('next_appointment_date')->nullable()->after('follow_up_instructions');
            $table->text('next_appointment_notes')->nullable()->after('next_appointment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn([
                'past_medical_history',
                'family_history',
                'social_history',
                'drug_history',
                'allergy_history',
                'past_medical_history_details',
                'past_medical_history_others',
                'current_medications',
                'drug_allergies',
                'past_drug_usage',
                'social_history_details',
                'general_examination',
                'blood_pressure_systolic',
                'blood_pressure_diastolic',
                'pulse_rate',
                'temperature',
                'respiratory_rate',
                'oxygen_saturation',
                'height',
                'weight',
                'bmi',
                'cardiovascular_examination',
                'respiratory_examination',
                'abdominal_examination',
                'neurological_examination',
                'is_draft',
                'template_used',
                'doctors_impression',
                'icd_10_codes',
                'medications_prescribed',
                'investigations_ordered',
                'referrals_made',
                'attached_files',
                'follow_up_instructions',
                'next_appointment_date',
                'next_appointment_notes'
            ]);
        });
    }
};