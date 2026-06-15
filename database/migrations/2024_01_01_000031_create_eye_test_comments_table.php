<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eye_test_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_request_id');
            $table->unsignedBigInteger('test_result_id')->nullable();
            $table->enum('comment_type', ['clinical', 'technical', 'interpretation', 'recommendation', 'follow_up'])->default('clinical');
            $table->longText('comment_content');
            $table->unsignedBigInteger('commented_by');
            $table->timestamp('commented_at')->useCurrent();
            $table->boolean('is_public')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['commented_by'], 'eye_test_comments_commented_by_foreign');
            $table->index(['test_request_id', 'comment_type']);
            $table->index(['test_result_id'], 'eye_test_comments_test_result_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eye_test_comments');
    }
};
