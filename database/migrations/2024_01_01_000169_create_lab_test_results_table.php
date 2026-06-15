<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_test_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lab_request_id');
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('parameter_id');
            $table->string('parameter_code', 255);
            $table->string('parameter_name', 255);
            $table->text('result_value')->nullable();
            $table->text('formatted_value')->nullable();
            $table->string('unit', 255)->nullable();
            $table->string('reference_range', 255)->nullable();
            $table->string('age_group', 255)->nullable();
            $table->string('gender', 255)->nullable();
            $table->boolean('is_pregnant')->default(0);
            $table->enum('result_status', ['normal', 'abnormal', 'critical', 'pending', 'cancelled', 'repeated'])->default('pending');
            $table->enum('verification_status', ['pending', 'verified', 'approved', 'rejected'])->default('pending');
            $table->enum('abnormal_flag', ['H', 'L', 'HH', 'LL', 'CRITICAL', 'DELTA', 'PANIC', 'POS'])->nullable();
            $table->decimal('delta_check_value', 10, 4)->nullable();
            $table->decimal('previous_value', 10, 4)->nullable();
            $table->text('clinical_interpretation')->nullable();
            $table->text('technical_notes')->nullable();
            $table->text('quality_control_notes')->nullable();
            $table->longText('quality_control_data')->nullable();
            $table->longText('calibration_data')->nullable();
            $table->string('methodology_used', 255)->nullable();
            $table->string('equipment_used', 255)->nullable();
            $table->string('reagent_lot_number', 255)->nullable();
            $table->date('reagent_expiry_date')->nullable();
            $table->string('technician_notes', 255)->nullable();
            $table->timestamp('test_performed_at')->nullable();
            $table->timestamp('result_entered_at')->nullable();
            $table->timestamp('result_verified_at')->nullable();
            $table->timestamp('result_approved_at')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->boolean('requires_repeat')->default(0);
            $table->text('repeat_reason')->nullable();
            $table->unsignedBigInteger('repeat_requested_by')->nullable();
            $table->timestamp('repeat_requested_at')->nullable();
            $table->boolean('is_critical_alert_sent')->default(0);
            $table->timestamp('critical_alert_sent_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['abnormal_flag']);
            $table->index(['approved_by'], 'lab_test_results_approved_by_foreign');
            $table->index(['is_critical_alert_sent']);
            $table->index(['lab_request_id', 'parameter_id']);
            $table->index(['parameter_id'], 'lab_test_results_parameter_id_foreign');
            $table->index(['performed_by'], 'lab_test_results_performed_by_foreign');
            $table->index(['repeat_requested_by'], 'lab_test_results_repeat_requested_by_foreign');
            $table->index(['result_status']);
            $table->index(['template_id'], 'lab_test_results_template_id_foreign');
            $table->index(['verified_by'], 'lab_test_results_verified_by_foreign');
$table->foreign('lab_request_id', 'lab_test_results_lab_request_id_foreign')->references('id')->on('lab_requests')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_test_results');
    }
};
