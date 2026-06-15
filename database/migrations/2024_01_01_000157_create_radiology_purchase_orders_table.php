<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 255);
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('branch_id');
            $table->enum('status', ['draft', 'ordered', 'partially_received', 'received', 'cancelled'])->default('draft');
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('ordered_by')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id', 'status']);
            $table->unique(['po_number']);
            $table->index(['supplier_id']);
$table->foreign('branch_id', 'radiology_purchase_orders_branch_id_foreign')->references('id')->on('branches')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('created_by', 'radiology_purchase_orders_created_by_foreign')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('ordered_by', 'radiology_purchase_orders_ordered_by_foreign')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('supplier_id', 'radiology_purchase_orders_supplier_id_foreign')->references('id')->on('suppliers')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('updated_by', 'radiology_purchase_orders_updated_by_foreign')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_purchase_orders');
    }
};
