<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teleconsultation_chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teleconsultation_id');
            $table->unsignedBigInteger('sender_id');
            $table->enum('sender_type', ['doctor', 'patient', 'system'])->default('doctor');
            $table->text('message');
            $table->enum('message_type', ['text', 'image', 'file', 'prescription', 'diagnosis', 'system_alert'])->default('text');
            $table->string('file_url', 255)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('file_type', 255)->nullable();
            $table->integer('file_size')->nullable();
            $table->boolean('is_read')->default(0);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_edited')->default(0);
            $table->timestamp('edited_at')->nullable();
            $table->text('edit_reason')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['is_read', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            $table->index(['teleconsultation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teleconsultation_chats');
    }
};
