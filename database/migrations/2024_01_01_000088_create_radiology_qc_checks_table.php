<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_qc_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id');
            $table->unsignedBigInteger('technician_id');
            $table->date('check_date');
            $table->enum('check_type', ['daily', 'weekly', 'monthly', 'quarterly', 'annual']);
            $table->longText('test_results');
            $table->boolean('passed')->default(1);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['equipment_id'], 'radiology_qc_checks_equipment_id_foreign');
            $table->index(['reviewed_by'], 'radiology_qc_checks_reviewed_by_foreign');
            $table->index(['technician_id'], 'radiology_qc_checks_technician_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_qc_checks');
    }
};
