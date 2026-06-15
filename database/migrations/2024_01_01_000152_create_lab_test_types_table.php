<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_test_types', function (Blueprint $table) {
            $table->id();
            $table->string('test_code', 255);
            $table->string('test_name', 255);
            $table->string('category', 255);
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('subcategory', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('specimen_type', 255);
            $table->string('collection_method', 255)->nullable();
            $table->longText('preparation_instructions')->nullable();
            $table->longText('collection_instructions')->nullable();
            $table->longText('storage_requirements')->nullable();
            $table->longText('transport_requirements')->nullable();
            $table->longText('parameters')->nullable();
            $table->longText('normal_ranges')->nullable();
            $table->longText('critical_values')->nullable();
            $table->longText('units')->nullable();
            $table->integer('routine_tat_hours')->default(24);
            $table->integer('urgent_tat_hours')->default(4);
            $table->integer('stat_tat_hours')->default(1);
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('nhis_cost', 10, 2)->nullable();
            $table->boolean('nhis_covered')->default(0);
            $table->boolean('requires_qc')->default(1);
            $table->longText('qc_requirements')->nullable();
            $table->boolean('requires_verification')->default(1);
            $table->longText('verification_requirements')->nullable();
            $table->string('equipment_required', 255)->nullable();
            $table->longText('reagents_required')->nullable();
            $table->string('methodology', 255)->nullable();
            $table->string('ghs_code', 255)->nullable();
            $table->boolean('ghs_mandatory')->default(0);
            $table->longText('ghs_reporting_requirements')->nullable();
            $table->boolean('is_active')->default(1);
            $table->boolean('requires_doctor_approval')->default(0);
            $table->boolean('requires_consultant_review')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['category_id'], 'lab_test_types_category_id_foreign');
            $table->index(['category']);
            $table->index(['is_active']);
            $table->index(['nhis_covered']);
            $table->index(['template_id']);
            $table->index(['test_code']);
            $table->unique(['test_code']);
$table->foreign('template_id', 'lab_test_types_template_id_foreign')->references('id')->on('lab_test_templates')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_test_types');
    }
};
