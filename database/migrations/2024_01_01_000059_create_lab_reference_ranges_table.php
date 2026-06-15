<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_reference_ranges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parameter_id');
            $table->string('age_group', 255);
            $table->string('gender', 255);
            $table->boolean('is_pregnant')->default(0);
            $table->string('pregnancy_trimester', 255)->nullable();
            $table->string('ethnicity', 255)->nullable();
            $table->string('population', 255)->nullable();
            $table->decimal('min_value', 10, 4)->nullable();
            $table->decimal('max_value', 10, 4)->nullable();
            $table->string('min_operator', 255)->default('>=');
            $table->string('max_operator', 255)->default('<=');
            $table->string('unit', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('source', 255)->nullable();
            $table->string('reference', 255)->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['parameter_id'], 'lab_reference_ranges_parameter_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_reference_ranges');
    }
};
