<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_pricing', function (Blueprint $table) {
            $table->id();
            $table->string('service_id', 255);
            $table->string('service_name', 255);
            $table->string('service_type', 255);
            $table->string('pricing_type', 32)->default('standalone');
            $table->boolean('is_additive')->default(1);
            $table->longText('module_codes')->nullable();
            $table->string('applies_on', 32)->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->decimal('base_price', 10, 2);
            $table->string('currency', 3)->default('GHS');
            $table->text('description')->nullable();
            $table->longText('pricing_tiers')->nullable();
            $table->boolean('is_active')->default(1);
            $table->boolean('requires_approval')->default(0);
            $table->longText('metadata')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id'], 'service_pricing_branch_id_foreign');
            $table->index(['created_by'], 'service_pricing_created_by_foreign');
            $table->index(['is_active', 'service_type']);
            $table->unique(['service_id', 'branch_id'], 'service_pricing_service_branch_unique');
            $table->index(['service_type', 'branch_id']);
            $table->index(['updated_by'], 'service_pricing_updated_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_pricing');
    }
};
