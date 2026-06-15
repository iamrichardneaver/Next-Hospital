<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_equipment_maintenance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id');
            $table->enum('maintenance_type', ['scheduled', 'preventive', 'emergency', 'repair'])->default('scheduled');
            $table->date('maintenance_date');
            $table->date('next_maintenance_date')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->string('service_provider', 255)->nullable();
            $table->text('description')->nullable();
            $table->longText('issues_found')->nullable();
            $table->longText('actions_taken')->nullable();
            $table->longText('parts_replaced')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['created_by'], 'lab_equipment_maintenance_created_by_foreign');
            $table->index(['equipment_id'], 'lab_equipment_maintenance_equipment_id_foreign');
            $table->index(['performed_by'], 'lab_equipment_maintenance_performed_by_foreign');
            $table->index(['updated_by'], 'lab_equipment_maintenance_updated_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_equipment_maintenance');
    }
};
