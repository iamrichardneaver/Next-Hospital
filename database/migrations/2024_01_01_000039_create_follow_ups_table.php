<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id');
            $table->date('follow_up_date');
            $table->time('follow_up_time')->nullable();
            $table->enum('follow_up_type', ['in-person', 'teleconsultation'])->default('in-person');
            $table->text('reason');
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->unsignedBigInteger('assigned_to');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['assigned_to'], 'follow_ups_assigned_to_foreign');
            $table->index(['consultation_id'], 'follow_ups_consultation_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
