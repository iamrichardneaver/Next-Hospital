<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vitals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id')->nullable();
            $table->integer('blood_pressure_systolic')->nullable();
            $table->integer('blood_pressure_diastolic')->nullable();
            $table->integer('pulse_rate')->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->integer('oxygen_saturation')->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('bmi', 4, 1)->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['consultation_id'], 'vitals_consultation_id_foreign');
            $table->index(['recorded_by'], 'vitals_recorded_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vitals');
    }
};
