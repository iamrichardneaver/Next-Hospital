<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('original_name', 255);
            $table->string('filename', 255);
            $table->string('path', 255);
            $table->enum('category', ['patient_photo', 'medical_image', 'lab_result', 'prescription', 'insurance_document', 'consultation_note', 'other']);
            $table->string('mime_type', 255);
            $table->bigInteger('size');
            $table->string('related_type', 255);
            $table->unsignedBigInteger('related_id');
            $table->text('description')->nullable();
            $table->boolean('is_private')->default(0);
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['related_type', 'related_id']);
            $table->index(['uploaded_by'], 'file_uploads_uploaded_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_uploads');
    }
};
