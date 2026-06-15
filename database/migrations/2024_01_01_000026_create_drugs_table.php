<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drugs', function (Blueprint $table) {
            $table->id();
            $table->string('drug_number', 255)->nullable();
            $table->string('name', 255);
            $table->string('generic_name', 255)->nullable();
            $table->string('drug_code', 255)->nullable();
            $table->string('category', 255);
            $table->string('dosage_form', 255);
            $table->string('strength', 255)->nullable();
            $table->string('unit', 255)->nullable();
            $table->text('description')->nullable();
            $table->text('indications')->nullable();
            $table->text('contraindications')->nullable();
            $table->text('side_effects')->nullable();
            $table->text('dosage_instructions')->nullable();
            $table->text('storage_conditions')->nullable();
            $table->string('manufacturer', 255)->nullable();
            $table->decimal('unit_price', 10, 2)->default(0.00);
            $table->decimal('cost_price', 10, 2)->nullable()->default(0.00);
            $table->decimal('selling_price', 10, 2)->default(0.00);
            $table->decimal('nhis_price', 10, 2)->nullable()->default(0.00);
            $table->string('barcode', 255)->nullable();
            $table->boolean('requires_prescription')->default(1);
            $table->boolean('prescription_required')->default(1);
            $table->boolean('controlled_substance')->default(0);
            $table->boolean('nhis_covered')->default(0);
            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['created_by'], 'drugs_created_by_foreign');
            $table->unique(['drug_code']);
            $table->unique(['drug_number']);
            $table->index(['updated_by'], 'drugs_updated_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drugs');
    }
};
