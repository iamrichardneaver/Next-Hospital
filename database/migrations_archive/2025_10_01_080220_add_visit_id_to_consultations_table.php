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
        Schema::table('consultations', function (Blueprint $table) {
            $table->unsignedBigInteger('visit_id')->nullable()->after('branch_id');
            $table->foreign('visit_id')->references('id')->on('visits')->onDelete('set null');
            $table->index('visit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropForeign(['visit_id']);
            $table->dropIndex(['visit_id']);
            $table->dropColumn('visit_id');
        });
    }
};