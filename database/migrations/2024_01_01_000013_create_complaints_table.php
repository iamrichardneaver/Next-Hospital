<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_number', 255);
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('complainant_name', 255);
            $table->string('complainant_phone', 255)->nullable();
            $table->string('complainant_email', 255)->nullable();
            $table->enum('complainant_type', ['patient', 'visitor', 'staff', 'other'])->default('patient');
            $table->string('subject', 255);
            $table->text('description');
            $table->enum('category', ['service_quality', 'staff_behavior', 'wait_time', 'billing', 'cleanliness', 'medical_care', 'facilities', 'other'])->default('other');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['pending', 'under_review', 'investigating', 'resolved', 'closed', 'rejected'])->default('pending');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->text('response')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->longText('attachments')->nullable();
            $table->boolean('requires_follow_up')->default(0);
            $table->date('follow_up_date')->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['assigned_to'], 'complaints_assigned_to_foreign');
            $table->index(['branch_id'], 'complaints_branch_id_foreign');
            $table->index(['category']);
            $table->index(['complaint_number']);
            $table->unique(['complaint_number']);
            $table->index(['created_at']);
            $table->index(['created_by'], 'complaints_created_by_foreign');
            $table->index(['patient_id']);
            $table->index(['priority']);
            $table->index(['resolved_by'], 'complaints_resolved_by_foreign');
            $table->index(['status']);
            $table->index(['updated_by'], 'complaints_updated_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
