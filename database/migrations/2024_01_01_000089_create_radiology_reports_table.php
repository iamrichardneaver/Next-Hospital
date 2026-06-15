<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('study_id');
            $table->unsignedBigInteger('radiologist_id');
            $table->text('findings')->nullable();
            $table->text('impression')->nullable();
            $table->text('recommendations')->nullable();
            $table->enum('status', ['draft', 'preliminary', 'final', 'amended', 'cancelled'])->default('draft');
            $table->dateTime('dictated_date')->nullable();
            $table->dateTime('transcribed_date')->nullable();
            $table->dateTime('signed_date')->nullable();
            $table->unsignedBigInteger('transcribed_by')->nullable();
            $table->text('amendment_reason')->nullable();
            $table->longText('selected_images')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['radiologist_id'], 'radiology_reports_radiologist_id_foreign');
            $table->index(['study_id'], 'radiology_reports_study_id_foreign');
            $table->index(['transcribed_by'], 'radiology_reports_transcribed_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_reports');
    }
};
