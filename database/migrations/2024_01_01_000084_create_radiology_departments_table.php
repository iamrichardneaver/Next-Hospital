<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('code', 255);
            $table->text('description')->nullable();
            $table->string('location', 255)->nullable();
            $table->string('contact_phone', 255)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_departments');
    }
};
