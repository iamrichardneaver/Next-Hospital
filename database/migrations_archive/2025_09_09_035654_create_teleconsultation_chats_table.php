<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teleconsultation_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teleconsultation_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->enum('sender_type', ['doctor', 'patient', 'system'])->default('doctor');
            $table->text('message');
            $table->enum('message_type', ['text', 'image', 'file', 'prescription', 'diagnosis', 'system_alert'])->default('text');
            $table->string('file_url')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->text('edit_reason')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['teleconsultation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            $table->index(['is_read', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teleconsultation_chats');
    }
};