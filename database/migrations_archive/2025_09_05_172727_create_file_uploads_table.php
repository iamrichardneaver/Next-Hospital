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
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('filename');
            $table->string('path');
            $table->enum('category', ['patient_photo', 'medical_image', 'lab_result', 'prescription', 'insurance_document', 'consultation_note', 'other']);
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->string('related_type');
            $table->unsignedBigInteger('related_id');
            $table->text('description')->nullable();
            $table->boolean('is_private')->default(false);
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['related_type', 'related_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_uploads');
    }
};
