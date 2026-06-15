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
        Schema::create('consultation_interventions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id');
            $table->enum('intervention_type', ['medication', 'procedure', 'lab_order', 'imaging_order', 'referral', 'counseling', 'lifestyle_advice']);
            $table->text('description');
            $table->unsignedBigInteger('medication_id')->nullable(); // FK to drugs table
            $table->text('dosage_instructions')->nullable();
            $table->string('frequency')->nullable(); // e.g., "once daily", "as needed"
            $table->string('duration')->nullable(); // e.g., "7 days"
            $table->string('procedure_code')->nullable(); // e.g., CPT code for procedures
            $table->unsignedBigInteger('lab_test_id')->nullable(); // FK to lab_requests
            $table->unsignedBigInteger('imaging_id')->nullable(); // FK to scans
            $table->enum('priority', ['routine', 'urgent'])->default('routine');
            $table->enum('status', ['ordered', 'completed', 'cancelled'])->default('ordered');
            $table->unsignedBigInteger('ordered_by');
            $table->timestamp('ordered_at');
            $table->timestamps();

            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
            $table->foreign('ordered_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_interventions');
    }
};