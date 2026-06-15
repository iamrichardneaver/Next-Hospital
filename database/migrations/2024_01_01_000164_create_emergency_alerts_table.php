<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('emergency_visit_id');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->enum('alert_type', ['critical_triage', 'patient_arrival', 'intervention_required', 'equipment_needed', 'staff_required']);
            $table->text('message');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent']);
            $table->string('location', 255)->nullable();
            $table->enum('status', ['active', 'acknowledged', 'resolved'])->default('active');
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('acknowledgment_notes')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['acknowledged_by'], 'emergency_alerts_acknowledged_by_foreign');
            $table->index(['created_by'], 'emergency_alerts_created_by_foreign');
            $table->index(['emergency_visit_id'], 'emergency_alerts_emergency_visit_id_foreign');
            $table->index(['resolved_by'], 'emergency_alerts_resolved_by_foreign');
$table->foreign('patient_id', 'emergency_alerts_patient_id_foreign')->references('id')->on('patients')->onDelete('set null')->onUpdate('restrict');
$table->foreign('updated_by', 'emergency_alerts_updated_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_alerts');
    }
};
