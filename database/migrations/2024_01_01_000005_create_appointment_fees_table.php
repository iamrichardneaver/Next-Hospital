<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->enum('appointment_type', ['in-person', 'teleconsultation']);
            $table->string('fee_category', 255)->default('general');
            $table->decimal('base_fee', 10, 2);
            $table->string('currency', 3)->default('GHS');
            $table->decimal('platform_fee', 10, 2)->default(0.00);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->longText('discount_rules')->nullable();
            $table->boolean('is_active')->default(1);
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id', 'appointment_type', 'is_active']);
            $table->index(['created_by'], 'appointment_fees_created_by_foreign');
            $table->index(['doctor_id', 'appointment_type', 'is_active']);
            $table->index(['fee_category', 'is_active']);
            $table->index(['updated_by'], 'appointment_fees_updated_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_fees');
    }
};
