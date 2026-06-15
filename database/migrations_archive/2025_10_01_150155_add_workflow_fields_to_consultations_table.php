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
            $table->text('clinical_notes')->nullable()->after('follow_up_instructions');
            $table->json('workflow_steps')->nullable()->after('clinical_notes');
            $table->string('next_stage')->nullable()->after('workflow_steps');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn(['clinical_notes', 'workflow_steps', 'next_stage']);
        });
    }
};
