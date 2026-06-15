<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wards', function (Blueprint $table) {
            $table->id();
            $table->string('ward_number', 255)->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->string('name', 255);
            $table->string('code', 255);
            $table->enum('type', ['male', 'female', 'general', 'pediatric', 'maternity', 'icu', 'isolation']);
            $table->integer('total_beds');
            $table->integer('available_beds')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id'], 'wards_branch_id_foreign');
            $table->unique(['code']);
            $table->unique(['ward_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wards');
    }
};
