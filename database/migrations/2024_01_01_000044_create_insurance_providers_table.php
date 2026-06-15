<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('code', 255);
            $table->string('type', 255)->default('private');
            $table->string('contact_person', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('website', 255)->nullable();
            $table->longText('api_endpoints')->nullable();
            $table->longText('api_credentials')->nullable();
            $table->decimal('default_coverage_percentage', 5, 2)->default(80.00);
            $table->decimal('default_co_pay_percentage', 5, 2)->default(20.00);
            $table->boolean('requires_pre_authorization')->default(0);
            $table->boolean('supports_electronic_claims')->default(0);
            $table->boolean('supports_real_time_verification')->default(0);
            $table->longText('claim_settings')->nullable();
            $table->longText('coverage_limits')->nullable();
            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['code'], 'code');
            $table->index(['code']);
            $table->index(['created_by'], 'insurance_providers_created_by_foreign');
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_providers');
    }
};
