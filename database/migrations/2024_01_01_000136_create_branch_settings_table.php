<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('setting_key', 255);
            $table->text('setting_value')->nullable();
            $table->string('setting_type', 255)->default('string');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['branch_id', 'setting_key']);
$table->foreign('branch_id', 'branch_settings_branch_id_foreign')->references('id')->on('branches')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_settings');
    }
};
