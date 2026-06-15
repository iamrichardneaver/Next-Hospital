<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgery_equipment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('theatre_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('category', 255)->nullable();
            $table->string('serial_number', 255)->nullable();
            $table->string('model', 255)->nullable();
            $table->string('manufacturer', 255)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->timestamp('last_maintenance')->nullable();
            $table->timestamp('next_maintenance')->nullable();
            $table->enum('status', ['operational', 'maintenance', 'out_of_order', 'retired'])->default('operational');
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['theatre_id'], 'surgery_equipment_theatre_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surgery_equipment');
    }
};
