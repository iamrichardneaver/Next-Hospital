<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->enum('item_type', ['equipment', 'reagent', 'consumable']);
            $table->enum('transaction_type', ['purchase', 'usage', 'waste', 'return', 'adjustment_in', 'adjustment_out', 'transfer']);
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->string('reference_number', 255)->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('location', 255)->nullable();
            $table->text('notes')->nullable();
            $table->date('transaction_date');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['created_by'], 'lab_inventory_transactions_created_by_foreign');
            $table->index(['item_id', 'item_type']);
            $table->index(['supplier_id'], 'lab_inventory_transactions_supplier_id_foreign');
            $table->index(['transaction_date']);
            $table->index(['updated_by'], 'lab_inventory_transactions_updated_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_inventory_transactions');
    }
};
