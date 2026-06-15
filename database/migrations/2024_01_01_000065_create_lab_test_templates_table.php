<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_test_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('template_code', 255);
            $table->string('template_name', 255);
            $table->string('category', 255);
            $table->string('subcategory', 255)->nullable();
            $table->text('description')->nullable();
            $table->longText('template_content')->nullable();
            $table->longText('quantitative_parameters')->nullable();
            $table->longText('qualitative_parameters')->nullable();
            $table->enum('template_type', ['qualitative', 'quantitative', 'narrative', 'combined'])->default('quantitative');
            $table->string('specimen_type', 255);
            $table->longText('collection_instructions')->nullable();
            $table->longText('preparation_instructions')->nullable();
            $table->longText('storage_requirements')->nullable();
            $table->longText('transport_requirements')->nullable();
            $table->longText('parameters_config')->nullable();
            $table->longText('reference_ranges')->nullable();
            $table->longText('critical_values')->nullable();
            $table->longText('units_config')->nullable();
            $table->longText('flagging_rules')->nullable();
            $table->string('methodology', 255)->nullable();
            $table->string('equipment_required', 255)->nullable();
            $table->longText('reagents_required')->nullable();
            $table->longText('quality_control_requirements')->nullable();
            $table->longText('calibration_requirements')->nullable();
            $table->integer('routine_tat_hours')->default(24);
            $table->integer('urgent_tat_hours')->default(4);
            $table->integer('stat_tat_hours')->default(1);
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('nhis_cost', 10, 2)->nullable();
            $table->boolean('nhis_covered')->default(0);
            $table->string('ghs_code', 255)->nullable();
            $table->boolean('ghs_mandatory')->default(0);
            $table->longText('ghs_reporting_requirements')->nullable();
            $table->string('international_standard', 255)->nullable();
            $table->longText('compliance_requirements')->nullable();
            $table->boolean('requires_doctor_approval')->default(0);
            $table->boolean('requires_consultant_review')->default(0);
            $table->boolean('requires_pathologist_review')->default(0);
            $table->boolean('is_active')->default(1);
            $table->boolean('is_template_bank')->default(0);
            $table->string('template_source', 255)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['category_id'], 'lab_test_templates_category_id_foreign');
            $table->index(['category']);
            $table->index(['is_active']);
            $table->index(['is_template_bank']);
            $table->index(['template_code']);
            $table->unique(['template_code']);
            $table->index(['template_type'], 'lab_test_templates_test_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_test_templates');
    }
};
