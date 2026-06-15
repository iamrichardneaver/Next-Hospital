<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_count_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_count_id');
            $table->unsignedBigInteger('item_id');
            $table->string('item_type', 100);
            $table->string('item_name', 255)->nullable();
            $table->decimal('system_qty', 12, 2)->default(0.00);
            $table->decimal('counted_qty', 12, 2)->nullable();
            $table->decimal('variance', 12, 2)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['stock_count_id', 'item_type', 'item_id']);
$table->foreign('stock_count_id', 'stock_count_items_stock_count_id_foreign')->references('id')->on('stock_counts')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_items');
    }
};
