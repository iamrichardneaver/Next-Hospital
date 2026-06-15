<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_quality_controls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parameter_id')->nullable();
            $table->string('qc_type', 255)->nullable();
            $table->string('qc_level', 255)->nullable();
            $table->string('qc_material', 255)->nullable();
            $table->string('lot_number', 255)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('target_value', 10, 4)->nullable();
            $table->decimal('acceptable_range_low', 10, 4)->nullable();
            $table->decimal('acceptable_range_high', 10, 4)->nullable();
            $table->decimal('measured_value', 10, 4)->nullable();
            $table->boolean('is_acceptable')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('performed_at')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
$table->foreign('parameter_id', 'lab_quality_controls_parameter_id_foreign')->references('id')->on('lab_test_parameters')->onDelete('set null')->onUpdate('restrict');
$table->foreign('performed_by', 'lab_quality_controls_performed_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_quality_controls');
    }
};
