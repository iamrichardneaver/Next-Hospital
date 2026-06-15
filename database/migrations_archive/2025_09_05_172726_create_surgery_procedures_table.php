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
        Schema::create('surgery_procedures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('procedure_type', ['major', 'minor', 'diagnostic', 'therapeutic']);
            $table->string('category')->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->enum('anesthesia_type', ['general', 'regional', 'local', 'conscious_sedation']);
            $table->integer('complexity_level')->default(1)->comment('1=Low, 2=Medium, 3=High, 4=Very High');
            $table->json('equipment_required')->nullable();
            $table->json('pre_op_requirements')->nullable();
            $table->text('post_op_care')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surgery_procedures');
    }
};
