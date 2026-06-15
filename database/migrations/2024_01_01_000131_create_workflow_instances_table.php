<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->string('entity_type', 255);
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('current_step_id')->nullable();
            $table->enum('status', ['active', 'completed', 'paused', 'cancelled'])->default('active');
            $table->unsignedBigInteger('started_by');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['current_step_id'], 'workflow_instances_current_step_id_foreign');
            $table->index(['entity_type', 'entity_id']);
            $table->index(['started_by'], 'workflow_instances_started_by_foreign');
            $table->index(['status']);
            $table->index(['workflow_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instances');
    }
};
