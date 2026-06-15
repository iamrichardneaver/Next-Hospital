<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_result_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_result_id');
            $table->enum('comment_type', ['clinical', 'technical', 'quality_control', 'interpretation', 'recommendation'])->nullable()->default('clinical');
            $table->longText('comment_content');
            $table->unsignedBigInteger('commented_by');
            $table->timestamp('commented_at')->useCurrent();
            $table->boolean('is_public')->nullable()->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['commented_by'], 'commented_by');
            $table->index(['test_result_id', 'comment_type'], 'idx_result_comments');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_result_comments');
    }
};
