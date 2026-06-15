<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_equipment_maintenances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lab_equipment_id');
            $table->enum('maintenance_type', ['scheduled', 'preventive', 'corrective', 'emergency'])->default('scheduled');
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
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
$table->foreign('created_by', 'lab_equipment_maintenances_created_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
$table->foreign('lab_equipment_id', 'lab_equipment_maintenances_lab_equipment_id_foreign')->references('id')->on('lab_equipment')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('performed_by', 'lab_equipment_maintenances_performed_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
$table->foreign('updated_by', 'lab_equipment_maintenances_updated_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_equipment_maintenances');
    }
};
