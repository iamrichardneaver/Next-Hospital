<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number', 255);
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('lab_request_id')->nullable();
            $table->string('report_type', 255)->default('routine');
            $table->string('status', 255)->default('draft');
            $table->text('clinical_history')->nullable();
            $table->text('specimen_info')->nullable();
            $table->text('methodology')->nullable();
            $table->text('results')->nullable();
            $table->text('interpretation')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('comments')->nullable();
            $table->string('technician_name', 255)->nullable();
            $table->string('reviewed_by', 255)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by', 255)->nullable();
            $table->longText('attachments')->nullable();
            $table->boolean('is_critical')->default(0);
            $table->boolean('is_abnormal')->default(0);
            $table->string('priority', 255)->default('normal');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['created_at']);
            $table->index(['created_by'], 'lab_reports_created_by_foreign');
            $table->index(['lab_request_id'], 'lab_reports_lab_request_id_foreign');
            $table->index(['patient_id', 'created_at']);
            $table->unique(['report_number']);
            $table->index(['report_type', 'status']);
            $table->index(['updated_by'], 'lab_reports_updated_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_reports');
    }
};
