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
        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('insurance_provider_id');
            $table->string('policy_number');
            $table->string('coverage_type'); // individual, family, group, corporate
            $table->string('policy_holder_name')->nullable();
            $table->string('policy_holder_relationship')->nullable(); // self, spouse, child, parent
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('coverage_percentage', 5, 2)->default(80.00);
            $table->decimal('co_pay_percentage', 5, 2)->default(20.00);
            $table->decimal('annual_limit', 12, 2)->nullable();
            $table->decimal('lifetime_limit', 12, 2)->nullable();
            $table->decimal('deductible', 10, 2)->default(0.00);
            $table->decimal('co_pay_amount', 10, 2)->default(0.00);
            $table->json('covered_services')->nullable(); // Services covered by this policy
            $table->json('excluded_services')->nullable(); // Services not covered
            $table->json('special_conditions')->nullable(); // Special policy conditions
            $table->boolean('requires_pre_authorization')->default(false);
            $table->boolean('is_primary')->default(true); // Primary or secondary insurance
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('insurance_provider_id')->references('id')->on('insurance_providers')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->unique(['patient_id', 'policy_number', 'insurance_provider_id'], 'insurance_policies_unique');
            $table->index(['patient_id', 'is_active']);
            $table->index(['insurance_provider_id', 'is_active']);
            $table->index('policy_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_policies');
    }
};
