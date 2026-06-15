<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debtors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('branch_id');
            $table->decimal('total_outstanding', 10, 2)->default(0.00);
            $table->decimal('total_paid', 10, 2)->default(0.00);
            $table->decimal('total_invoiced', 10, 2)->default(0.00);
            $table->integer('outstanding_invoices_count')->default(0);
            $table->integer('overdue_invoices_count')->default(0);
            $table->date('last_payment_date')->nullable();
            $table->date('last_invoice_date')->nullable();
            $table->date('first_outstanding_date')->nullable();
            $table->enum('debt_status', ['current', 'overdue', 'critical', 'resolved'])->default('current');
            $table->integer('days_overdue')->default(0);
            $table->decimal('largest_outstanding_amount', 10, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id'], 'debtors_branch_id_foreign');
            $table->index(['created_by'], 'debtors_created_by_foreign');
            $table->index(['days_overdue']);
            $table->index(['debt_status', 'is_active']);
            $table->index(['patient_id', 'branch_id']);
            $table->index(['updated_by'], 'debtors_updated_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debtors');
    }
};
