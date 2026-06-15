<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_donations', function (Blueprint $table) {
            $table->id();
            $table->string('donation_id', 255);
            $table->unsignedBigInteger('donor_id')->nullable();
            $table->string('donor_name', 255);
            $table->string('donor_phone', 255)->nullable();
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']);
            $table->decimal('volume_ml', 8, 2);
            $table->date('donation_date');
            $table->time('donation_time')->nullable();
            $table->date('expiry_date');
            $table->enum('status', ['pending', 'tested', 'approved', 'rejected', 'used', 'expired'])->default('pending');
            $table->text('screening_notes')->nullable();
            $table->enum('hiv_test', ['positive', 'negative', 'pending'])->nullable();
            $table->enum('hbv_test', ['positive', 'negative', 'pending'])->nullable();
            $table->enum('hcv_test', ['positive', 'negative', 'pending'])->nullable();
            $table->enum('syphilis_test', ['positive', 'negative', 'pending'])->nullable();
            $table->string('blood_bag_number', 255)->nullable();
            $table->unsignedBigInteger('collected_by')->nullable();
            $table->unsignedBigInteger('tested_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('tested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['blood_bag_number']);
            $table->index(['blood_group', 'status']);
            $table->index(['donation_date']);
            $table->unique(['donation_id']);
            $table->index(['expiry_date']);
$table->foreign('approved_by', 'blood_donations_approved_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
$table->foreign('branch_id', 'blood_donations_branch_id_foreign')->references('id')->on('branches')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('collected_by', 'blood_donations_collected_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
$table->foreign('donor_id', 'blood_donations_donor_id_foreign')->references('id')->on('patients')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('tested_by', 'blood_donations_tested_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_donations');
    }
};
