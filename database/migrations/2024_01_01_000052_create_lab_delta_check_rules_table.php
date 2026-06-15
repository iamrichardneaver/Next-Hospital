<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_delta_check_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parameter_id');
            $table->string('rule_name', 255);
            $table->decimal('delta_percentage', 5, 2)->nullable();
            $table->decimal('delta_absolute', 10, 4)->nullable();
            $table->integer('time_window_hours')->nullable()->default(24);
            $table->boolean('is_active')->nullable()->default(1);
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['parameter_id', 'is_active'], 'idx_delta_rules');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_delta_check_rules');
    }
};
