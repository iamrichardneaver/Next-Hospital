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
        Schema::create('emergency_interventions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('emergency_visit_id');
            $table->enum('intervention_type', ['medication', 'procedure', 'lab_order', 'imaging', 'consultation', 'transfer']);
            $table->text('description');
            $table->unsignedBigInteger('medication_id')->nullable();
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->string('procedure_code')->nullable();
            $table->json('lab_tests')->nullable();
            $table->string('imaging_type')->nullable();
            $table->string('consultation_specialty')->nullable();
            $table->string('transfer_destination')->nullable();
            $table->enum('priority', ['immediate', 'urgent', 'routine']);
            $table->enum('status', ['ordered', 'in_progress', 'completed', 'cancelled'])->default('ordered');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('ordered_by');
            $table->timestamp('ordered_at');
            $table->timestamps();

            $table->foreign('emergency_visit_id')->references('id')->on('emergency_visits')->onDelete('cascade');
            $table->foreign('medication_id')->references('id')->on('drugs')->onDelete('set null');
            $table->foreign('ordered_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_interventions');
    }
};
