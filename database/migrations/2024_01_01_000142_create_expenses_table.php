<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_reference', 255)->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('department', 30)->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('expense_date');
            $table->string('description', 255);
            $table->string('reference', 255)->nullable();
            $table->string('payment_method', 255)->nullable();
            $table->string('vendor', 255)->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['branch_id', 'expense_date']);
            $table->index(['category_id']);
            $table->index(['department']);
            $table->unique(['expense_reference']);
            $table->index(['status', 'expense_date']);
$table->foreign('category_id', 'expenses_category_id_foreign')->references('id')->on('expense_categories')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
