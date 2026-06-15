<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class RevenueTransactionTracker
{
    /**
     * Log invoice creation.
     */
    public static function logInvoiceCreated(Invoice $invoice, array $properties = [])
    {
        ActivityLog::create([
            'log_name' => 'revenue',
            'description' => "Invoice #{$invoice->invoice_number} created for patient {$invoice->patient->first_name} {$invoice->patient->last_name}",
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
            'causer_type' => 'App\\Models\\User',
            'causer_id' => Auth::id(),
            'event' => 'created',
            'properties' => array_merge([
                'invoice_number' => $invoice->invoice_number,
                'patient_id' => $invoice->patient_id,
                'branch_id' => $invoice->branch_id,
                'total_amount' => $invoice->total_amount,
                'status' => $invoice->status,
                'items' => $invoice->items,
                'created_at' => $invoice->created_at->toISOString()
            ], $properties)
        ]);
    }

    /**
     * Log invoice status change.
     */
    public static function logInvoiceStatusChanged(Invoice $invoice, string $oldStatus, string $newStatus, array $properties = [])
    {
        ActivityLog::create([
            'log_name' => 'revenue',
            'description' => "Invoice #{$invoice->invoice_number} status changed from {$oldStatus} to {$newStatus}",
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
            'causer_type' => 'App\\Models\\User',
            'causer_id' => Auth::id(),
            'event' => 'updated',
            'properties' => array_merge([
                'invoice_number' => $invoice->invoice_number,
                'patient_id' => $invoice->patient_id,
                'branch_id' => $invoice->branch_id,
                'total_amount' => $invoice->total_amount,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'updated_at' => now()->toISOString()
            ], $properties)
        ]);
    }

    /**
     * Log payment creation.
     */
    public static function logPaymentCreated(Payment $payment, array $properties = [])
    {
        ActivityLog::create([
            'log_name' => 'revenue',
            'description' => "Payment #{$payment->payment_reference} of GHS {$payment->amount} received via {$payment->payment_method} for Invoice #{$payment->invoice->invoice_number}",
            'subject_type' => Payment::class,
            'subject_id' => $payment->id,
            'causer_type' => 'App\\Models\\User',
            'causer_id' => Auth::id(),
            'event' => 'created',
            'properties' => array_merge([
                'payment_reference' => $payment->payment_reference,
                'invoice_id' => $payment->invoice_id,
                'invoice_number' => $payment->invoice->invoice_number,
                'patient_id' => $payment->invoice->patient_id,
                'branch_id' => $payment->invoice->branch_id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'status' => $payment->status,
                'created_at' => $payment->created_at->toISOString()
            ], $properties)
        ]);
    }

    /**
     * Log payment status change.
     */
    public static function logPaymentStatusChanged(Payment $payment, string $oldStatus, string $newStatus, array $properties = [])
    {
        ActivityLog::create([
            'log_name' => 'revenue',
            'description' => "Payment #{$payment->payment_reference} status changed from {$oldStatus} to {$newStatus}",
            'subject_type' => Payment::class,
            'subject_id' => $payment->id,
            'causer_type' => 'App\\Models\\User',
            'causer_id' => Auth::id(),
            'event' => 'updated',
            'properties' => array_merge([
                'payment_reference' => $payment->payment_reference,
                'invoice_id' => $payment->invoice_id,
                'invoice_number' => $payment->invoice->invoice_number,
                'patient_id' => $payment->invoice->patient_id,
                'branch_id' => $payment->invoice->branch_id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'updated_at' => now()->toISOString()
            ], $properties)
        ]);
    }

    /**
     * Log revenue analytics access.
     */
    public static function logRevenueAnalyticsAccess(array $filters = [])
    {
        ActivityLog::create([
            'log_name' => 'revenue',
            'description' => 'Revenue analytics dashboard accessed',
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => 'App\\Models\\User',
            'causer_id' => Auth::id(),
            'event' => 'viewed',
            'properties' => array_merge([
                'filters' => $filters,
                'accessed_at' => now()->toISOString()
            ])
        ]);
    }

    /**
     * Get transaction trail for a specific invoice.
     */
    public static function getInvoiceTransactionTrail(Invoice $invoice)
    {
        return ActivityLog::where('log_name', 'revenue')
            ->where(function($query) use ($invoice) {
                $query->where('subject_type', Invoice::class)
                      ->where('subject_id', $invoice->id);
            })
            ->orWhere(function($query) use ($invoice) {
                $query->where('subject_type', Payment::class)
                      ->whereIn('subject_id', $invoice->payments()->pluck('id'));
            })
            ->with(['causer', 'subject'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get transaction trail for a specific payment.
     */
    public static function getPaymentTransactionTrail(Payment $payment)
    {
        return ActivityLog::where('log_name', 'revenue')
            ->where('subject_type', Payment::class)
            ->where('subject_id', $payment->id)
            ->with(['causer', 'subject'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get revenue transactions by date range.
     */
    public static function getRevenueTransactionsByDateRange($startDate, $endDate, $branchId = null)
    {
        $query = ActivityLog::where('log_name', 'revenue')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['causer', 'subject']);

        if ($branchId) {
            $query->whereJsonContains('properties->branch_id', $branchId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get revenue transactions by payment method.
     */
    public static function getRevenueTransactionsByPaymentMethod($paymentMethod, $startDate = null, $endDate = null)
    {
        $query = ActivityLog::where('log_name', 'revenue')
            ->where('subject_type', Payment::class)
            ->whereJsonContains('properties->payment_method', $paymentMethod)
            ->with(['causer', 'subject']);

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get revenue transactions by department/service type.
     */
    public static function getRevenueTransactionsByServiceType($serviceType, $startDate = null, $endDate = null)
    {
        $query = ActivityLog::where('log_name', 'revenue')
            ->where('subject_type', Invoice::class)
            ->whereJsonContains('properties->items', function($item) use ($serviceType) {
                return $item['service_type'] === $serviceType;
            })
            ->with(['causer', 'subject']);

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
