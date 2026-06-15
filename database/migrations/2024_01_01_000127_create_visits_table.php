<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_token', 255);
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('branch_id');
            $table->enum('visit_type', ['OPD', 'IPD', 'Emergency', 'LabOnly', 'PharmacyOnly', 'RadiologyOnly']);
            $table->enum('status', ['active', 'completed', 'cancelled', 'transferred'])->default('active');
            $table->unsignedBigInteger('workflow_instance_id')->nullable();
            $table->timestamp('check_in_time')->useCurrent();
            $table->timestamp('check_out_time')->nullable();
            $table->unsignedBigInteger('assigned_doctor_id')->nullable();
            $table->unsignedBigInteger('assigned_nurse_id')->nullable();
            $table->text('chief_complaint')->nullable();
            $table->text('visit_notes')->nullable();
            $table->longText('vital_signs')->nullable();
            $table->enum('priority', ['routine', 'urgent', 'critical'])->default('routine');
            $table->string('referral_source', 255)->nullable();
            $table->text('referral_notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['check_in_time'], 'idx_visits_check_in_time');
            $table->index(['assigned_doctor_id'], 'visits_assigned_doctor_id_foreign');
            $table->index(['assigned_nurse_id'], 'visits_assigned_nurse_id_foreign');
            $table->index(['branch_id'], 'visits_branch_id_foreign');
            $table->index(['check_in_time']);
            $table->index(['created_by'], 'visits_created_by_foreign');
            $table->index(['patient_id', 'visit_type']);
            $table->index(['updated_by'], 'visits_updated_by_foreign');
            $table->unique(['visit_token']);
            $table->index(['visit_type', 'status']);
            $table->index(['workflow_instance_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
