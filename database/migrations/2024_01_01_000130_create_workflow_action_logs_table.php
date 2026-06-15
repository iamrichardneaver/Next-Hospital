<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_action_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_instance_id');
            $table->unsignedBigInteger('step_id')->nullable();
            $table->enum('action_type', ['completed', 'skipped', 'redirected', 'started', 'paused', 'resumed', 'cancelled'])->default('completed');
            $table->unsignedBigInteger('user_id');
            $table->longText('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['step_id'], 'workflow_action_logs_step_id_foreign');
            $table->index(['user_id']);
            $table->index(['workflow_instance_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_action_logs');
    }
};
