<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('consultation_id');
            $table->unsignedBigInteger('workflow_instance_id')->nullable();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('prescription_number', 255);
            $table->date('prescription_date');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->enum('billing_status', ['pending', 'billed', 'paid', 'partial'])->default('pending');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->decimal('billing_amount', 10, 2)->nullable();
            $table->timestamp('billed_at')->nullable();
            $table->index(['branch_id'], 'prescriptions_branch_id_foreign');
            $table->index(['consultation_id'], 'prescriptions_consultation_id_foreign');
            $table->index(['created_by'], 'prescriptions_created_by_foreign');
            $table->index(['doctor_id'], 'prescriptions_doctor_id_foreign');
            $table->index(['patient_id'], 'prescriptions_patient_id_foreign');
            $table->unique(['prescription_number']);
            $table->index(['workflow_instance_id']);
$table->foreign('invoice_id', 'prescriptions_invoice_id_foreign')->references('id')->on('invoices')->onDelete('set null')->onUpdate('restrict');
$table->foreign('workflow_instance_id', 'prescriptions_workflow_instance_id_foreign')->references('id')->on('workflow_instances')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
