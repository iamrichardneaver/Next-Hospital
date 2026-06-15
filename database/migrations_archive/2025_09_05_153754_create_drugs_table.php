<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('drugs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('generic_name')->nullable();
            $table->string('category');
            $table->string('dosage_form'); // tablet, capsule, syrup, injection, etc.
            $table->string('strength')->nullable(); // e.g., "500mg", "10ml"
            $table->text('description')->nullable();
            $table->string('manufacturer')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->string('barcode')->nullable();
            $table->boolean('requires_prescription')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drugs');
    }
};