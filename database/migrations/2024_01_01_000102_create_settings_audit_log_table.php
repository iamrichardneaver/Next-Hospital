<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings_audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('section', 255);
            $table->string('action', 255);
            $table->longText('old_values')->nullable();
            $table->longText('new_values')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('ip_address', 255)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['section', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings_audit_log');
    }
};
