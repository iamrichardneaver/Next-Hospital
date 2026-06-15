<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visit_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('branch_id');
            $table->enum('queue_type', ['OPD', 'Lab', 'Pharmacy', 'Emergency', 'Radiology']);
            $table->string('ticket_number', 20)->nullable();
            $table->integer('position');
            $table->enum('status', ['waiting', 'called', 'serving', 'completed', 'cancelled', 'no_show'])->default('waiting');
            $table->timestamp('queued_at')->useCurrent();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('serving_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('called_by')->nullable();
            $table->unsignedBigInteger('served_by')->nullable();
            $table->text('notes')->nullable();
            $table->integer('estimated_wait_time')->nullable();
            $table->enum('priority', ['routine', 'urgent', 'critical'])->default('routine');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['status', 'queue_type'], 'idx_queues_status_type');
            $table->index(['branch_id'], 'queues_branch_id_foreign');
            $table->index(['called_by'], 'queues_called_by_foreign');
            $table->index(['patient_id'], 'queues_patient_id_foreign');
            $table->index(['position']);
            $table->index(['queued_at']);
            $table->unique(['queue_type', 'branch_id', 'position']);
            $table->index(['queue_type', 'status']);
            $table->index(['served_by'], 'queues_served_by_foreign');
            $table->index(['ticket_number']);
            $table->index(['visit_id'], 'queues_visit_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
