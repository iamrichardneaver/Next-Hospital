<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eye_test_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->string('template_code', 255);
            $table->string('template_name', 255);
            $table->text('description')->nullable();
            $table->enum('test_type', ['vision', 'refraction', 'visual_field', 'fundus', 'pressure', 'biometry', 'combined'])->default('vision');
            $table->longText('test_parameters')->nullable();
            $table->longText('reference_ranges')->nullable();
            $table->longText('abnormal_criteria')->nullable();
            $table->longText('equipment_config')->nullable();
            $table->longText('test_sequence')->nullable();
            $table->integer('estimated_duration_minutes')->default(15);
            $table->boolean('requires_dilation')->default(0);
            $table->longText('dilation_requirements')->nullable();
            $table->boolean('requires_dark_room')->default(0);
            $table->boolean('requires_bright_light')->default(0);
            $table->longText('environmental_requirements')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['service_id'], 'eye_test_templates_service_id_foreign');
            $table->unique(['template_code']);
            $table->index(['test_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eye_test_templates');
    }
};
