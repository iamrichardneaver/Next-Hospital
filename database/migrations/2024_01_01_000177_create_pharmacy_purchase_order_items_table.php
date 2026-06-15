<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pharmacy_purchase_order_id');
            $table->unsignedBigInteger('drug_id');
            $table->unsignedInteger('quantity_ordered');
            $table->unsignedInteger('quantity_received')->default(0);
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            $table->string('batch_number', 255)->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['drug_id'], 'drug_id');
            $table->index(['pharmacy_purchase_order_id', 'drug_id'], 'pharm_po_items_po_drug_idx');
$table->foreign('pharmacy_purchase_order_id', 'pharmacy_purchase_order_items_ibfk_1')->references('id')->on('pharmacy_purchase_orders')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('drug_id', 'pharmacy_purchase_order_items_ibfk_2')->references('id')->on('drugs')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_purchase_order_items');
    }
};
