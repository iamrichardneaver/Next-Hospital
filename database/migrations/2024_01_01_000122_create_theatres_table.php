<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theatres', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->integer('capacity')->default(1);
            $table->longText('equipment')->nullable();
            $table->longText('working_hours')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id'], 'theatres_branch_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theatres');
    }
};
