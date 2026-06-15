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
        Schema::create('insurance_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('private'); // private, government, nhis
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('website')->nullable();
            $table->json('api_endpoints')->nullable(); // API configuration
            $table->json('api_credentials')->nullable(); // Encrypted API credentials
            $table->decimal('default_coverage_percentage', 5, 2)->default(80.00);
            $table->decimal('default_co_pay_percentage', 5, 2)->default(20.00);
            $table->boolean('requires_pre_authorization')->default(false);
            $table->boolean('supports_electronic_claims')->default(false);
            $table->boolean('supports_real_time_verification')->default(false);
            $table->json('claim_settings')->nullable(); // Claim processing settings
            $table->json('coverage_limits')->nullable(); // Annual, lifetime limits
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['type', 'is_active']);
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_providers');
    }
};
