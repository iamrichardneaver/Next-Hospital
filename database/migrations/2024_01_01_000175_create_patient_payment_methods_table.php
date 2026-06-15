<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->enum('payment_type', ['card', 'mobile_money', 'bank_account']);
            $table->string('provider', 100)->nullable();
            $table->string('account_name', 255)->nullable();
            $table->string('account_number', 100)->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_brand', 50)->nullable();
            $table->boolean('is_default')->nullable()->default(0);
            $table->boolean('is_active')->nullable()->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->index(['patient_id'], 'patient_id');
$table->foreign('patient_id', 'patient_payment_methods_ibfk_1')->references('id')->on('patients')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_payment_methods');
    }
};
