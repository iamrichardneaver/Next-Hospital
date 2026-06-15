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
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            
            // Audio notification settings
            $table->boolean('audio_enabled')->default(true);
            $table->integer('audio_volume')->default(80); // 0-100
            $table->string('notification_sound')->default('standard'); // standard, urgent, critical, custom
            
            // Queue-specific notifications
            $table->boolean('notify_opd_queue')->default(true);
            $table->boolean('notify_lab_queue')->default(true);
            $table->boolean('notify_pharmacy_queue')->default(true);
            $table->boolean('notify_emergency_queue')->default(true);
            $table->boolean('notify_triage_queue')->default(true);
            
            // Priority-based notifications
            $table->boolean('notify_routine')->default(true);
            $table->boolean('notify_urgent')->default(true);
            $table->boolean('notify_critical')->default(true);
            
            // Workflow stage notifications
            $table->boolean('notify_new_patient')->default(true); // New patient in my queue
            $table->boolean('notify_patient_waiting')->default(true); // Patient waiting too long
            $table->boolean('notify_prescription_ready')->default(true); // Prescription ready to dispense
            $table->boolean('notify_lab_result_ready')->default(true); // Lab result ready
            $table->boolean('notify_consultation_required')->default(true); // Doctor consultation needed
            
            // Additional settings
            $table->integer('check_interval')->default(30); // Seconds between checks (for polling)
            $table->boolean('desktop_notification')->default(true); // Browser desktop notifications
            $table->boolean('do_not_disturb')->default(false); // Temporarily mute all
            $table->time('dnd_start')->nullable(); // Do not disturb start time
            $table->time('dnd_end')->nullable(); // Do not disturb end time
            
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};

