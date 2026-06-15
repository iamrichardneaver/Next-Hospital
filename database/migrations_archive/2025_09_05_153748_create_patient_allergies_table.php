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
        Schema::create('patient_allergies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->string('allergen'); // e.g., "Penicillin", "Peanuts"
            $table->string('reaction'); // e.g., "Rash", "Anaphylaxis"
            $table->enum('severity', ['mild', 'moderate', 'severe']);
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at');
            $table->unsignedBigInteger('recorded_by');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_allergies');
    }
};