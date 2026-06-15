<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_test_parameters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->string('parameter_code', 255);
            $table->string('parameter_name', 255);
            $table->text('description')->nullable();
            $table->enum('data_type', ['numeric', 'text', 'boolean', 'date', 'time', 'datetime'])->default('numeric');
            $table->enum('input_type', ['text', 'number', 'select', 'radio', 'checkbox', 'textarea', 'rich_text'])->default('text');
            $table->longText('input_options')->nullable();
            $table->string('unit', 255)->nullable();
            $table->integer('decimal_places')->default(0);
            $table->boolean('is_required')->default(1);
            $table->boolean('is_critical')->default(0);
            $table->boolean('allows_delta_check')->default(0);
            $table->longText('validation_rules')->nullable();
            $table->longText('reference_ranges')->nullable();
            $table->longText('critical_values')->nullable();
            $table->longText('flagging_rules')->nullable();
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['is_active']);
            $table->index(['parameter_code']);
            $table->index(['template_id']);
            $table->unique(['template_id', 'parameter_code'], 'lab_test_parameters_template_parameter_unique');
$table->foreign('created_by', 'lab_test_parameters_created_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
$table->foreign('updated_by', 'lab_test_parameters_updated_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_test_parameters');
    }
};
