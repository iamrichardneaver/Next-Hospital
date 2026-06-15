<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_coverage', function (Blueprint $table) {
            $table->id();
            $table->string('service_id', 255);
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('insurance_provider_id');
            $table->string('policy_number', 255);
            $table->decimal('coverage_percentage', 5, 2);
            $table->decimal('max_coverage_amount', 10, 2)->nullable();
            $table->decimal('co_pay_percentage', 5, 2)->default(0.00);
            $table->boolean('requires_pre_authorization')->default(0);
            $table->boolean('is_active')->default(1);
            $table->date('valid_from');
            $table->date('valid_until');
            $table->longText('coverage_details')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['insurance_provider_id', 'is_active']);
            $table->index(['service_id', 'patient_id', 'is_active']);
$table->foreign('created_by', 'insurance_coverage_created_by_foreign')->references('id')->on('users')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('insurance_provider_id', 'insurance_coverage_insurance_provider_id_foreign')->references('id')->on('insurance_providers')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('patient_id', 'insurance_coverage_patient_id_foreign')->references('id')->on('patients')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_coverage');
    }
};
