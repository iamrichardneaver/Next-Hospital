<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 255)->default('custom');
            $table->string('api_url', 255)->nullable();
            $table->string('api_key', 255)->nullable();
            $table->string('api_secret', 255)->nullable();
            $table->string('sender_id', 255)->nullable();
            $table->longText('custom_headers')->nullable();
            $table->longText('request_body_template')->nullable();
            $table->string('response_success_field', 255)->default('success');
            $table->boolean('is_active')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_settings');
    }
};
