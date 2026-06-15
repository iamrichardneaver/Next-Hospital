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
        Schema::create('vitals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id');
            $table->integer('blood_pressure_systolic')->nullable(); // mmHg
            $table->integer('blood_pressure_diastolic')->nullable(); // mmHg
            $table->integer('pulse_rate')->nullable(); // beats per minute
            $table->integer('respiratory_rate')->nullable(); // breaths per minute
            $table->decimal('temperature', 4, 1)->nullable(); // Celsius
            $table->integer('oxygen_saturation')->nullable(); // %
            $table->decimal('height', 5, 2)->nullable(); // cm
            $table->decimal('weight', 5, 2)->nullable(); // kg
            $table->decimal('bmi', 4, 1)->nullable(); // auto-calculated
            $table->timestamp('recorded_at');
            $table->unsignedBigInteger('recorded_by');
            $table->timestamps();

            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vitals');
    }
};