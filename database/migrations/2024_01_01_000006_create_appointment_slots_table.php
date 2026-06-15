<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('branch_id');
            $table->date('slot_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration')->default(30);
            $table->integer('max_appointments')->default(1);
            $table->integer('booked_appointments')->default(0);
            $table->enum('status', ['available', 'booked', 'blocked', 'maintenance'])->default('available');
            $table->decimal('fee', 10, 2)->nullable();
            $table->string('currency', 3)->default('GHS');
            $table->enum('appointment_type', ['in-person', 'teleconsultation'])->default('in-person');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id', 'slot_date']);
            $table->index(['created_by'], 'appointment_slots_created_by_foreign');
            $table->index(['doctor_id', 'slot_date', 'status']);
            $table->index(['slot_date', 'start_time']);
            $table->index(['updated_by'], 'appointment_slots_updated_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_slots');
    }
};
