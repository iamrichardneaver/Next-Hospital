<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('test_code', 255);
            $table->string('test_name', 255);
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('test_type_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->text('description')->nullable();
            $table->string('specimen_type', 255)->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('nhis_cost', 10, 2)->nullable();
            $table->boolean('nhis_covered')->default(0);
            $table->integer('turnaround_hours')->default(24);
            $table->boolean('is_active')->default(1);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['category_id']);
            $table->index(['is_active']);
            $table->index(['template_id'], 'lab_tests_template_id_foreign');
            $table->index(['test_code']);
            $table->unique(['test_code']);
            $table->index(['test_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_tests');
    }
};
