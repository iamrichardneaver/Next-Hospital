<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_riders', function (Blueprint $table) {
            $table->id();
            $table->string('rider_number', 255);
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('phone', 255);
            $table->string('emergency_contact', 255)->nullable();
            $table->string('vehicle_type', 255)->nullable();
            $table->string('vehicle_number', 255)->nullable();
            $table->string('license_number', 255)->nullable();
            $table->enum('status', ['active', 'inactive', 'on_delivery', 'off_duty'])->default('active');
            $table->integer('total_deliveries')->default(0);
            $table->integer('successful_deliveries')->default(0);
            $table->integer('failed_deliveries')->default(0);
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unique(['rider_number']);
            $table->index(['status', 'branch_id']);
$table->foreign('branch_id', 'delivery_riders_branch_id_foreign')->references('id')->on('branches')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('created_by', 'delivery_riders_created_by_foreign')->references('id')->on('users')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('updated_by', 'delivery_riders_updated_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
$table->foreign('user_id', 'delivery_riders_user_id_foreign')->references('id')->on('users')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_riders');
    }
};
