<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['conversation_id', 'user_id']);
$table->foreign('conversation_id', 'conversation_participants_conversation_id_foreign')->references('id')->on('conversations')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('user_id', 'conversation_participants_user_id_foreign')->references('id')->on('users')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};
