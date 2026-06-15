<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branding_settings', function (Blueprint $table) {
            $table->id();
            $table->string('platform_name', 255)->default('NextHospital');
            $table->string('business_name', 255)->nullable();
            $table->text('business_address')->nullable();
            $table->string('business_phone', 255)->nullable();
            $table->string('business_email', 255)->nullable();
            $table->string('business_website', 255)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->string('favicon_path', 255)->nullable();
            $table->string('mobile_logo_path', 255)->nullable();
            $table->string('primary_color', 255)->default('#009ef7');
            $table->string('secondary_color', 255)->default('#f1f1f1');
            $table->string('accent_color', 255)->default('#ffc700');
            $table->text('custom_css')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branding_settings');
    }
};
