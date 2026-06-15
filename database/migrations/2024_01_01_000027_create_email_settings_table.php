<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();
            $table->string('mail_driver', 255)->default('smtp');
            $table->string('mail_host', 255)->nullable();
            $table->integer('mail_port')->default(587);
            $table->string('mail_username', 255)->nullable();
            $table->string('mail_password', 255)->nullable();
            $table->string('mail_encryption', 255)->default('tls');
            $table->string('mail_from_address', 255)->nullable();
            $table->string('mail_from_name', 255)->nullable();
            $table->boolean('mail_verify_peer')->default(1);
            $table->boolean('is_active')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_settings');
    }
};
