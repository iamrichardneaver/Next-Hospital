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
        // Add appointment_number to appointments table
        if (!Schema::hasColumn('appointments', 'appointment_number')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('appointment_number')->unique()->nullable()->after('id');
            });
        }

        // Add consultation_number to consultations table
        if (!Schema::hasColumn('consultations', 'consultation_number')) {
            Schema::table('consultations', function (Blueprint $table) {
                $table->string('consultation_number')->unique()->nullable()->after('id');
            });
        }

        // Add lab_request_number to lab_requests table
        if (!Schema::hasColumn('lab_requests', 'lab_request_number')) {
            Schema::table('lab_requests', function (Blueprint $table) {
                $table->string('lab_request_number')->unique()->nullable()->after('id');
            });
        }

        // Add invoice_number to invoices table
        if (!Schema::hasColumn('invoices', 'invoice_number')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('invoice_number')->unique()->nullable()->after('id');
            });
        }

        // Add prescription_number to prescriptions table
        if (!Schema::hasColumn('prescriptions', 'prescription_number')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->string('prescription_number')->unique()->nullable()->after('id');
            });
        }

        // Add scan_number to scans table
        if (!Schema::hasColumn('scans', 'scan_number')) {
            Schema::table('scans', function (Blueprint $table) {
                $table->string('scan_number')->unique()->nullable()->after('id');
            });
        }

        // Add store_order_number to store_orders table
        if (!Schema::hasColumn('store_orders', 'store_order_number')) {
            Schema::table('store_orders', function (Blueprint $table) {
                $table->string('store_order_number')->unique()->nullable()->after('id');
            });
        }

        // Add payment_reference to payments table
        if (!Schema::hasColumn('payments', 'payment_reference')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('payment_reference')->unique()->nullable()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('appointment_number');
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn('consultation_number');
        });

        Schema::table('lab_requests', function (Blueprint $table) {
            $table->dropColumn('lab_request_number');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('invoice_number');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn('prescription_number');
        });

        Schema::table('scans', function (Blueprint $table) {
            $table->dropColumn('scan_number');
        });

        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropColumn('store_order_number');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_reference');
        });
    }
};
