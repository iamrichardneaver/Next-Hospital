<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('radiology_inventory_item_id');
            $table->decimal('quantity', 10, 2);
            $table->enum('movement_type', ['purchase_receipt', 'study_consumption', 'consumption_reversal', 'adjustment', 'waste']);
            $table->string('reference_type', 255)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('performed_by');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['movement_type']);
            $table->index(['branch_id', 'radiology_inventory_item_id'], 'radio_inv_mov_branch_item');
            $table->index(['reference_type', 'reference_id'], 'radio_inv_mov_reference');
$table->foreign('branch_id', 'radiology_inventory_movements_branch_id_foreign')->references('id')->on('branches')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('performed_by', 'radiology_inventory_movements_performed_by_foreign')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('radiology_inventory_item_id', 'radio_mov_item_fk')->references('id')->on('radiology_inventory_items')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_inventory_movements');
    }
};
