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
        Schema::table('appointments', function (Blueprint $table) {
            $table->boolean('is_teleconsultation')->default(false);
            $table->foreignId('teleconsultation_id')->nullable()->constrained()->onDelete('set null');
            $table->string('meeting_url')->nullable();
            $table->string('meeting_password')->nullable();
            
            // Index for teleconsultation appointments
            $table->index(['is_teleconsultation', 'appointment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['is_teleconsultation', 'appointment_date']);
            $table->dropColumn([
                'is_teleconsultation',
                'teleconsultation_id',
                'meeting_url',
                'meeting_password',
            ]);
        });
    }
};