<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('service_id', 255);
            $table->string('rule_name', 255);
            $table->text('description')->nullable();
            $table->enum('rule_type', ['percentage_increase', 'percentage_decrease', 'fixed_increase', 'fixed_decrease', 'set_price', 'volume_discount', 'time_based']);
            $table->decimal('adjustment_value', 10, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->integer('min_quantity')->nullable();
            $table->longText('conditions')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(1);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['created_by'], 'pricing_rules_created_by_foreign');
            $table->index(['rule_type', 'is_active']);
            $table->index(['service_id', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
