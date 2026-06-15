<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name', 255)->default('NextHospital');
            $table->string('app_short_name', 255)->default('NextHosp');
            $table->string('app_icon_path', 255)->nullable();
            $table->string('splash_screen_path', 255)->nullable();
            $table->string('app_logo_path', 255)->nullable();
            $table->string('package_name', 255)->default('com.nexthospital.app');
            $table->string('version', 255)->default('1.0.0');
            $table->text('app_description')->nullable();
            $table->longText('app_permissions')->nullable();
            $table->boolean('enable_offline_mode')->default(1);
            $table->boolean('enable_push_notifications')->default(1);
            $table->boolean('enable_biometric_auth')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_app_settings');
    }
};
