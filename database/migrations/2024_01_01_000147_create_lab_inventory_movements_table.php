<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->enum('item_type', ['reagent', 'consumable']);
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 10, 2);
            $table->enum('movement_type', ['purchase_receipt', 'test_consumption', 'consumption_reversal', 'adjustment', 'waste']);
            $table->string('reference_type', 255)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('performed_by');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['movement_type']);
            $table->index(['branch_id', 'item_type', 'item_id'], 'lab_inv_mov_branch_item');
            $table->index(['reference_type', 'reference_id'], 'lab_inv_mov_reference');
            $table->index(['performed_by'], 'performed_by');
$table->foreign('branch_id', 'lab_inventory_movements_ibfk_1')->references('id')->on('branches')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('performed_by', 'lab_inventory_movements_ibfk_2')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_inventory_movements');
    }
};
