<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eye_test_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 255);
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('consultation_id')->nullable();
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->text('clinical_notes')->nullable();
            $table->text('reason_for_test')->nullable();
            $table->enum('priority', ['routine', 'urgent', 'emergency'])->default('routine');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled', 'failed'])->default('pending');
            $table->boolean('requires_dilation')->default(0);
            $table->boolean('dilation_completed')->default(0);
            $table->timestamp('dilation_time')->nullable();
            $table->text('dilation_notes')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('actual_duration_minutes')->nullable();
            $table->boolean('has_results')->default(0);
            $table->timestamp('results_entered_at')->nullable();
            $table->timestamp('results_verified_at')->nullable();
            $table->unsignedBigInteger('results_entered_by')->nullable();
            $table->unsignedBigInteger('results_verified_by')->nullable();
            $table->boolean('quality_control_passed')->default(0);
            $table->text('quality_control_notes')->nullable();
            $table->unsignedBigInteger('quality_control_by')->nullable();
            $table->timestamp('quality_control_at')->nullable();
            $table->decimal('service_cost', 10, 2)->nullable();
            $table->boolean('billing_processed')->default(0);
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['assigned_to', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['created_by'], 'eye_test_requests_created_by_foreign');
            $table->index(['patient_id', 'status']);
            $table->index(['quality_control_by'], 'eye_test_requests_quality_control_by_foreign');
            $table->index(['requested_by'], 'eye_test_requests_requested_by_foreign');
            $table->index(['request_number']);
            $table->unique(['request_number']);
            $table->index(['results_entered_by'], 'eye_test_requests_results_entered_by_foreign');
            $table->index(['results_verified_by'], 'eye_test_requests_results_verified_by_foreign');
            $table->index(['service_id'], 'eye_test_requests_service_id_foreign');
            $table->index(['template_id'], 'eye_test_requests_template_id_foreign');
$table->foreign('appointment_id', 'eye_test_requests_appointment_id_foreign')->references('id')->on('appointments')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('branch_id', 'eye_test_requests_branch_id_foreign')->references('id')->on('branches')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('consultation_id', 'eye_test_requests_consultation_id_foreign')->references('id')->on('consultations')->onDelete('set null')->onUpdate('restrict');
$table->foreign('patient_id', 'eye_test_requests_patient_id_foreign')->references('id')->on('patients')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eye_test_requests');
    }
};
