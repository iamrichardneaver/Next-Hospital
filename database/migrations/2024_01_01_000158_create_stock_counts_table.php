<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->enum('department', ['pharmacy', 'lab', 'radiology']);
            $table->unsignedBigInteger('counted_by');
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->timestamp('counted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id', 'department', 'status']);
$table->foreign('branch_id', 'stock_counts_branch_id_foreign')->references('id')->on('branches')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('counted_by', 'stock_counts_counted_by_foreign')->references('id')->on('users')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_counts');
    }
};
