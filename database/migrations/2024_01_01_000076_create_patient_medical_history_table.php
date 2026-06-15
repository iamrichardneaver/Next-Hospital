<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_medical_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->string('condition', 255);
            $table->date('diagnosis_date');
            $table->enum('status', ['active', 'resolved', 'chronic']);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['patient_id'], 'patient_medical_history_patient_id_foreign');
            $table->index(['recorded_by'], 'patient_medical_history_recorded_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_medical_history');
    }
};
