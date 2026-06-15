<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teleconsultations', function (Blueprint $table) {
            $table->id();
            $table->string('teleconsultation_number', 255)->nullable();
            $table->char('uuid', 36);
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('consultation_id')->nullable();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('meeting_id', 255);
            $table->string('meeting_password', 255)->nullable();
            $table->string('meeting_url', 255)->nullable();
            $table->enum('status', ['scheduled', 'waiting', 'in_progress', 'completed', 'cancelled', 'failed'])->default('scheduled');
            $table->enum('consultation_type', ['video', 'audio', 'chat'])->default('video');
            $table->text('consultation_notes')->nullable();
            $table->longText('technical_notes')->nullable();
            $table->timestamp('scheduled_at')->useCurrent();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->enum('connection_quality', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->boolean('video_enabled')->default(1);
            $table->boolean('audio_enabled')->default(1);
            $table->boolean('screen_sharing_enabled')->default(0);
            $table->boolean('recording_enabled')->default(0);
            $table->string('recording_url', 255)->nullable();
            $table->boolean('patient_consent_given')->default(0);
            $table->timestamp('consent_given_at')->nullable();
            $table->longText('patient_preferences')->nullable();
            $table->boolean('emergency_contact_notified')->default(0);
            $table->text('emergency_notes')->nullable();
            $table->boolean('safety_check_completed')->default(0);
            $table->boolean('requires_follow_up')->default(0);
            $table->text('follow_up_notes')->nullable();
            $table->timestamp('follow_up_scheduled_at')->nullable();
            $table->enum('outcome', ['successful', 'technical_issues', 'patient_no_show', 'doctor_unavailable', 'cancelled'])->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['appointment_id'], 'teleconsultations_appointment_id_foreign');
            $table->index(['branch_id', 'status']);
            $table->index(['consultation_id'], 'teleconsultations_consultation_id_foreign');
            $table->index(['created_by'], 'teleconsultations_created_by_foreign');
            $table->index(['doctor_id', 'status']);
            $table->unique(['meeting_id']);
            $table->index(['patient_id', 'status']);
            $table->index(['scheduled_at']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['updated_by'], 'teleconsultations_updated_by_foreign');
            $table->unique(['uuid']);
            $table->unique(['teleconsultation_number'], 'teleconsultation_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teleconsultations');
    }
};
