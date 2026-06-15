<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgery_procedures', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->enum('procedure_type', ['major', 'minor', 'diagnostic', 'therapeutic']);
            $table->string('category', 255)->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->enum('anesthesia_type', ['general', 'regional', 'local', 'conscious_sedation']);
            $table->integer('complexity_level')->default(1);
            $table->longText('equipment_required')->nullable();
            $table->longText('pre_op_requirements')->nullable();
            $table->text('post_op_care')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surgery_procedures');
    }
};
