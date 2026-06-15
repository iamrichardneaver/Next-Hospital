<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgery_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('surgeon_id');
            $table->unsignedBigInteger('theatre_id');
            $table->unsignedBigInteger('procedure_id');
            $table->string('surgery_number', 255);
            $table->date('surgery_date');
            $table->time('surgery_time');
            $table->integer('estimated_duration');
            $table->timestamp('actual_start_time')->nullable();
            $table->timestamp('actual_end_time')->nullable();
            $table->timestamp('anesthesia_start_time')->nullable();
            $table->timestamp('anesthesia_end_time')->nullable();
            $table->timestamp('incision_time')->nullable();
            $table->timestamp('closure_time')->nullable();
            $table->integer('recovery_room_time')->nullable();
            $table->enum('priority', ['elective', 'urgent', 'emergency'])->default('elective');
            $table->enum('surgery_type', ['major', 'minor', 'diagnostic', 'therapeutic']);
            $table->enum('anesthesia_type', ['general', 'regional', 'local', 'conscious_sedation']);
            $table->text('pre_op_instructions')->nullable();
            $table->text('post_op_instructions')->nullable();
            $table->text('special_requirements')->nullable();
            $table->longText('equipment_required')->nullable();
            $table->longText('pre_op_checklist')->nullable();
            $table->text('post_op_notes')->nullable();
            $table->text('complications')->nullable();
            $table->decimal('blood_loss', 8, 2)->nullable();
            $table->longText('vital_signs')->nullable();
            $table->text('discharge_instructions')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'postponed'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('started_by')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('branch_id');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id'], 'surgery_schedules_branch_id_foreign');
            $table->index(['completed_by'], 'surgery_schedules_completed_by_foreign');
            $table->index(['created_by'], 'surgery_schedules_created_by_foreign');
            $table->index(['patient_id', 'status']);
            $table->index(['procedure_id'], 'surgery_schedules_procedure_id_foreign');
            $table->index(['started_by'], 'surgery_schedules_started_by_foreign');
            $table->index(['status']);
            $table->index(['surgeon_id', 'surgery_date']);
            $table->index(['surgery_number']);
            $table->unique(['surgery_number']);
            $table->index(['theatre_id', 'surgery_date', 'surgery_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surgery_schedules');
    }
};
