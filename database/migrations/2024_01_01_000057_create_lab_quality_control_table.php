<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_quality_control', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parameter_id');
            $table->string('qc_type', 255);
            $table->string('qc_level', 255);
            $table->string('qc_material', 255)->nullable();
            $table->string('lot_number', 255)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('target_value', 10, 4)->nullable();
            $table->decimal('acceptable_range_low', 10, 4)->nullable();
            $table->decimal('acceptable_range_high', 10, 4)->nullable();
            $table->decimal('measured_value', 10, 4)->nullable();
            $table->boolean('is_acceptable')->nullable()->default(1);
            $table->text('notes')->nullable();
            $table->timestamp('performed_at')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['parameter_id', 'qc_type', 'performed_at'], 'idx_qc_parameter_type');
            $table->index(['performed_by'], 'performed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_quality_control');
    }
};
