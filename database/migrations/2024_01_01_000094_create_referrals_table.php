<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id');
            $table->string('referred_to_specialty', 255);
            $table->unsignedBigInteger('referred_to_doctor_id')->nullable();
            $table->text('reason');
            $table->enum('urgency', ['routine', 'urgent'])->default('routine');
            $table->enum('status', ['pending', 'accepted', 'completed'])->default('pending');
            $table->unsignedBigInteger('referred_by');
            $table->date('referral_date');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['consultation_id'], 'referrals_consultation_id_foreign');
            $table->index(['referred_by'], 'referrals_referred_by_foreign');
            $table->index(['referred_to_doctor_id'], 'referrals_referred_to_doctor_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
