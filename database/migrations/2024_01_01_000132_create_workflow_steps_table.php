<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->string('step_key', 255);
            $table->string('step_name', 255);
            $table->text('step_description')->nullable();
            $table->string('route_name', 255)->nullable();
            $table->string('required_permission', 255)->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_required')->default(1);
            $table->boolean('can_skip')->default(0);
            $table->boolean('auto_redirect')->default(0);
            $table->longText('route_parameters')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['step_key']);
            $table->index(['workflow_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
