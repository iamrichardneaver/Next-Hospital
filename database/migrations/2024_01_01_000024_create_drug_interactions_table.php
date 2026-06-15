<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drug_interactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('drug1_id');
            $table->unsignedBigInteger('drug2_id');
            $table->enum('severity', ['minor', 'moderate', 'major', 'severe'])->default('moderate');
            $table->text('description');
            $table->text('clinical_effect');
            $table->text('management');
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['drug1_id', 'drug2_id']);
            $table->index(['drug2_id'], 'drug_interactions_drug2_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drug_interactions');
    }
};
