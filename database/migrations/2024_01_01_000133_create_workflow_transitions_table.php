<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('from_step_id');
            $table->unsignedBigInteger('to_step_id');
            $table->enum('condition_type', ['always', 'conditional', 'permission_based'])->default('always');
            $table->longText('condition_logic')->nullable();
            $table->string('required_permission', 255)->nullable();
            $table->integer('priority')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['from_step_id'], 'workflow_transitions_from_step_id_foreign');
            $table->index(['to_step_id'], 'workflow_transitions_to_step_id_foreign');
            $table->index(['workflow_id', 'from_step_id']);
            $table->index(['workflow_id', 'to_step_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_transitions');
    }
};
