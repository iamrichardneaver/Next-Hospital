<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_critical_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parameter_id');
            $table->string('age_group', 255);
            $table->string('gender', 255);
            $table->boolean('is_pregnant')->nullable()->default(0);
            $table->decimal('critical_low', 10, 4)->nullable();
            $table->decimal('critical_high', 10, 4)->nullable();
            $table->decimal('panic_low', 10, 4)->nullable();
            $table->decimal('panic_high', 10, 4)->nullable();
            $table->string('unit', 255)->nullable();
            $table->text('alert_message')->nullable();
            $table->longText('notification_recipients')->nullable();
            $table->integer('escalation_time_minutes')->nullable()->default(15);
            $table->boolean('is_active')->nullable()->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['is_active'], 'idx_critical_values_active');
            $table->index(['parameter_id', 'age_group', 'gender', 'is_pregnant'], 'idx_critical_values_patient');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_critical_values');
    }
};
