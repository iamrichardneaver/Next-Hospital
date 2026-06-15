<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('branch_id');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();
            $table->integer('slot_duration')->default(30);
            $table->integer('max_appointments_per_slot')->default(1);
            $table->boolean('is_available')->default(1);
            $table->text('notes')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id', 'day_of_week']);
            $table->index(['created_by'], 'doctor_schedules_created_by_foreign');
            $table->index(['doctor_id', 'is_available']);
            $table->unique(['doctor_id', 'branch_id', 'day_of_week', 'effective_from'], 'doctor_schedules_unique');
            $table->index(['updated_by'], 'doctor_schedules_updated_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
