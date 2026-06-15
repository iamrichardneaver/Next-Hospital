<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('sender_id');
            $table->text('message');
            $table->enum('type', ['text', 'file', 'image'])->default('text');
            $table->string('file_path', 255)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('file_type', 255)->nullable();
            $table->boolean('is_edited')->default(0);
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['created_at']);
$table->foreign('conversation_id', 'messages_conversation_id_foreign')->references('id')->on('conversations')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('sender_id', 'messages_sender_id_foreign')->references('id')->on('users')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
