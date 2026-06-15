<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_inventory', function (Blueprint $table) {
            $table->id();
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']);
            $table->enum('blood_component', ['whole_blood', 'packed_cells', 'plasma', 'platelets', 'cryoprecipitate'])->default('whole_blood');
            $table->decimal('total_units', 8, 2)->default(0.00);
            $table->decimal('available_units', 8, 2)->default(0.00);
            $table->decimal('reserved_units', 8, 2)->default(0.00);
            $table->decimal('used_units', 8, 2)->default(0.00);
            $table->decimal('expired_units', 8, 2)->default(0.00);
            $table->decimal('minimum_stock_level', 8, 2)->default(5.00);
            $table->decimal('optimal_stock_level', 8, 2)->default(20.00);
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->unsignedBigInteger('last_updated_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['blood_component']);
            $table->unique(['blood_group', 'blood_component', 'branch_id']);
            $table->index(['blood_group']);
$table->foreign('branch_id', 'blood_inventory_branch_id_foreign')->references('id')->on('branches')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('last_updated_by', 'blood_inventory_last_updated_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_inventory');
    }
};
