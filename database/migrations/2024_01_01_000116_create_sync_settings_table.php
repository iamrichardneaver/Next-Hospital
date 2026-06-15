<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enable_offline_mode')->default(1);
            $table->integer('sync_interval_minutes')->default(5);
            $table->integer('max_offline_days')->default(7);
            $table->integer('max_sync_retries')->default(3);
            $table->boolean('auto_sync_on_online')->default(1);
            $table->boolean('sync_on_app_start')->default(1);
            $table->longText('sync_tables')->nullable();
            $table->longText('excluded_tables')->nullable();
            $table->boolean('enable_conflict_resolution')->default(1);
            $table->string('conflict_resolution_strategy', 255)->default('timestamp');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_settings');
    }
};
