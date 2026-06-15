<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lab_purchase_order_id');
            $table->enum('item_type', ['reagent', 'consumable']);
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity_ordered', 10, 2);
            $table->decimal('quantity_received', 10, 2)->default(0.00);
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            $table->string('batch_number', 255)->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['lab_purchase_order_id', 'item_type', 'item_id'], 'lab_po_items_lookup');
$table->foreign('lab_purchase_order_id', 'lab_purchase_order_items_ibfk_1')->references('id')->on('lab_purchase_orders')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_purchase_order_items');
    }
};
