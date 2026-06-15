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
        Schema::create('drug_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescription_id');
            $table->unsignedBigInteger('drug_id');
            $table->integer('quantity');
            $table->integer('quantity_dispensed')->default(0);
            $table->text('dosage_instructions'); // e.g., "Take 1 tablet twice daily with food"
            $table->string('frequency'); // e.g., "twice daily", "as needed"
            $table->string('duration')->nullable(); // e.g., "7 days"
            $table->enum('status', ['pending', 'dispensed', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('dispensed_by')->nullable();
            $table->timestamp('dispensed_at')->nullable();
            $table->text('notes')->nullable(); // Dispensing notes
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('prescription_id')->references('id')->on('prescriptions')->onDelete('cascade');
            $table->foreign('drug_id')->references('id')->on('drugs')->onDelete('cascade');
            $table->foreign('dispensed_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drug_orders');
    }
};