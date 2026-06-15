<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('claim_id');
            $table->string('service_type', 255);
            $table->string('service_code', 255)->nullable();
            $table->string('description', 255);
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('covered_amount', 12, 2);
            $table->decimal('co_pay_amount', 12, 2);
            $table->decimal('deductible_amount', 10, 2)->default(0.00);
            $table->longText('service_details')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['claim_id', 'service_type']);
$table->foreign('claim_id', 'claim_items_claim_id_foreign')->references('id')->on('insurance_claims')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_items');
    }
};
