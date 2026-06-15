<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('insurance_provider_id');
            $table->string('policy_number', 255);
            $table->string('coverage_type', 255);
            $table->string('policy_holder_name', 255)->nullable();
            $table->string('policy_holder_relationship', 255)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('coverage_percentage', 5, 2)->default(80.00);
            $table->decimal('co_pay_percentage', 5, 2)->default(20.00);
            $table->decimal('annual_limit', 12, 2)->nullable();
            $table->decimal('lifetime_limit', 12, 2)->nullable();
            $table->decimal('deductible', 10, 2)->default(0.00);
            $table->decimal('co_pay_amount', 10, 2)->default(0.00);
            $table->longText('covered_services')->nullable();
            $table->longText('excluded_services')->nullable();
            $table->longText('special_conditions')->nullable();
            $table->boolean('requires_pre_authorization')->default(0);
            $table->boolean('is_primary')->default(1);
            $table->boolean('is_active')->default(1);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['created_by'], 'insurance_policies_created_by_foreign');
            $table->index(['insurance_provider_id'], 'insurance_policies_insurance_provider_id_foreign');
            $table->index(['patient_id'], 'insurance_policies_patient_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_policies');
    }
};
