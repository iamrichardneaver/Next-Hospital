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
        Schema::create('lab_test_types', function (Blueprint $table) {
            $table->id();
            $table->string('test_code')->unique();
            $table->string('test_name');
            $table->string('category'); // Hematology, Biochemistry, Microbiology, etc.
            $table->string('subcategory')->nullable(); // Full Blood Count, Liver Function, etc.
            $table->text('description')->nullable();
            $table->string('specimen_type'); // Blood, Urine, Stool, Sputum, etc.
            $table->string('collection_method')->nullable();
            $table->json('preparation_instructions')->nullable(); // Patient preparation
            $table->json('collection_instructions')->nullable(); // Collection procedure
            $table->json('storage_requirements')->nullable(); // Temperature, time limits
            $table->json('transport_requirements')->nullable(); // Transport conditions
            
            // Test Parameters
            $table->json('parameters')->nullable(); // Array of test parameters
            $table->json('normal_ranges')->nullable(); // Age/gender specific ranges
            $table->json('critical_values')->nullable(); // Critical value thresholds
            $table->json('units')->nullable(); // Measurement units
            
            // Turnaround Times
            $table->integer('routine_tat_hours')->default(24); // Routine turnaround time
            $table->integer('urgent_tat_hours')->default(4); // Urgent turnaround time
            $table->integer('stat_tat_hours')->default(1); // STAT turnaround time
            
            // Pricing
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('nhis_cost', 10, 2)->nullable();
            $table->boolean('nhis_covered')->default(false);
            
            // Quality Control
            $table->boolean('requires_qc')->default(true);
            $table->json('qc_requirements')->nullable();
            $table->boolean('requires_verification')->default(true);
            $table->json('verification_requirements')->nullable();
            
            // Equipment & Reagents
            $table->string('equipment_required')->nullable();
            $table->json('reagents_required')->nullable();
            $table->string('methodology')->nullable(); // Test methodology
            
            // Ghanaian Standards
            $table->string('ghs_code')->nullable(); // GHS test code
            $table->boolean('ghs_mandatory')->default(false); // GHS mandatory reporting
            $table->json('ghs_reporting_requirements')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_doctor_approval')->default(false);
            $table->boolean('requires_consultant_review')->default(false);
            
            // Audit Trail
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('test_code');
            $table->index('category');
            $table->index('is_active');
            $table->index('nhis_covered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_test_types');
    }
};
