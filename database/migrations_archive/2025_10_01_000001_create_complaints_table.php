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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_number')->unique();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->onDelete('set null');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Complainant Information (can be patient or anyone else)
            $table->string('complainant_name');
            $table->string('complainant_phone')->nullable();
            $table->string('complainant_email')->nullable();
            $table->enum('complainant_type', ['patient', 'visitor', 'staff', 'other'])->default('patient');
            
            // Complaint Details
            $table->string('subject');
            $table->text('description');
            $table->enum('category', [
                'service_quality',
                'staff_behavior',
                'wait_time',
                'billing',
                'cleanliness',
                'medical_care',
                'facilities',
                'other'
            ])->default('other');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', [
                'pending',
                'under_review',
                'investigating',
                'resolved',
                'closed',
                'rejected'
            ])->default('pending');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            
            // Assignment and Resolution
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('response')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Attachments
            $table->json('attachments')->nullable();
            
            // Follow-up
            $table->boolean('requires_follow_up')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->text('follow_up_notes')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('complaint_number');
            $table->index('patient_id');
            $table->index('status');
            $table->index('category');
            $table->index('priority');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};

