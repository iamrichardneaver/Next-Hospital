<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_studies', function (Blueprint $table) {
            $table->id();
            $table->string('study_uid', 255);
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('modality_id');
            $table->unsignedBigInteger('equipment_id');
            $table->string('study_description', 255);
            $table->text('study_notes')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->dateTime('study_date')->nullable();
            $table->dateTime('completed_date')->nullable();
            $table->unsignedBigInteger('technician_id')->nullable();
            $table->unsignedBigInteger('radiologist_id')->nullable();
            $table->text('technique_notes')->nullable();
            $table->longText('study_parameters')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['equipment_id'], 'radiology_studies_equipment_id_foreign');
            $table->index(['modality_id'], 'radiology_studies_modality_id_foreign');
            $table->index(['patient_id'], 'radiology_studies_patient_id_foreign');
            $table->index(['radiologist_id'], 'radiology_studies_radiologist_id_foreign');
            $table->index(['request_id'], 'radiology_studies_request_id_foreign');
            $table->unique(['study_uid']);
            $table->index(['technician_id'], 'radiology_studies_technician_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_studies');
    }
};
