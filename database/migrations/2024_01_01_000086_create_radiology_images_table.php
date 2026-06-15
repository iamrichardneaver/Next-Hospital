<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_images', function (Blueprint $table) {
            $table->id();
            $table->string('sop_instance_uid', 255);
            $table->unsignedBigInteger('series_id');
            $table->integer('instance_number');
            $table->string('file_path', 255);
            $table->string('file_name', 255);
            $table->bigInteger('file_size');
            $table->string('mime_type', 255)->default('application/dicom');
            $table->longText('dicom_tags')->nullable();
            $table->boolean('is_compressed')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['series_id'], 'radiology_images_series_id_foreign');
            $table->unique(['sop_instance_uid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_images');
    }
};
