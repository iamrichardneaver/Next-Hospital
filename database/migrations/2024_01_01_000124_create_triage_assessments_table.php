<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triage_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('emergency_visit_id');
            $table->integer('triage_level');
            $table->longText('vital_signs');
            $table->text('chief_complaint');
            $table->text('assessment_notes')->nullable();
            $table->unsignedBigInteger('assessed_by');
            $table->timestamp('assessment_time')->useCurrent();
            $table->text('reassessment_reason')->nullable();
            $table->unsignedBigInteger('reassessed_by')->nullable();
            $table->timestamp('reassessment_time')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['assessed_by'], 'triage_assessments_assessed_by_foreign');
            $table->index(['emergency_visit_id'], 'triage_assessments_emergency_visit_id_foreign');
            $table->index(['reassessed_by'], 'triage_assessments_reassessed_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triage_assessments');
    }
};
