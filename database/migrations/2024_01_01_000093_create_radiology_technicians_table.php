<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_technicians', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('license_number', 255);
            $table->string('certification_body', 255);
            $table->date('certification_date');
            $table->date('expiry_date');
            $table->longText('specializations');
            $table->unsignedBigInteger('department_id');
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['department_id'], 'radiology_technicians_department_id_foreign');
            $table->unique(['license_number']);
            $table->index(['user_id'], 'radiology_technicians_user_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_technicians');
    }
};
