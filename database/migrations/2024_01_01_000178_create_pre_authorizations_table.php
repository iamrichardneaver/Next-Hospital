<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_authorizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('insurance_provider_id');
            $table->unsignedBigInteger('policy_id');
            $table->string('pre_auth_number', 255);
            $table->string('external_pre_auth_id', 255)->nullable();
            $table->string('service_type', 255);
            $table->string('service_code', 255)->nullable();
            $table->text('service_description');
            $table->decimal('requested_amount', 12, 2);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('co_pay_amount', 12, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired', 'cancelled'])->default('pending');
            $table->enum('urgency', ['routine', 'urgent', 'emergency'])->default('routine');
            $table->date('request_date');
            $table->date('service_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('approved_date')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->longText('attachments')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['insurance_provider_id', 'status']);
            $table->index(['patient_id', 'status']);
            $table->index(['pre_auth_number']);
            $table->unique(['pre_auth_number']);
            $table->index(['request_date', 'status']);
$table->foreign('approved_by', 'pre_authorizations_approved_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
$table->foreign('insurance_provider_id', 'pre_authorizations_insurance_provider_id_foreign')->references('id')->on('insurance_providers')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('patient_id', 'pre_authorizations_patient_id_foreign')->references('id')->on('patients')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('policy_id', 'pre_authorizations_policy_id_foreign')->references('id')->on('insurance_policies')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('requested_by', 'pre_authorizations_requested_by_foreign')->references('id')->on('users')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_authorizations');
    }
};
