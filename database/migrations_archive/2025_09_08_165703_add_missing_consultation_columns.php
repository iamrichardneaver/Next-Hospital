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
            // Add missing columns that the controller expects
            $table->string('icd_10_code')->nullable()->after('plan');
            $table->string('ghs_diagnosis_code')->nullable()->after('icd_10_code');
            $table->enum('severity', ['mild', 'moderate', 'severe', 'critical'])->nullable()->after('ghs_diagnosis_code');
            $table->enum('urgency', ['routine', 'urgent', 'emergency'])->nullable()->after('severity');
            $table->string('nhis_scheme')->nullable()->after('nhis_eligible');
            $table->string('nhis_number')->nullable()->after('nhis_scheme');
            $table->decimal('consultation_fee', 10, 2)->nullable()->after('nhis_number');
            $table->string('referral_specialty')->nullable()->after('requires_referral');
            $table->text('referral_reason')->nullable()->after('referral_specialty');
            
            // Add additional medical history fields
            $table->text('past_medical_history')->nullable()->after('history_of_present_illness');
            $table->text('family_history')->nullable()->after('past_medical_history');
            $table->text('social_history')->nullable()->after('family_history');
            $table->text('drug_history')->nullable()->after('social_history');
            $table->text('allergy_history')->nullable()->after('drug_history');
            $table->json('physical_examination')->nullable()->after('allergy_history');
            $table->json('vitals')->nullable()->after('physical_examination');
            $table->json('diagnoses')->nullable()->after('vitals');
            $table->json('treatment_plan')->nullable()->after('diagnoses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn([
                'icd_10_code',
                'ghs_diagnosis_code',
                'severity',
                'urgency',
                'nhis_scheme',
                'nhis_number',
                'consultation_fee',
                'referral_specialty',
                'referral_reason',
                'past_medical_history',
                'family_history',
                'social_history',
                'drug_history',
                'allergy_history',
                'physical_examination',
                'vitals',
                'diagnoses',
                'treatment_plan'
            ]);
        });
    }
};
