<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_settings', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 255);
            $table->text('header_html')->nullable();
            $table->text('footer_html')->nullable();
            $table->string('header_height', 255)->default('50px');
            $table->string('footer_height', 255)->default('30px');
            $table->longText('margins')->nullable();
            $table->string('font_family', 255)->default('Arial');
            $table->integer('font_size')->default(12);
            $table->boolean('show_logo')->default(1);
            $table->boolean('show_address')->default(1);
            $table->boolean('show_contact')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_settings');
    }
};
