<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imaging_modalities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('code', 255);
            $table->text('description')->nullable();
            $table->string('category', 255);
            $table->boolean('requires_contrast')->default(0);
            $table->boolean('requires_sedation')->default(0);
            $table->integer('preparation_time_minutes')->default(0);
            $table->integer('procedure_time_minutes')->default(30);
            $table->decimal('base_cost', 10, 2)->default(0.00);
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_modalities');
    }
};
