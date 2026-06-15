<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->string('lab_result_number', 255)->nullable();
            $table->unsignedBigInteger('lab_request_id');
            $table->string('test_name', 255);
            $table->text('result_value');
            $table->string('unit', 255)->nullable();
            $table->text('reference_range')->nullable();
            $table->enum('result_status', ['normal', 'abnormal', 'critical'])->default('normal');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['lab_request_id'], 'lab_results_lab_request_id_foreign');
            $table->unique(['lab_result_number']);
            $table->index(['verified_by'], 'lab_results_verified_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};
