<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('test_type', 255);
            $table->string('category', 255);
            $table->string('subcategory', 255)->nullable();
            $table->string('specimen_type', 255);
            $table->unsignedBigInteger('template_id');
            $table->boolean('is_default')->default(0);
            $table->integer('priority')->default(1);
            $table->boolean('auto_select')->default(1);
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['auto_select']);
            $table->index(['created_by'], 'template_assignments_created_by_foreign');
            $table->index(['is_default', 'priority']);
            $table->index(['template_id'], 'template_assignments_template_id_foreign');
            $table->index(['test_type', 'category', 'specimen_type']);
            $table->unique(['test_type', 'category', 'specimen_type', 'template_id'], 'unique_template_assignment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_assignments');
    }
};
