<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_purchase_orders', function (Blueprint $table) {
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
            $table->index(['created_by'], 'created_by');
            $table->index(['branch_id', 'status']);
            $table->index(['supplier_id']);
            $table->index(['ordered_by'], 'ordered_by');
            $table->unique(['po_number'], 'po_number');
            $table->index(['updated_by'], 'updated_by');
$table->foreign('supplier_id', 'lab_purchase_orders_ibfk_1')->references('id')->on('suppliers')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('branch_id', 'lab_purchase_orders_ibfk_2')->references('id')->on('branches')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('ordered_by', 'lab_purchase_orders_ibfk_3')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('created_by', 'lab_purchase_orders_ibfk_4')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('updated_by', 'lab_purchase_orders_ibfk_5')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_purchase_orders');
    }
};
