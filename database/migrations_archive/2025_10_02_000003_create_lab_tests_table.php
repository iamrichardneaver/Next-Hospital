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
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('test_code')->unique();
            $table->string('test_name');
            $table->unsignedBigInteger('category_id'); // Link to test_categories
            $table->unsignedBigInteger('test_type_id')->nullable(); // Link to lab_test_types (optional)
            $table->unsignedBigInteger('template_id')->nullable(); // Link to templates
            $table->text('description')->nullable();
            $table->string('specimen_type')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('nhis_cost', 10, 2)->nullable();
            $table->boolean('nhis_covered')->default(false);
            $table->integer('turnaround_hours')->default(24);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            $table->foreign('category_id')->references('id')->on('lab_test_categories')->onDelete('cascade');
            $table->foreign('test_type_id')->references('id')->on('lab_test_types')->onDelete('set null');
            $table->foreign('template_id')->references('id')->on('lab_test_templates')->onDelete('set null');
            
            $table->index('test_code');
            $table->index('category_id');
            $table->index('test_type_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_tests');
    }
};

