<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eye_service_billing_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_request_id');
            $table->unsignedBigInteger('service_id');
            $table->string('item_code', 255);
            $table->string('item_name', 255);
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('final_amount', 12, 2);
            $table->string('currency', 3)->default('GHS');
            $table->boolean('is_insurance_covered')->default(0);
            $table->decimal('insurance_coverage', 10, 2)->default(0.00);
            $table->decimal('patient_co_pay', 10, 2)->default(0.00);
            $table->enum('billing_status', ['pending', 'billed', 'paid', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['item_code']);
            $table->index(['service_id'], 'eye_service_billing_items_service_id_foreign');
            $table->index(['test_request_id', 'billing_status']);
$table->foreign('invoice_id', 'eye_service_billing_items_invoice_id_foreign')->references('id')->on('invoices')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eye_service_billing_items');
    }
};
