<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_allergies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->string('allergen', 255);
            $table->string('reaction', 255);
            $table->enum('severity', ['mild', 'moderate', 'severe']);
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['patient_id'], 'patient_allergies_patient_id_foreign');
            $table->index(['recorded_by'], 'patient_allergies_recorded_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_allergies');
    }
};
