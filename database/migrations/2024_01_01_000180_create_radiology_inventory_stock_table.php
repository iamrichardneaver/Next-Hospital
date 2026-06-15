<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_inventory_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('radiology_inventory_item_id');
            $table->decimal('quantity', 10, 2)->default(0.00);
            $table->string('batch_number', 255)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id', 'radiology_inventory_item_id'], 'radio_stock_branch_item');
$table->foreign('branch_id', 'radiology_inventory_stock_branch_id_foreign')->references('id')->on('branches')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('created_by', 'radiology_inventory_stock_created_by_foreign')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('updated_by', 'radiology_inventory_stock_updated_by_foreign')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('radiology_inventory_item_id', 'radio_stock_item_fk')->references('id')->on('radiology_inventory_items')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_inventory_stock');
    }
};
