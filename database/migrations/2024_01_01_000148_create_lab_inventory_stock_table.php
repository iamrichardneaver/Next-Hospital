<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_inventory_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->enum('item_type', ['reagent', 'consumable']);
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 10, 2)->default(0.00);
            $table->string('batch_number', 255)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['created_by'], 'created_by');
            $table->index(['branch_id', 'item_type', 'item_id'], 'lab_stock_branch_item');
            $table->index(['updated_by'], 'updated_by');
$table->foreign('branch_id', 'lab_inventory_stock_ibfk_1')->references('id')->on('branches')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('created_by', 'lab_inventory_stock_ibfk_2')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('updated_by', 'lab_inventory_stock_ibfk_3')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_inventory_stock');
    }
};
