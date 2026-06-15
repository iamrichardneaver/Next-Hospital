<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->text('delivery_address');
            $table->string('delivery_phone', 255);
            $table->text('delivery_notes')->nullable();
            $table->text('rider_notes')->nullable();
            $table->decimal('delivery_rating', 3, 2)->nullable();
            $table->enum('status', ['pending', 'assigned', 'in_transit', 'delivered', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('estimated_delivery')->nullable();
            $table->timestamp('actual_delivery')->nullable();
            $table->string('delivery_person', 255)->nullable();
            $table->unsignedBigInteger('rider_id')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['created_by'], 'deliveries_created_by_foreign');
            $table->index(['order_id'], 'deliveries_order_id_foreign');
            $table->index(['updated_by'], 'deliveries_updated_by_foreign');
$table->foreign('rider_id', 'deliveries_rider_id_foreign')->references('id')->on('delivery_riders')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
