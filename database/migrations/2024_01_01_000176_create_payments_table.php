<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_reference', 255)->nullable();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('branch_id');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 50);
            $table->enum('source_platform', ['web', 'mobile', 'api', 'webhook', 'system'])->default('web');
            $table->string('device_info', 255)->nullable();
            $table->string('ip_address', 255)->nullable();
            $table->string('transaction_id', 255)->nullable();
            $table->string('reference_number', 255)->nullable();
            $table->date('payment_date')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('processed_by');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('processed_at')->useCurrent();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->longText('metadata')->nullable();
            $table->index(['branch_id']);
            $table->index(['invoice_id'], 'payments_invoice_id_foreign');
            $table->index(['patient_id', 'branch_id']);
            $table->index(['patient_id']);
            $table->index(['payment_date', 'status']);
            $table->unique(['payment_reference']);
            $table->index(['processed_by'], 'payments_processed_by_foreign');
            $table->index(['processed_by', 'payment_date']);
            $table->index(['source_platform']);
$table->foreign('branch_id', 'payments_branch_id_foreign')->references('id')->on('branches')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('patient_id', 'payments_patient_id_foreign')->references('id')->on('patients')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
