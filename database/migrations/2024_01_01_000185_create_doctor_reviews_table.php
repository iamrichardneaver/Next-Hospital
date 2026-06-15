<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['appointment_id']);
            $table->index(['doctor_id', 'created_at']);
            $table->index(['patient_id', 'doctor_id']);
$table->foreign('appointment_id', 'doctor_reviews_appointment_id_foreign')->references('id')->on('appointments')->onDelete('set null')->onUpdate('restrict');
$table->foreign('doctor_id', 'doctor_reviews_doctor_id_foreign')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('patient_id', 'doctor_reviews_patient_id_foreign')->references('id')->on('patients')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_reviews');
    }
};
