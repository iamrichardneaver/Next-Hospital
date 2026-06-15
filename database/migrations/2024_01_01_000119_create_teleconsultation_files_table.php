<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teleconsultation_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teleconsultation_id');
            $table->unsignedBigInteger('uploaded_by');
            $table->string('file_name', 255);
            $table->string('file_path', 255);
            $table->string('file_url', 255);
            $table->string('file_type', 255);
            $table->string('mime_type', 255);
            $table->integer('file_size');
            $table->enum('file_category', ['prescription', 'lab_result', 'scan', 'document', 'image', 'other'])->default('other');
            $table->text('description')->nullable();
            $table->boolean('is_shared_with_patient')->default(1);
            $table->boolean('requires_consent')->default(0);
            $table->boolean('consent_given')->default(0);
            $table->timestamp('consent_given_at')->nullable();
            $table->boolean('is_encrypted')->default(0);
            $table->string('encryption_key', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['is_shared_with_patient', 'created_at']);
            $table->index(['teleconsultation_id', 'file_category']);
            $table->index(['uploaded_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teleconsultation_files');
    }
};
