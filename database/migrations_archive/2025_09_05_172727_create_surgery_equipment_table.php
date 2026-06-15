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
        Schema::create('surgery_equipment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('theatre_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('model')->nullable();
            $table->string('manufacturer')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->timestamp('last_maintenance')->nullable();
            $table->timestamp('next_maintenance')->nullable();
            $table->enum('status', ['operational', 'maintenance', 'out_of_order', 'retired'])->default('operational');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('theatre_id')->references('id')->on('theatres')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surgery_equipment');
    }
};
