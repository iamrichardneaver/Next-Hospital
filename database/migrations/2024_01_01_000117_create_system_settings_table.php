<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('timezone', 255)->default('Africa/Accra');
            $table->string('date_format', 255)->default('Y-m-d');
            $table->string('time_format', 255)->default('H:i:s');
            $table->string('currency', 255)->default('GHS');
            $table->string('currency_symbol', 255)->default('₵');
            $table->decimal('tax_rate', 5, 4)->nullable()->default(0.1500);
            $table->decimal('delivery_fee', 10, 2)->nullable()->default(5.00);
            $table->integer('session_timeout')->default(120);
            $table->integer('password_min_length')->default(8);
            $table->boolean('require_password_change')->default(0);
            $table->integer('password_change_days')->default(90);
            $table->boolean('enable_audit_logs')->default(1);
            $table->integer('audit_log_retention_days')->default(365);
            $table->boolean('enable_maintenance_mode')->default(0);
            $table->text('maintenance_message')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->decimal('registration_fee', 10, 2)->default(0.00);
            $table->boolean('registration_fee_apply_to_new_patients')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
