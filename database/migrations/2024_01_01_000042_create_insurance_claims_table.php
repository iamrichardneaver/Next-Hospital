<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('insurance_provider_id');
            $table->unsignedBigInteger('policy_id');
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('claim_number', 255);
            $table->string('service_type', 255);
            $table->date('service_date');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('covered_amount', 12, 2);
            $table->decimal('co_pay_amount', 12, 2);
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'paid', 'cancelled'])->nullable()->default('draft');
            $table->date('submitted_date')->nullable();
            $table->date('processed_date')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['claim_number'], 'claim_number');
            $table->index(['patient_id'], 'patient_id');
            $table->index(['policy_id'], 'policy_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claims');
    }
};
