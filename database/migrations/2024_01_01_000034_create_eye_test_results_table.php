<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eye_test_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_request_id');
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
            $table->enum('result_status', ['normal', 'abnormal', 'critical', 'pending', 'cancelled'])->default('pending');
            $table->enum('abnormal_flag', ['H', 'L', 'HH', 'LL', 'CRITICAL', 'ABNORMAL'])->nullable();
            $table->text('clinical_interpretation')->nullable();
            $table->text('technical_notes')->nullable();
            $table->longText('equipment_used')->nullable();
            $table->longText('test_conditions')->nullable();
            $table->string('methodology_used', 255)->nullable();
            $table->timestamp('test_performed_at')->nullable();
            $table->timestamp('result_entered_at')->nullable();
            $table->timestamp('result_verified_at')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->boolean('requires_repeat')->default(0);
            $table->text('repeat_reason')->nullable();
            $table->boolean('is_critical_alert_sent')->default(0);
            $table->timestamp('critical_alert_sent_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['abnormal_flag']);
            $table->index(['parameter_id'], 'eye_test_results_parameter_id_foreign');
            $table->index(['performed_by'], 'eye_test_results_performed_by_foreign');
            $table->index(['result_status']);
            $table->index(['template_id'], 'eye_test_results_template_id_foreign');
            $table->index(['test_request_id', 'parameter_id']);
            $table->index(['verified_by'], 'eye_test_results_verified_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eye_test_results');
    }
};
