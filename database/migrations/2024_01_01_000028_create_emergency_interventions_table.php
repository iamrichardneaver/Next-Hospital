<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_interventions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('emergency_visit_id');
            $table->enum('intervention_type', ['medication', 'procedure', 'lab_order', 'imaging', 'consultation', 'transfer']);
            $table->text('description');
            $table->unsignedBigInteger('medication_id')->nullable();
            $table->string('dosage', 255)->nullable();
            $table->string('frequency', 255)->nullable();
            $table->string('procedure_code', 255)->nullable();
            $table->longText('lab_tests')->nullable();
            $table->string('imaging_type', 255)->nullable();
            $table->string('consultation_specialty', 255)->nullable();
            $table->string('transfer_destination', 255)->nullable();
            $table->enum('priority', ['immediate', 'urgent', 'routine']);
            $table->enum('status', ['ordered', 'in_progress', 'completed', 'cancelled'])->default('ordered');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('ordered_by');
            $table->timestamp('ordered_at')->useCurrent();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['emergency_visit_id'], 'emergency_interventions_emergency_visit_id_foreign');
            $table->index(['medication_id'], 'emergency_interventions_medication_id_foreign');
            $table->index(['ordered_by'], 'emergency_interventions_ordered_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_interventions');
    }
};
