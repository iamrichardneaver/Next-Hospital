<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescription_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('pharmacist_id')->nullable();
            $table->enum('notification_type', ['prescription_ready', 'dispensed', 'completed', 'cancelled', 'interaction_alert', 'stock_issue']);
            $table->string('title', 255);
            $table->text('message');
            $table->enum('status', ['pending', 'sent', 'read', 'failed'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['doctor_id'], 'prescription_notifications_doctor_id_foreign');
            $table->index(['patient_id'], 'prescription_notifications_patient_id_foreign');
            $table->index(['pharmacist_id'], 'prescription_notifications_pharmacist_id_foreign');
            $table->index(['prescription_id'], 'prescription_notifications_prescription_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_notifications');
    }
};
