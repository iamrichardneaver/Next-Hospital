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
        Schema::create('triage_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('emergency_visit_id');
            $table->integer('triage_level')->comment('1=Critical, 2=Urgent, 3=Less Urgent, 4=Non-urgent, 5=Minor');
            $table->json('vital_signs');
            $table->text('chief_complaint');
            $table->text('assessment_notes')->nullable();
            $table->unsignedBigInteger('assessed_by');
            $table->timestamp('assessment_time');
            $table->text('reassessment_reason')->nullable();
            $table->unsignedBigInteger('reassessed_by')->nullable();
            $table->timestamp('reassessment_time')->nullable();
            $table->timestamps();

            $table->foreign('emergency_visit_id')->references('id')->on('emergency_visits')->onDelete('cascade');
            $table->foreign('assessed_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reassessed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('triage_assessments');
    }
};
