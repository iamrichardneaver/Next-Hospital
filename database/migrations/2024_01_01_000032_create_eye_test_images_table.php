<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eye_test_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_request_id');
            $table->unsignedBigInteger('parameter_id')->nullable();
            $table->string('image_type', 255);
            $table->string('image_path', 255);
            $table->string('original_filename', 255);
            $table->string('file_extension', 255);
            $table->integer('file_size_bytes');
            $table->longText('image_metadata')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_primary')->default(0);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['is_primary']);
            $table->index(['parameter_id'], 'eye_test_images_parameter_id_foreign');
            $table->index(['test_request_id', 'image_type']);
            $table->index(['uploaded_by'], 'eye_test_images_uploaded_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eye_test_images');
    }
};
