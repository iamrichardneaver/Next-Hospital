<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('consultation_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('scan_number', 255);
            $table->string('scan_type', 255);
            $table->text('scan_description');
            $table->text('clinical_notes')->nullable();
            $table->enum('priority', ['routine', 'urgent', 'stat'])->default('routine');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('technician_id')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->text('report')->nullable();
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id'], 'scans_branch_id_foreign');
            $table->index(['consultation_id'], 'scans_consultation_id_foreign');
            $table->index(['doctor_id'], 'scans_doctor_id_foreign');
            $table->index(['patient_id'], 'scans_patient_id_foreign');
            $table->index(['reported_by'], 'scans_reported_by_foreign');
            $table->unique(['scan_number']);
            $table->index(['technician_id'], 'scans_technician_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
