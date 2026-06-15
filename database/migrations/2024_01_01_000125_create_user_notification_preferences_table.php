<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('audio_enabled')->default(1);
            $table->integer('audio_volume')->default(80);
            $table->string('notification_sound', 255)->default('standard');
            $table->boolean('notify_opd_queue')->default(1);
            $table->boolean('notify_lab_queue')->default(1);
            $table->boolean('notify_pharmacy_queue')->default(1);
            $table->boolean('notify_emergency_queue')->default(1);
            $table->boolean('notify_triage_queue')->default(1);
            $table->boolean('notify_routine')->default(1);
            $table->boolean('notify_urgent')->default(1);
            $table->boolean('notify_critical')->default(1);
            $table->boolean('notify_new_patient')->default(1);
            $table->boolean('notify_patient_waiting')->default(1);
            $table->boolean('notify_prescription_ready')->default(1);
            $table->boolean('notify_lab_result_ready')->default(1);
            $table->boolean('notify_consultation_required')->default(1);
            $table->integer('check_interval')->default(30);
            $table->boolean('desktop_notification')->default(1);
            $table->boolean('do_not_disturb')->default(0);
            $table->time('dnd_start')->nullable();
            $table->time('dnd_end')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
