<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_interventions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id');
            $table->enum('intervention_type', ['medication', 'procedure', 'lab_order', 'imaging_order', 'referral', 'counseling', 'lifestyle_advice']);
            $table->text('description');
            $table->unsignedBigInteger('medication_id')->nullable();
            $table->text('dosage_instructions')->nullable();
            $table->string('frequency', 255)->nullable();
            $table->string('duration', 255)->nullable();
            $table->string('procedure_code', 255)->nullable();
            $table->unsignedBigInteger('lab_test_id')->nullable();
            $table->unsignedBigInteger('imaging_id')->nullable();
            $table->enum('priority', ['routine', 'urgent'])->default('routine');
            $table->enum('status', ['ordered', 'completed', 'cancelled'])->default('ordered');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('ordered_by');
            $table->timestamp('ordered_at')->useCurrent();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['consultation_id'], 'consultation_interventions_consultation_id_foreign');
            $table->index(['ordered_by'], 'consultation_interventions_ordered_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_interventions');
    }
};
