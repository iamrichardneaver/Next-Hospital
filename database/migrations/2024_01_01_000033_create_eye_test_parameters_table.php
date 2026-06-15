<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eye_test_parameters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->string('parameter_code', 255);
            $table->string('parameter_name', 255);
            $table->text('description')->nullable();
            $table->enum('data_type', ['numeric', 'text', 'boolean', 'select', 'image', 'file'])->default('numeric');
            $table->enum('input_type', ['text', 'number', 'select', 'radio', 'checkbox', 'textarea', 'file', 'image'])->default('text');
            $table->longText('input_options')->nullable();
            $table->string('unit', 255)->nullable();
            $table->integer('decimal_places')->default(0);
            $table->boolean('is_required')->default(1);
            $table->boolean('is_critical')->default(0);
            $table->longText('validation_rules')->nullable();
            $table->longText('reference_ranges')->nullable();
            $table->longText('abnormal_criteria')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['is_active']);
            $table->unique(['parameter_code']);
            $table->index(['template_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eye_test_parameters');
    }
};
