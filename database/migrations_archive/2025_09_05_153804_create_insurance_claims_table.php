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
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('insurance_provider_id');
            $table->unsignedBigInteger('policy_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('claim_number')->unique();
            $table->string('external_claim_id')->nullable(); // External system claim ID
            $table->string('service_type'); // consultation, lab, pharmacy, procedure, etc.
            $table->date('service_date');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('covered_amount', 12, 2);
            $table->decimal('co_pay_amount', 12, 2);
            $table->decimal('deductible_amount', 12, 2)->default(0.00);
            $table->decimal('processed_amount', 12, 2)->nullable();
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'paid', 'cancelled'])->default('draft');
            $table->date('submitted_date')->nullable();
            $table->date('processed_date')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('claim_items')->nullable(); // Detailed claim items
            $table->json('attachments')->nullable(); // Supporting documents
            $table->boolean('requires_pre_authorization')->default(false);
            $table->unsignedBigInteger('pre_authorization_id')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('insurance_provider_id')->references('id')->on('insurance_providers')->onDelete('cascade');
            $table->foreign('policy_id')->references('id')->on('insurance_policies')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('pre_authorization_id')->references('id')->on('pre_authorizations')->onDelete('set null');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['patient_id', 'status']);
            $table->index(['insurance_provider_id', 'status']);
            $table->index(['service_date', 'status']);
            $table->index('claim_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_claims');
    }
};
