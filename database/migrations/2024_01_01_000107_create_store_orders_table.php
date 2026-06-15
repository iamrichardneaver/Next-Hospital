<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_orders', function (Blueprint $table) {
            $table->id();
            $table->string('store_order_number', 255)->nullable();
            $table->string('order_number', 255);
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('branch_id');
            $table->date('order_date');
            $table->enum('delivery_method', ['pickup', 'delivery']);
            $table->text('delivery_address')->nullable();
            $table->string('delivery_phone', 255)->nullable();
            $table->text('delivery_notes')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('delivery_fee', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method', 50)->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_reference', 255)->nullable();
            $table->string('transaction_id', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->longText('payment_metadata')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'processing', 'ready', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->text('status_notes')->nullable();
            $table->timestamp('status_updated_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->enum('order_source', ['web', 'mobile_app', 'api', 'system'])->default('web');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id'], 'store_orders_branch_id_foreign');
            $table->index(['created_by'], 'store_orders_created_by_foreign');
            $table->index(['delivery_method']);
            $table->unique(['order_number']);
            $table->index(['order_source']);
            $table->index(['patient_id', 'order_date']);
            $table->index(['payment_reference']);
            $table->index(['payment_status']);
            $table->index(['status', 'order_date']);
            $table->unique(['store_order_number']);
            $table->index(['transaction_id']);
            $table->index(['updated_by'], 'store_orders_updated_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_orders');
    }
};
