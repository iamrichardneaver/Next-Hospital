<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('store_item_id')->nullable();
            $table->unsignedBigInteger('drug_id')->nullable();
            $table->enum('item_type', ['drug', 'store_item']);
            $table->integer('quantity')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->index(['drug_id'], 'drug_id');
            $table->index(['store_item_id'], 'store_item_id');
            $table->unique(['patient_id', 'store_item_id', 'drug_id', 'item_type'], 'unique_patient_item');
$table->foreign('patient_id', 'patient_cart_ibfk_1')->references('id')->on('patients')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('store_item_id', 'patient_cart_ibfk_2')->references('id')->on('store_items')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('drug_id', 'patient_cart_ibfk_3')->references('id')->on('drugs')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_cart');
    }
};
