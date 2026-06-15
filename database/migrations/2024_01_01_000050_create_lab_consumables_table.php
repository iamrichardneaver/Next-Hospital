<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_consumables', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('catalog_number', 255)->nullable();
            $table->string('manufacturer', 255)->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('category', 255)->nullable();
            $table->string('subcategory', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('unit_of_measure', 255)->default('units');
            $table->decimal('current_stock', 10, 2)->default(0.00);
            $table->decimal('minimum_stock', 10, 2)->default(0.00);
            $table->decimal('maximum_stock', 10, 2)->default(0.00);
            $table->decimal('reorder_level', 10, 2)->default(0.00);
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            $table->date('expiry_date')->nullable();
            $table->string('batch_number', 255)->nullable();
            $table->longText('storage_requirements')->nullable();
            $table->boolean('disposable')->default(1);
            $table->boolean('sterile')->default(0);
            $table->boolean('single_use')->default(1);
            $table->text('usage_instructions')->nullable();
            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['created_by'], 'lab_consumables_created_by_foreign');
            $table->index(['supplier_id'], 'lab_consumables_supplier_id_foreign');
            $table->index(['updated_by'], 'lab_consumables_updated_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_consumables');
    }
};
