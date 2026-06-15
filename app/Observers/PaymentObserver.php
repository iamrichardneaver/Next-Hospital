<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\RevenueTransaction;
use App\Services\AppNotificationService;
use Illuminate\Support\Facades\DB;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        // Update invoice paid_amount and balance_amount
        if ($payment->invoice_id && $payment->status === 'completed') {
            $this->updateInvoicePaymentTracking($payment->invoice_id);
            
            // Create revenue transaction for the payment
            $this->createRevenueTransaction($payment);
            
            // Update debtor records
            $this->updateDebtorRecords($payment);

            $this->notifyPatientPaymentReceived($payment);
        }
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        // If payment status changed or amount changed, update invoice
        if ($payment->isDirty('status') || $payment->isDirty('amount')) {
            if ($payment->invoice_id) {
                $this->updateInvoicePaymentTracking($payment->invoice_id);
                
                // Update debtor records
                $this->updateDebtorRecords($payment);
                
                if ($payment->status === 'completed') {
                    $exists = RevenueTransaction::where('source_type', Payment::class)
                        ->where('source_id', $payment->id)
                        ->exists();

                    if (!$exists) {
                        $this->createRevenueTransaction($payment);
                    } else {
                        $this->updateRevenueTransactionStatus($payment);
                    }

                    if ($payment->wasChanged('status')) {
                        $this->notifyPatientPaymentReceived($payment);
                    }
                } else {
                    $this->updateRevenueTransactionStatus($payment);
                }
            }
        }
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        // Update invoice payment tracking
        if ($payment->invoice_id) {
            $this->updateInvoicePaymentTracking($payment->invoice_id);
            
            // Update debtor records
            $this->updateDebtorRecords($payment);
        }
        
        // Delete associated revenue transaction
        RevenueTransaction::where('source_type', Payment::class)
            ->where('source_id', $payment->id)
            ->delete();
    }

    /**
     * Update invoice payment tracking.
     */
    protected function updateInvoicePaymentTracking($invoiceId): void
    {
        try {
            $invoice = Invoice::find($invoiceId);
            if (!$invoice) {
                return;
            }
            
            // Calculate total paid amount from all completed payments
            $totalPaid = Payment::where('invoice_id', $invoiceId)
                ->where('status', 'completed')
                ->sum('amount');
            
            // Update invoice without triggering observer
            $invoice->paid_amount = $totalPaid;
            $invoice->balance_amount = $invoice->total_amount - $totalPaid;
            
            // Update payment status
            if ($totalPaid >= $invoice->total_amount && $invoice->total_amount > 0) {
                $invoice->payment_status = 'paid';
                $invoice->status = 'paid';
            } elseif ($totalPaid > 0 && $totalPaid < $invoice->total_amount) {
                $invoice->payment_status = 'partial';
                if ($invoice->status !== 'overdue') {
                    $invoice->status = 'partial';
                }
            } else {
                // Check if overdue
                if ($invoice->due_date && $invoice->due_date < now()->toDateString()) {
                    $invoice->payment_status = 'overdue';
                    $invoice->status = 'overdue';
                } else {
                    $invoice->payment_status = 'unpaid';
                }
            }
            
            $invoice->saveQuietly();
        } catch (\Exception $e) {
            \Log::error('Failed to update invoice payment tracking: ' . $e->getMessage());
        }
    }

    /**
     * Create revenue transaction for payment.
     */
    protected function createRevenueTransaction(Payment $payment): void
    {
        try {
            $invoice = $payment->invoice;
            if (!$invoice) {
                return;
            }
            
            // Determine service type from invoice
            $serviceType = $this->determineServiceTypeFromInvoice($invoice);
            
            RevenueTransaction::create([
                'patient_id' => $payment->patient_id,
                'branch_id' => $payment->branch_id,
                'source_type' => Payment::class,
                'source_id' => $payment->id,
                'service_type' => $serviceType,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'source_platform' => $payment->source_platform ?? 'web',
                'transaction_date' => $payment->payment_date ?? now()->toDateString(),
                'status' => $payment->status === 'completed' ? 'completed' : 'pending',
                'metadata' => [
                    'payment_reference' => $payment->payment_reference,
                    'transaction_id' => $payment->transaction_id,
                    'invoice_id' => $payment->invoice_id,
                    'invoice_number' => $invoice->invoice_number ?? null,
                    'device_info' => $payment->device_info,
                    'ip_address' => $payment->ip_address
                ],
                'recorded_by' => $payment->processed_by ?? auth()->id() ?? 1
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create revenue transaction for payment: ' . $e->getMessage());
        }
    }

    /**
     * Update revenue transaction status.
     */
    protected function updateRevenueTransactionStatus(Payment $payment): void
    {
        try {
            $revenueTransaction = RevenueTransaction::where('source_type', Payment::class)
                ->where('source_id', $payment->id)
                ->first();
            
            if ($revenueTransaction) {
                $revenueTransaction->status = $payment->status === 'completed' ? 'completed' : 'pending';
                $revenueTransaction->amount = $payment->amount;
                $revenueTransaction->save();
            }
        } catch (\Exception $e) {
            \Log::error('Failed to update revenue transaction status: ' . $e->getMessage());
        }
    }

    /**
     * Update debtor records.
     */
    protected function updateDebtorRecords(Payment $payment): void
    {
        try {
            if ($payment->patient_id && $payment->branch_id) {
                $debtorService = app(\App\Services\DebtorService::class);
                $debtorService->createOrUpdateDebtor($payment->patient_id, $payment->branch_id);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to update debtor records: ' . $e->getMessage());
        }
    }

    /**
     * Determine service type from invoice.
     */
    protected function determineServiceTypeFromInvoice($invoice): string
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
            'insurance' => 'insurance',
            'nhis' => 'insurance',
            'claim' => 'insurance',
        ];
        
        return $serviceTypeMap[strtolower($serviceType ?? '')] ?? 'other';
    }

    protected function notifyPatientPaymentReceived(Payment $payment): void
    {
        try {
            app(AppNotificationService::class)->notifyPaymentReceived(
                $payment->loadMissing(['patient']),
                $payment->processed_by
            );
        } catch (\Throwable $e) {
            \Log::error('Failed to send payment notification: ' . $e->getMessage());
        }
    }
}

