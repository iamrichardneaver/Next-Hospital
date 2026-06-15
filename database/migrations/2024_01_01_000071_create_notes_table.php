<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id');
            $table->enum('note_type', ['progress', 'procedure', 'discharge', 'consult']);
            $table->text('content');
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['consultation_id'], 'notes_consultation_id_foreign');
            $table->index(['created_by'], 'notes_created_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
