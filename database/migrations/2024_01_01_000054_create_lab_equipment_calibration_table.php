<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_equipment_calibration', function (Blueprint $table) {
            $table->id();
            $table->string('equipment_name', 255);
            $table->string('equipment_model', 255)->nullable();
            $table->string('serial_number', 255)->nullable();
            $table->string('calibration_type', 255);
            $table->longText('calibration_parameters')->nullable();
            $table->longText('calibration_results')->nullable();
            $table->boolean('is_acceptable')->nullable()->default(1);
            $table->text('notes')->nullable();
            $table->timestamp('calibrated_at')->nullable();
            $table->timestamp('next_calibration_due')->nullable();
            $table->unsignedBigInteger('calibrated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['calibrated_by'], 'calibrated_by');
            $table->index(['equipment_name', 'calibrated_at'], 'idx_equipment_calibration');
            $table->index(['next_calibration_due'], 'idx_next_calibration_due');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_equipment_calibration');
    }
};
