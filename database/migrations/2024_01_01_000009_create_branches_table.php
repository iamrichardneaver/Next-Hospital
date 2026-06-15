<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_number', 255)->nullable();
            $table->string('name', 255);
            $table->string('code', 255);
            $table->text('address');
            $table->string('phone', 255);
            $table->string('email', 255);
            $table->string('timezone', 255)->default('Africa/Accra');
            $table->boolean('is_active')->default(1);
            $table->longText('settings')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['branch_number']);
            $table->unique(['code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
