<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eye_services', function (Blueprint $table) {
            $table->id();
            $table->string('service_code', 255);
            $table->string('service_name', 255);
            $table->text('description')->nullable();
            $table->string('category', 255);
            $table->string('subcategory', 255)->nullable();
            $table->enum('service_type', ['examination', 'test', 'treatment', 'consultation', 'procedure'])->default('examination');
            $table->text('instructions')->nullable();
            $table->integer('duration_minutes')->default(30);
            $table->boolean('requires_doctor')->default(1);
            $table->boolean('requires_equipment')->default(0);
            $table->longText('equipment_required')->nullable();
            $table->longText('preparation_instructions')->nullable();
            $table->longText('post_service_instructions')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->decimal('nhis_price', 10, 2)->nullable();
            $table->boolean('nhis_covered')->default(0);
            $table->string('currency', 3)->default('GHS');
            $table->string('ghs_code', 255)->nullable();
            $table->boolean('ghs_mandatory')->default(0);
            $table->longText('ghs_reporting_requirements')->nullable();
            $table->boolean('is_active')->default(1);
            $table->boolean('requires_approval')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['created_by'], 'eye_services_created_by_foreign');
            $table->index(['is_active']);
            $table->unique(['service_code']);
            $table->index(['service_type', 'category']);
            $table->index(['updated_by'], 'eye_services_updated_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eye_services');
    }
};
