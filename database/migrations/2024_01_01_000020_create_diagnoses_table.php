<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id');
            $table->string('icd_code', 255)->nullable();
            $table->text('diagnosis_description');
            $table->enum('diagnosis_type', ['primary', 'secondary', 'differential'])->default('primary');
            $table->enum('confidence_level', ['confirmed', 'probable', 'possible'])->default('confirmed');
            $table->unsignedBigInteger('diagnosed_by');
            $table->date('diagnosis_date');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['consultation_id'], 'diagnoses_consultation_id_foreign');
            $table->index(['diagnosed_by'], 'diagnoses_diagnosed_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnoses');
    }
};
