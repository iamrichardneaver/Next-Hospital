<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_series', function (Blueprint $table) {
            $table->id();
            $table->string('series_uid', 255);
            $table->unsignedBigInteger('study_id');
            $table->integer('series_number');
            $table->string('series_description', 255);
            $table->string('body_part_examined', 255)->nullable();
            $table->string('view_position', 255)->nullable();
            $table->integer('number_of_instances')->default(0);
            $table->longText('series_parameters')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['series_uid']);
            $table->index(['study_id'], 'radiology_series_study_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_series');
    }
};
