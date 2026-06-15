<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\RevenueTransaction;

class InvoiceObserver
{
    /**
     * Handle the Invoice "creating" event.
     */
    public function creating(Invoice $invoice): void
    {
        // Initialize payment tracking fields for new invoices
        if (is_null($invoice->paid_amount)) {
            $invoice->paid_amount = 0.00;
        }
        
        if (is_null($invoice->balance_amount)) {
            $invoice->balance_amount = $invoice->total_amount;
        }
        
        if (is_null($invoice->payment_status)) {
            $invoice->payment_status = 'unpaid';
        }
    }

    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        // Revenue is recorded by PaymentObserver when payments complete (avoids duplicate counting)
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        // Recalculate payment status based on paid_amount
        if ($invoice->isDirty('paid_amount') || $invoice->isDirty('total_amount')) {
            $this->updatePaymentStatus($invoice);
        }
    }

    /**
     * Handle the Invoice "deleting" event.
     */
    public function deleting(Invoice $invoice): void
    {
        // Clean up related revenue transactions
        RevenueTransaction::where('source_type', Invoice::class)
            ->where('source_id', $invoice->id)
            ->delete();
    }

    /**
     * Update invoice payment status based on paid amount.
     */
    protected function updatePaymentStatus(Invoice $invoice): void
    {
        $paidAmount = $invoice->paid_amount ?? 0;
        $totalAmount = $invoice->total_amount ?? 0;
        $balanceAmount = $totalAmount - $paidAmount;
        
        // Update balance_amount
        $invoice->balance_amount = $balanceAmount;
        
        // Determine payment status
        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            $invoice->payment_status = 'paid';
            $invoice->status = 'paid';
        } elseif ($paidAmount > 0 && $paidAmount < $totalAmount) {
            $invoice->payment_status = 'partial';
            if ($invoice->status !== 'overdue') {
                $invoice->status = 'partial';
            }
        } elseif ($paidAmount == 0) {
            // Check if overdue
            if ($invoice->due_date && $invoice->due_date < now()->toDateString()) {
                $invoice->payment_status = 'overdue';
                $invoice->status = 'overdue';
            } else {
                $invoice->payment_status = 'unpaid';
                if ($invoice->status === 'draft' || $invoice->status === 'pending') {
                    // Keep existing status
                } else {
                    $invoice->status = 'pending';
                }
            }
        }
        
        // Save without triggering the observer again
        $invoice->saveQuietly();
    }

    /**
     * Create revenue transaction for invoice.
     */
    protected function createRevenueTransaction(Invoice $invoice): void
    {
        try {
            // Determine service type from invoice items
            $serviceType = $this->determineServiceType($invoice);
            
            RevenueTransaction::create([
                'patient_id' => $invoice->patient_id,
                'branch_id' => $invoice->branch_id,
                'source_type' => Invoice::class,
                'source_id' => $invoice->id,
                'service_type' => $serviceType,
                'amount' => $invoice->total_amount,
                'payment_method' => $invoice->payment_method,
                'source_platform' => $invoice->source_platform ?? 'web',
                'transaction_date' => $invoice->invoice_date ?? now()->toDateString(),
                'status' => $invoice->status === 'paid' ? 'completed' : 'pending',
                'metadata' => [
                    'invoice_number' => $invoice->invoice_number,
                    'items_count' => is_array($invoice->items) ? count($invoice->items) : 0,
                ],
                'recorded_by' => $invoice->created_by ?? auth()->id() ?? 1
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create revenue transaction for invoice: ' . $e->getMessage());
        }
    }

    /**
     * Determine service type from invoice items.
     */
    protected function determineServiceType(Invoice $invoice): string
    {
        if (!is_array($invoice->items) || empty($invoice->items)) {
            return 'other';
        }
        
        // Get the first item's service type
        $firstItem = $invoice->items[0] ?? [];
        $serviceType = $firstItem['service_type'] ?? null;
        
        // Map service types
        $serviceTypeMap = [
            'consultation' => 'consultation',
            'lab_test' => 'lab',
            'lab' => 'lab',
            'imaging' => 'imaging',
            'radiology' => 'imaging',
            'pharmacy' => 'pharmacy',
            'drug' => 'pharmacy',
            'medication' => 'pharmacy',
            'ward' => 'ward',
            'bed' => 'ward',
            'surgery' => 'surgery',
            'procedure' => 'surgery',
            'ecommerce' => 'ecommerce',
            'store' => 'ecommerce',
        ];
        
        return $serviceTypeMap[strtolower($serviceType ?? '')] ?? 'other';
    }
}

