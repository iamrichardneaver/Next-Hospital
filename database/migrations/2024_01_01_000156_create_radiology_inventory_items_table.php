<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('sku', 255)->nullable();
            $table->enum('category', ['contrast', 'film', 'consumable', 'supply']);
            $table->string('unit', 50)->default('unit');
            $table->decimal('current_stock', 10, 2)->default(0.00);
            $table->decimal('reorder_level', 10, 2)->default(0.00);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->string('batch_number', 255)->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['category', 'is_active']);
            $table->index(['sku']);
$table->foreign('created_by', 'radiology_inventory_items_created_by_foreign')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('supplier_id', 'radiology_inventory_items_supplier_id_foreign')->references('id')->on('suppliers')->onDelete('set null')->onUpdate('restrict');
$table->foreign('updated_by', 'radiology_inventory_items_updated_by_foreign')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_inventory_items');
    }
};
