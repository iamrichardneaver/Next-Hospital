<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('invoice_number', 255);
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->longText('items')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->decimal('balance_amount', 10, 2)->default(0.00);
            $table->enum('status', ['draft', 'pending', 'partial', 'overdue', 'paid', 'cancelled', 'refunded'])->default('draft');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'overdue'])->default('unpaid');
            $table->string('payment_method', 50)->nullable();
            $table->enum('source_platform', ['web', 'mobile', 'api', 'system'])->default('web');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['status', 'invoice_date'], 'idx_invoices_status_date');
            $table->index(['branch_id'], 'invoices_branch_id_foreign');
            $table->index(['branch_id', 'invoice_date']);
            $table->index(['created_by'], 'invoices_created_by_foreign');
            $table->index(['created_by', 'invoice_date']);
            $table->unique(['invoice_number']);
            $table->index(['patient_id'], 'invoices_patient_id_foreign');
            $table->index(['patient_id', 'payment_status']);
            $table->index(['payment_status']);
            $table->index(['source_platform']);
            $table->index(['updated_by'], 'invoices_updated_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
