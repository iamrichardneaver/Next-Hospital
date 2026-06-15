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
        Schema::create('teleconsultation_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teleconsultation_id')->constrained()->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_url');
            $table->string('file_type');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->enum('file_category', ['prescription', 'lab_result', 'scan', 'document', 'image', 'other'])->default('other');
            $table->text('description')->nullable();
            $table->boolean('is_shared_with_patient')->default(true);
            $table->boolean('requires_consent')->default(false);
            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->string('encryption_key')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['teleconsultation_id', 'file_category']);
            $table->index(['uploaded_by', 'created_at']);
            $table->index(['is_shared_with_patient', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teleconsultation_files');
    }
};