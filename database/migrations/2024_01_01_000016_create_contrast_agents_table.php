<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrast_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('generic_name', 255);
            $table->string('manufacturer', 255);
            $table->text('indications');
            $table->text('contraindications');
            $table->text('side_effects');
            $table->decimal('dose_ml', 8, 2);
            $table->string('route_of_administration', 255);
            $table->boolean('requires_consent')->default(1);
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrast_agents');
    }
};
