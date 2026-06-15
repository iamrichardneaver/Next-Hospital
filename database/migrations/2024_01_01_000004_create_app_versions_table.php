<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', ['android', 'ios', 'both'])->default('both');
            $table->integer('version_code');
            $table->string('version_name', 255);
            $table->integer('build_number');
            $table->boolean('is_force_update')->default(0);
            $table->integer('min_supported_version')->nullable();
            $table->string('download_url', 500)->nullable();
            $table->string('play_store_url', 500)->nullable();
            $table->string('app_store_url', 500)->nullable();
            $table->text('release_notes')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['platform', 'is_active']);
            $table->index(['version_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
