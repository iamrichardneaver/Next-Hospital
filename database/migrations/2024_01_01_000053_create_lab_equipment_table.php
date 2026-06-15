<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('model', 255)->nullable();
            $table->string('manufacturer', 255)->nullable();
            $table->string('serial_number', 255);
            $table->enum('equipment_type', ['analyzer', 'microscope', 'centrifuge', 'incubator', 'refrigerator', 'freezer', 'other'])->default('other');
            $table->string('location', 255)->nullable();
            $table->string('department', 255)->nullable();
            $table->date('installation_date')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->enum('status', ['operational', 'maintenance', 'out_of_service', 'retired'])->default('operational');
            $table->longText('specifications')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 10, 2)->nullable();
            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['created_by'], 'lab_equipment_created_by_foreign');
            $table->unique(['serial_number']);
            $table->index(['supplier_id'], 'lab_equipment_supplier_id_foreign');
            $table->index(['updated_by'], 'lab_equipment_updated_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_equipment');
    }
};
