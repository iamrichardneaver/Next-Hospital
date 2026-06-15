<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('description', 255)->nullable();
            $table->string('specialty', 255)->nullable();
            $table->longText('template_data');
            $table->boolean('is_active')->default(1);
            $table->boolean('is_system')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['created_by'], 'consultation_templates_created_by_foreign');
            $table->index(['specialty', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_templates');
    }
};
