<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 255);
            $table->string('environment', 255)->default('sandbox');
            $table->string('public_key', 255)->nullable();
            $table->string('secret_key', 255)->nullable();
            $table->string('merchant_id', 255)->nullable();
            $table->longText('webhook_urls')->nullable();
            $table->string('callback_url', 255)->nullable();
            $table->string('webhook_secret', 255)->nullable();
            $table->longText('supported_currencies')->nullable();
            $table->longText('supported_payment_methods')->nullable();
            $table->boolean('is_active')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
