<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drug_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescription_id');
            $table->unsignedBigInteger('drug_id');
            $table->integer('quantity');
            $table->integer('quantity_dispensed')->nullable()->default(0);
            $table->text('dosage_instructions')->nullable();
            $table->text('instructions');
            $table->string('frequency', 255);
            $table->string('duration', 255)->nullable();
            $table->enum('status', ['pending', 'processing', 'ready', 'dispensed', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('dispensed_by')->nullable();
            $table->timestamp('dispensed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drug_orders');
    }
};
