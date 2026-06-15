<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debtor_payment_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('debtor_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->decimal('payment_amount', 10, 2)->default(0.00);
            $table->decimal('remaining_balance', 10, 2)->default(0.00);
            $table->date('payment_date');
            $table->string('payment_method', 50)->nullable();
            $table->string('reference_number', 255)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('processed_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['debtor_id', 'payment_date']);
            $table->index(['payment_date']);
            $table->index(['payment_method']);
$table->foreign('debtor_id', 'debtor_payment_histories_debtor_id_foreign')->references('id')->on('debtors')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('invoice_id', 'debtor_payment_histories_invoice_id_foreign')->references('id')->on('invoices')->onDelete('set null')->onUpdate('restrict');
$table->foreign('payment_id', 'debtor_payment_histories_payment_id_foreign')->references('id')->on('payments')->onDelete('set null')->onUpdate('restrict');
$table->foreign('processed_by', 'debtor_payment_histories_processed_by_foreign')->references('id')->on('users')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debtor_payment_histories');
    }
};
