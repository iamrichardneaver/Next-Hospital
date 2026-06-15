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
            $table->boolean('nhis_eligible')->default(false)->after('plan');
            $table->boolean('requires_referral')->default(false)->after('nhis_eligible');
            $table->text('referral_notes')->nullable()->after('requires_referral');
            $table->timestamp('started_at')->nullable()->after('referral_notes');
            $table->timestamp('completed_at')->nullable()->after('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn([
                'nhis_eligible',
                'requires_referral', 
                'referral_notes',
                'started_at',
                'completed_at'
            ]);
        });
    }
};