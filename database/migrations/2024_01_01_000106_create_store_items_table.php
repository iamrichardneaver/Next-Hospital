<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('drug_id')->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('category', 255);
            $table->decimal('price', 10, 2);
            $table->integer('stock_quantity')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->string('image_url', 255)->nullable();
            $table->boolean('is_active')->default(1);
            $table->boolean('is_available')->default(1);
            $table->boolean('prescription_required')->default(0);
            $table->text('dosage_instructions')->nullable();
            $table->text('side_effects')->nullable();
            $table->text('contraindications')->nullable();
            $table->string('manufacturer', 255)->nullable();
            $table->string('batch_number', 255)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->decimal('selling_price', 10, 2)->default(0.00);
            $table->string('sku', 255)->nullable();
            $table->longText('metadata')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['category', 'is_active']);
            $table->index(['created_by'], 'store_items_created_by_foreign');
            $table->index(['drug_id'], 'store_items_drug_id_foreign');
            $table->index(['is_available', 'is_active']);
            $table->index(['prescription_required']);
            $table->unique(['sku']);
            $table->index(['updated_by'], 'store_items_updated_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_items');
    }
};
