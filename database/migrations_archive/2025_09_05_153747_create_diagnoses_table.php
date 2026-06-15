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
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id');
            $table->string('icd_code')->nullable(); // International Classification of Diseases code
            $table->text('diagnosis_description');
            $table->enum('diagnosis_type', ['primary', 'secondary', 'differential'])->default('primary');
            $table->enum('confidence_level', ['confirmed', 'probable', 'possible'])->default('confirmed');
            $table->unsignedBigInteger('diagnosed_by');
            $table->date('diagnosis_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
            $table->foreign('diagnosed_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnoses');
    }
};