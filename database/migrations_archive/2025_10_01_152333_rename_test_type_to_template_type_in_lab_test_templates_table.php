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
            $table->renameColumn('test_type', 'template_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_test_templates', function (Blueprint $table) {
            $table->renameColumn('template_type', 'test_type');
        });
    }
};
