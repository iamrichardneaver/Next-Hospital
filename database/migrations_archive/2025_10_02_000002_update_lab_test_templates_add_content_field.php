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
        Schema::table('lab_test_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_test_templates', 'template_content')) {
                $table->longText('template_content')->nullable()->after('description');
            }
            // Add category_id if not exists
            if (!Schema::hasColumn('lab_test_templates', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('id');
                $table->foreign('category_id')->references('id')->on('lab_test_categories')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_test_templates', function (Blueprint $table) {
            if (Schema::hasColumn('lab_test_templates', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }
            if (Schema::hasColumn('lab_test_templates', 'template_content')) {
                $table->dropColumn('template_content');
            }
        });
    }
};

