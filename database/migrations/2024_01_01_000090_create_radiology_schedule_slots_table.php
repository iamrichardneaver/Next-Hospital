<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id');
            $table->date('slot_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['available', 'booked', 'maintenance', 'blocked'])->default('available');
            $table->unsignedBigInteger('booked_by')->nullable();
            $table->unsignedBigInteger('study_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['booked_by'], 'radiology_schedule_slots_booked_by_foreign');
            $table->index(['equipment_id'], 'radiology_schedule_slots_equipment_id_foreign');
            $table->index(['study_id'], 'radiology_schedule_slots_study_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_schedule_slots');
    }
};
