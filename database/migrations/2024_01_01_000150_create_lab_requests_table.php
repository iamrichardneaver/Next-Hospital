<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_requests', function (Blueprint $table) {
            $table->id();
            $table->string('lab_request_number', 255)->nullable();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('consultation_id')->nullable();
            $table->unsignedBigInteger('workflow_instance_id')->nullable();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('test_type_id')->nullable();
            $table->unsignedBigInteger('test_category_id')->nullable();
            $table->string('test_category_name', 255)->nullable();
            $table->string('test_type_name', 255)->nullable();
            $table->boolean('has_multiple_templates')->default(0);
            $table->integer('total_templates')->default(0);
            $table->integer('completed_templates')->default(0);
            $table->enum('overall_status', ['pending', 'partial', 'completed', 'cancelled'])->default('pending');
            $table->string('request_number', 255);
            $table->string('test_type', 255);
            $table->text('test_description');
            $table->string('specimen_type', 255)->nullable();
            $table->longText('collection_instructions')->nullable();
            $table->text('special_instructions')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->enum('priority', ['routine', 'urgent', 'stat'])->default('routine');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('technician_id')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('inventory_deducted_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->enum('billing_status', ['pending', 'billed', 'paid', 'partial'])->default('pending');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->decimal('billing_amount', 10, 2)->nullable();
            $table->timestamp('billed_at')->nullable();
            $table->index(['status'], 'idx_lab_requests_status');
            $table->index(['branch_id'], 'lab_requests_branch_id_foreign');
            $table->index(['consultation_id'], 'lab_requests_consultation_id_foreign');
            $table->index(['created_by'], 'lab_requests_created_by_foreign');
            $table->index(['doctor_id'], 'lab_requests_doctor_id_foreign');
            $table->index(['has_multiple_templates']);
            $table->unique(['lab_request_number']);
            $table->index(['overall_status']);
            $table->index(['patient_id'], 'lab_requests_patient_id_foreign');
            $table->unique(['request_number']);
            $table->index(['technician_id'], 'lab_requests_technician_id_foreign');
            $table->index(['template_id']);
            $table->index(['test_category_id'], 'lab_requests_test_category_id_foreign');
            $table->index(['test_type_id']);
            $table->index(['updated_by'], 'lab_requests_updated_by_foreign');
            $table->index(['workflow_instance_id']);
$table->foreign('invoice_id', 'lab_requests_invoice_id_foreign')->references('id')->on('invoices')->onDelete('set null')->onUpdate('restrict');
$table->foreign('workflow_instance_id', 'lab_requests_workflow_instance_id_foreign')->references('id')->on('workflow_instances')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_requests');
    }
};
