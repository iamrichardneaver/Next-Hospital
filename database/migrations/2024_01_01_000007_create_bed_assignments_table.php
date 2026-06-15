<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bed_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('bed_id');
            $table->unsignedBigInteger('ward_id');
            $table->unsignedBigInteger('assigned_by');
            $table->timestamp('admission_date')->useCurrent();
            $table->timestamp('discharge_date')->nullable();
            $table->enum('status', ['active', 'discharged', 'transferred'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['assigned_by'], 'bed_assignments_assigned_by_foreign');
            $table->index(['bed_id'], 'bed_assignments_bed_id_foreign');
            $table->index(['patient_id'], 'bed_assignments_patient_id_foreign');
            $table->index(['visit_id'], 'bed_assignments_visit_id_foreign');
            $table->index(['ward_id'], 'bed_assignments_ward_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bed_assignments');
    }
};
