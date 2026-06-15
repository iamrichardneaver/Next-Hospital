<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('drug_stocks', function (Blueprint $table) {
            // Add missing columns that are in the model but not in database
            $table->integer('reorder_level')->default(10)->after('maximum_stock');
            $table->string('supplier')->nullable()->after('batch_number');
            $table->boolean('is_active')->default(true)->after('selling_price');
            $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            
            // Add foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drug_stocks', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            
            // Drop columns
            $table->dropColumn([
                'reorder_level',
                'supplier',
                'is_active',
                'created_by',
                'updated_by'
            ]);
        });
    }
};

