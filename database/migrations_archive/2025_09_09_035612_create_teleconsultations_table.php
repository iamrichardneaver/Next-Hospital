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
        Schema::create('teleconsultations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('consultation_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            
            // Teleconsultation specific fields
            $table->string('meeting_id')->unique();
            $table->string('meeting_password')->nullable();
            $table->string('meeting_url')->nullable();
            $table->enum('status', ['scheduled', 'waiting', 'in_progress', 'completed', 'cancelled', 'failed'])->default('scheduled');
            $table->enum('consultation_type', ['video', 'audio', 'chat'])->default('video');
            $table->text('consultation_notes')->nullable();
            $table->json('technical_notes')->nullable(); // For technical issues, connection quality, etc.
            
            // Timing fields
            $table->timestamp('scheduled_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            
            // Quality and technical fields
            $table->enum('connection_quality', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->boolean('video_enabled')->default(true);
            $table->boolean('audio_enabled')->default(true);
            $table->boolean('screen_sharing_enabled')->default(false);
            $table->boolean('recording_enabled')->default(false);
            $table->string('recording_url')->nullable();
            
            // Patient consent and preferences
            $table->boolean('patient_consent_given')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->json('patient_preferences')->nullable(); // Language, accessibility needs, etc.
            
            // Emergency and safety
            $table->boolean('emergency_contact_notified')->default(false);
            $table->text('emergency_notes')->nullable();
            $table->boolean('safety_check_completed')->default(false);
            
            // Follow-up and outcomes
            $table->boolean('requires_follow_up')->default(false);
            $table->text('follow_up_notes')->nullable();
            $table->timestamp('follow_up_scheduled_at')->nullable();
            $table->enum('outcome', ['successful', 'technical_issues', 'patient_no_show', 'doctor_unavailable', 'cancelled'])->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Indexes
            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['scheduled_at']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teleconsultations');
    }
};