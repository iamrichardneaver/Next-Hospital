<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crash_carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('item_name', 255);
            $table->text('description')->nullable();
            $table->string('category', 255)->nullable();
            $table->integer('current_quantity')->default(0);
            $table->integer('minimum_quantity')->default(0);
            $table->integer('maximum_quantity')->default(100);
            $table->string('unit', 255)->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamp('last_used')->nullable();
            $table->unsignedBigInteger('last_used_by')->nullable();
            $table->text('usage_notes')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id'], 'crash_carts_branch_id_foreign');
            $table->index(['last_used_by'], 'crash_carts_last_used_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crash_carts');
    }
};
