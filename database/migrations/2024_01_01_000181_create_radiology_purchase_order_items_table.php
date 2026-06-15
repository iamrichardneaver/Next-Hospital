<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('radiology_purchase_order_id');
            $table->unsignedBigInteger('radiology_inventory_item_id');
            $table->decimal('quantity_ordered', 10, 2);
            $table->decimal('quantity_received', 10, 2)->default(0.00);
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            $table->string('batch_number', 255)->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['radiology_purchase_order_id', 'radiology_inventory_item_id'], 'radio_po_items_lookup');
$table->foreign('radiology_inventory_item_id', 'radio_po_items_item_fk')->references('id')->on('radiology_inventory_items')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('radiology_purchase_order_id', 'radio_po_items_po_fk')->references('id')->on('radiology_purchase_orders')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_purchase_order_items');
    }
};
