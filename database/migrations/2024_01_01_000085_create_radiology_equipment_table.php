<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('model', 255);
            $table->string('manufacturer', 255);
            $table->string('serial_number', 255);
            $table->unsignedBigInteger('modality_id');
            $table->unsignedBigInteger('department_id');
            $table->date('installation_date');
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->enum('status', ['operational', 'maintenance', 'out_of_order', 'retired'])->default('operational');
            $table->text('specifications')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['department_id'], 'radiology_equipment_department_id_foreign');
            $table->index(['modality_id'], 'radiology_equipment_modality_id_foreign');
            $table->unique(['serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_equipment');
    }
};
