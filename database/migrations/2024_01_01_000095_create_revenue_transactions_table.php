<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_reference', 255);
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('source_type', 255);
            $table->unsignedBigInteger('source_id');
            $table->enum('service_type', ['consultation', 'pharmacy', 'lab', 'imaging', 'ward', 'surgery', 'ecommerce', 'insurance', 'other'])->default('other');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 50)->nullable();
            $table->enum('source_platform', ['web', 'mobile', 'api', 'webhook', 'system'])->default('web');
            $table->date('transaction_date');
            $table->enum('status', ['completed', 'pending', 'failed', 'refunded'])->default('completed');
            $table->longText('metadata')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id', 'service_type', 'transaction_date'], 'idx_rev_trans_bsd');
            $table->index(['patient_id', 'branch_id']);
            $table->index(['payment_method']);
            $table->index(['payment_method', 'transaction_date']);
            $table->index(['recorded_by'], 'revenue_transactions_recorded_by_foreign');
            $table->index(['service_type']);
            $table->index(['service_type', 'transaction_date']);
            $table->index(['source_platform']);
            $table->index(['source_type', 'source_id']);
            $table->index(['status']);
            $table->index(['transaction_date']);
            $table->index(['transaction_reference']);
            $table->unique(['transaction_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_transactions');
    }
};
