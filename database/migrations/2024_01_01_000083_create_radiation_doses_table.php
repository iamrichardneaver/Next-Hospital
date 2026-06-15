<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiation_doses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('study_id');
            $table->decimal('dose_length_product', 10, 2)->nullable();
            $table->decimal('effective_dose', 10, 4)->nullable();
            $table->decimal('ctdi_vol', 10, 2)->nullable();
            $table->decimal('dlp', 10, 2)->nullable();
            $table->longText('dose_parameters')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['study_id'], 'radiation_doses_study_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiation_doses');
    }
};
