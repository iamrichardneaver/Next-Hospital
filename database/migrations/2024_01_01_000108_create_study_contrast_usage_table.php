<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_contrast_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('study_id');
            $table->unsignedBigInteger('contrast_agent_id');
            $table->decimal('dose_ml', 8, 2);
            $table->string('route', 255);
            $table->dateTime('administered_at');
            $table->unsignedBigInteger('administered_by');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['administered_by'], 'study_contrast_usage_administered_by_foreign');
            $table->index(['contrast_agent_id'], 'study_contrast_usage_contrast_agent_id_foreign');
            $table->index(['study_id'], 'study_contrast_usage_study_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_contrast_usage');
    }
};
