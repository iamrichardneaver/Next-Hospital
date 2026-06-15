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
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lab_request_id');
            $table->unsignedBigInteger('test_type_id')->nullable();
            $table->string('parameter_name');
            $table->string('parameter_code')->nullable(); // Internal parameter code
            $table->string('result_value');
            $table->string('unit')->nullable();
            $table->string('reference_range')->nullable();
            $table->string('age_group')->nullable(); // For age-specific ranges
            $table->string('gender')->nullable(); // For gender-specific ranges
            $table->enum('result_status', ['normal', 'abnormal', 'critical', 'pending', 'cancelled'])->default('normal');
            $table->enum('abnormal_flag', ['H', 'L', 'HH', 'LL', 'CRITICAL', 'DELTA'])->nullable(); // H=High, L=Low, etc.
            $table->decimal('delta_check_value', 10, 4)->nullable(); // For delta checks
            $table->text('notes')->nullable();
            $table->text('clinical_notes')->nullable(); // Clinical interpretation
            $table->text('technical_notes')->nullable(); // Technical notes
            $table->json('quality_control_data')->nullable(); // QC results
            $table->json('calibration_data')->nullable(); // Calibration information
            $table->string('methodology')->nullable(); // Test method used
            $table->string('equipment_used')->nullable(); // Equipment used
            $table->string('reagent_lot')->nullable(); // Reagent lot number
            $table->date('reagent_expiry')->nullable(); // Reagent expiry date
            $table->timestamp('test_performed_at')->nullable();
            $table->timestamp('result_entered_at')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable(); // Technician who performed test
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable(); // Consultant approval
            $table->timestamp('approved_at')->nullable();
            $table->boolean('requires_repeat')->default(false);
            $table->text('repeat_reason')->nullable();
            $table->unsignedBigInteger('repeat_requested_by')->nullable();
            $table->timestamp('repeat_requested_at')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('lab_request_id')->references('id')->on('lab_requests')->onDelete('cascade');
            $table->foreign('test_type_id')->references('id')->on('lab_test_types')->onDelete('cascade');
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('repeat_requested_by')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes
            $table->index(['lab_request_id', 'parameter_name']);
            $table->index('result_status');
            $table->index('abnormal_flag');
            $table->index('test_performed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};