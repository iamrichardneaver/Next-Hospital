<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_test_type_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lab_test_type_id');
            $table->enum('item_type', ['reagent', 'consumable']);
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity_per_test', 10, 2)->default(1.00);
            $table->boolean('is_optional')->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['created_by'], 'created_by');
            $table->unique(['lab_test_type_id', 'item_type', 'item_id'], 'lab_test_type_item_unique');
            $table->index(['updated_by'], 'updated_by');
$table->foreign('lab_test_type_id', 'lab_test_type_items_ibfk_1')->references('id')->on('lab_test_types')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('created_by', 'lab_test_type_items_ibfk_2')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
$table->foreign('updated_by', 'lab_test_type_items_ibfk_3')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_test_type_items');
    }
};
