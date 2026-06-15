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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id');
            $table->string('referred_to_specialty'); // e.g., "Cardiology", "Neurology"
            $table->unsignedBigInteger('referred_to_doctor_id')->nullable();
            $table->text('reason');
            $table->enum('urgency', ['routine', 'urgent'])->default('routine');
            $table->enum('status', ['pending', 'accepted', 'completed'])->default('pending');
            $table->unsignedBigInteger('referred_by');
            $table->date('referral_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
            $table->foreign('referred_to_doctor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('referred_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};