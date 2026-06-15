<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('code', 255);
            $table->text('description')->nullable();
            $table->string('parent_category', 255)->nullable();
            $table->boolean('is_active')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['code']);
            $table->unique(['code']);
            $table->index(['parent_category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_service_categories');
    }
};
