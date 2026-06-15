<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('id_prefix_settings', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 255);
            $table->string('company_prefix', 255)->default('HWC');
            $table->string('module_prefix', 255)->default('PAT');
            $table->string('pattern', 255)->default('{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}');
            $table->integer('sequence_length')->default(5);
            $table->integer('current_sequence')->default(0);
            $table->boolean('include_year')->default(1);
            $table->boolean('include_month')->default(1);
            $table->boolean('include_day')->default(1);
            $table->string('separator', 255)->default('/');
            $table->boolean('is_locked')->default(0);
            $table->boolean('is_active')->default(1);
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['entity_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('id_prefix_settings');
    }
};
