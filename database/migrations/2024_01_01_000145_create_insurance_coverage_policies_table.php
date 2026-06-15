<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_coverage_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurance_provider_id');
            $table->unsignedBigInteger('service_category_id')->nullable();
            $table->string('service_type', 255);
            $table->string('service_code', 255)->nullable();
            $table->decimal('coverage_percentage', 5, 2)->default(80.00);
            $table->decimal('co_pay_percentage', 5, 2)->default(20.00);
            $table->decimal('max_coverage_amount', 12, 2)->nullable();
            $table->decimal('min_coverage_amount', 12, 2)->default(0.00);
            $table->decimal('deductible', 10, 2)->default(0.00);
            $table->boolean('requires_pre_authorization')->default(0);
            $table->integer('pre_authorization_days')->nullable();
            $table->longText('coverage_conditions')->nullable();
            $table->longText('exclusions')->nullable();
            $table->longText('age_restrictions')->nullable();
            $table->longText('gender_restrictions')->nullable();
            $table->boolean('is_active')->default(1);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['service_category_id', 'is_active'], 'icp_category_active_idx');
            $table->index(['effective_from', 'effective_until'], 'icp_effective_idx');
            $table->index(['insurance_provider_id', 'service_type', 'is_active'], 'icp_provider_service_idx');
$table->foreign('created_by', 'insurance_coverage_policies_created_by_foreign')->references('id')->on('users')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('insurance_provider_id', 'insurance_coverage_policies_insurance_provider_id_foreign')->references('id')->on('insurance_providers')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('service_category_id', 'insurance_coverage_policies_service_category_id_foreign')->references('id')->on('insurance_service_categories')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_coverage_policies');
    }
};
