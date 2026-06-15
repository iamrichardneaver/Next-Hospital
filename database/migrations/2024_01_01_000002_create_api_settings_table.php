<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_settings', function (Blueprint $table) {
            $table->id();
            $table->string('frontend_api_url', 255)->default('http://localhost:8000/api');
            $table->string('mobile_api_url', 255)->default('http://localhost:8000/api');
            $table->string('websocket_url', 255)->default('ws://localhost:6001');
            $table->string('api_version', 255)->default('v1');
            $table->integer('api_timeout')->default(30);
            $table->integer('max_retry_attempts')->default(3);
            $table->boolean('enable_api_caching')->default(1);
            $table->integer('api_cache_ttl')->default(300);
            $table->boolean('enable_rate_limiting')->default(1);
            $table->integer('rate_limit_per_minute')->default(60);
            $table->text('allowed_origins')->nullable();
            $table->boolean('enable_api_logging')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_settings');
    }
};
