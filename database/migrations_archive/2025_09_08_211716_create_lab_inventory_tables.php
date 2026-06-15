<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create suppliers table
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_id')->nullable();
            $table->enum('supplier_type', ['equipment', 'reagent', 'consumable', 'general'])->default('general');
            $table->string('payment_terms')->nullable();
            $table->string('delivery_terms')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // Create lab_equipment table
        Schema::create('lab_equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('serial_number')->unique();
            $table->enum('equipment_type', ['analyzer', 'microscope', 'centrifuge', 'incubator', 'refrigerator', 'freezer', 'other'])->default('other');
            $table->string('location')->nullable();
            $table->string('department')->nullable();
            $table->date('installation_date')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->enum('status', ['operational', 'maintenance', 'out_of_service', 'retired'])->default('operational');
            $table->json('specifications')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // Create lab_reagents table
        Schema::create('lab_reagents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('catalog_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->text('description')->nullable();
            $table->string('unit_of_measure')->default('units');
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('minimum_stock', 10, 2)->default(0);
            $table->decimal('maximum_stock', 10, 2)->default(0);
            $table->decimal('reorder_level', 10, 2)->default(0);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->json('storage_requirements')->nullable();
            $table->decimal('storage_temperature', 5, 2)->nullable();
            $table->decimal('storage_humidity', 5, 2)->nullable();
            $table->boolean('light_sensitive')->default(false);
            $table->boolean('hazardous')->default(false);
            $table->text('safety_notes')->nullable();
            $table->text('usage_instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // Create lab_consumables table
        Schema::create('lab_consumables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('catalog_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->text('description')->nullable();
            $table->string('unit_of_measure')->default('units');
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('minimum_stock', 10, 2)->default(0);
            $table->decimal('maximum_stock', 10, 2)->default(0);
            $table->decimal('reorder_level', 10, 2)->default(0);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->json('storage_requirements')->nullable();
            $table->boolean('disposable')->default(true);
            $table->boolean('sterile')->default(false);
            $table->boolean('single_use')->default(true);
            $table->text('usage_instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // Create lab_equipment_maintenance table
        Schema::create('lab_equipment_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('lab_equipment');
            $table->enum('maintenance_type', ['scheduled', 'preventive', 'emergency', 'repair'])->default('scheduled');
            $table->date('maintenance_date');
            $table->date('next_maintenance_date')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users');
            $table->string('service_provider')->nullable();
            $table->text('description')->nullable();
            $table->json('issues_found')->nullable();
            $table->json('actions_taken')->nullable();
            $table->json('parts_replaced')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // Create lab_inventory_transactions table
        Schema::create('lab_inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->enum('item_type', ['equipment', 'reagent', 'consumable']);
            $table->enum('transaction_type', ['purchase', 'usage', 'waste', 'return', 'adjustment_in', 'adjustment_out', 'transfer']);
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->string('reference_number')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->date('transaction_date');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['item_id', 'item_type']);
            $table->index('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_inventory_transactions');
        Schema::dropIfExists('lab_equipment_maintenance');
        Schema::dropIfExists('lab_consumables');
        Schema::dropIfExists('lab_reagents');
        Schema::dropIfExists('lab_equipment');
        Schema::dropIfExists('suppliers');
    }
};