<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 255);
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('consultation_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('modality_id');
            $table->unsignedBigInteger('department_id');
            $table->text('clinical_history')->nullable();
            $table->text('clinical_question')->nullable();
            $table->text('indication')->nullable();
            $table->enum('priority', ['routine', 'urgent', 'stat', 'emergency'])->default('routine');
            $table->enum('status', ['requested', 'scheduled', 'in_progress', 'completed', 'cancelled', 'rejected'])->default('requested');
            $table->date('requested_date');
            $table->date('scheduled_date')->nullable();
            $table->time('scheduled_time')->nullable();
            $table->unsignedBigInteger('technician_id')->nullable();
            $table->unsignedBigInteger('radiologist_id')->nullable();
            $table->text('technician_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->enum('billing_status', ['pending', 'billed', 'paid', 'partial'])->default('pending');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->decimal('billing_amount', 10, 2)->nullable();
            $table->timestamp('billed_at')->nullable();
            $table->index(['branch_id'], 'radiology_requests_branch_id_foreign');
            $table->index(['consultation_id']);
            $table->index(['department_id'], 'radiology_requests_department_id_foreign');
            $table->index(['doctor_id'], 'radiology_requests_doctor_id_foreign');
            $table->index(['invoice_id'], 'radiology_requests_invoice_id_foreign');
            $table->index(['modality_id'], 'radiology_requests_modality_id_foreign');
            $table->index(['radiologist_id'], 'radiology_requests_radiologist_id_foreign');
            $table->unique(['request_number']);
            $table->index(['technician_id'], 'radiology_requests_technician_id_foreign');
$table->foreign('patient_id', 'radiology_requests_patient_id_foreign')->references('id')->on('patients')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_requests');
    }
};
