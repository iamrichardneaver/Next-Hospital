<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('device_id', 255);
            $table->string('device_name', 255)->nullable();
            $table->enum('platform', ['mobile', 'web', 'desktop'])->default('mobile');
            $table->text('fcm_token')->nullable();
            $table->string('app_version', 255)->nullable();
            $table->string('os_version', 255)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_active')->default(1);
            $table->unique(['device_id']);
            $table->index(['user_id'], 'devices_user_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
