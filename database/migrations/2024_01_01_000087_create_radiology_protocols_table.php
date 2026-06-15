<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_protocols', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->unsignedBigInteger('modality_id');
            $table->string('body_part', 255);
            $table->text('description');
            $table->longText('technical_parameters');
            $table->text('patient_preparation');
            $table->text('contraindications');
            $table->boolean('requires_contrast')->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['modality_id'], 'radiology_protocols_modality_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_protocols');
    }
};
