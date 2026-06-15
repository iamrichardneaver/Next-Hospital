<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    /**
     * Create standardized invoice.
     * 
     * @param int $patientId
     * @param int $branchId
     * @param array $items
     * @param array $options
     * @return Invoice
     */
    public function createInvoice($patientId, $branchId, array $items, array $options = [])
    {
        // Validate patient and branch
        $patient = Patient::findOrFail($patientId);
        $branch = Branch::findOrFail($branchId);
        
        // Calculate totals
        $subtotal = 0;
        $processedItems = [];
        
        foreach ($items as $item) {
            $quantity = $item['quantity'] ?? 1;
            $unitPrice = $item['unit_price'] ?? 0;
            $total = $quantity * $unitPrice;
            $subtotal += $total;
            
            $processedItems[] = [
                'id' => $item['id'] ?? 'item_' . uniqid(),
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $total,
                'service_type' => $item['service_type'] ?? 'other',
                'metadata' => $item['metadata'] ?? null
            ];
        }
        
        // Calculate tax and discount
        $taxAmount = $options['tax_amount'] ?? 0;
        $discountAmount = $options['discount_amount'] ?? 0;
        $totalAmount = $subtotal + $taxAmount - $discountAmount;
        
        // Create invoice
        $invoice = Invoice::create([
            'patient_id' => $patientId,
            'branch_id' => $branchId,
            'invoice_date' => $options['invoice_date'] ?? now()->toDateString(),
            'due_date' => $options['due_date'] ?? now()->addDays(30)->toDateString(),
            'items' => $processedItems,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'status' => $options['status'] ?? 'pending',
            'payment_method' => $options['payment_method'] ?? null,
            'notes' => $options['notes'] ?? null,
            'created_by' => $options['created_by'] ?? auth()->id() ?? 1
        ]);
        
        Log::info('Invoice created via InvoiceService', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'patient_id' => $patientId,
            'branch_id' => $branchId,
            'total_amount' => $totalAmount,
            'items_count' => count($processedItems)
        ]);
        
        return $invoice;
    }

    /**
     * Create invoice and record payment in one transaction.
     * 
     * @param int $patientId
     * @param int $branchId
     * @param array $items
     * @param float $paymentAmount
     * @param string $paymentMethod
     * @param array $options
     * @return array
     */
    public function createInvoiceWithPayment($patientId, $branchId, array $items, $paymentAmount, $paymentMethod, array $options = [])
    {
        DB::beginTransaction();
        
        try {
            // Create invoice
            $invoice = $this->createInvoice($patientId, $branchId, $items, $options);
            
            // Record payment using PaymentService
            $paymentService = app(PaymentService::class);
            $paymentResult = $paymentService->recordPayment(
                $invoice->id,
                $paymentAmount,
                $paymentMethod,
                [
                    'reference_number' => $options['payment_reference'] ?? null,
                    'notes' => $options['payment_notes'] ?? 'Payment on invoice creation',
                    'processed_by' => $options['processed_by'] ?? auth()->id()
                ]
            );
            
            if (!$paymentResult['success']) {
                throw new \Exception($paymentResult['message']);
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'invoice' => $paymentResult['invoice'], // Updated invoice
                'payment' => $paymentResult['payment'],
                'message' => 'Invoice created and payment recorded successfully'
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Invoice creation with payment failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'invoice' => null,
                'payment' => null,
                'message' => 'Failed to create invoice: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update invoice items (for editing before payment).
     * 
     * @param int $invoiceId
     * @param array $items
     * @return Invoice
     */
    public function updateInvoiceItems($invoiceId, array $items)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        
        // Only allow editing unpaid or draft invoices
        if (in_array($invoice->payment_status, ['paid', 'partial'])) {
            throw new \Exception('Cannot edit invoices that have been paid or partially paid');
        }
        
        // Recalculate totals
        $subtotal = 0;
        $processedItems = [];
        
        foreach ($items as $item) {
            $quantity = $item['quantity'] ?? 1;
            $unitPrice = $item['unit_price'] ?? 0;
            $total = $quantity * $unitPrice;
            $subtotal += $total;
            
            $processedItems[] = [
                'id' => $item['id'] ?? 'item_' . uniqid(),
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $total,
                'service_type' => $item['service_type'] ?? 'other'
            ];
        }
        
        // Update invoice
        $invoice->items = $processedItems;
        $invoice->subtotal = $subtotal;
        $invoice->total_amount = $subtotal + $invoice->tax_amount - $invoice->discount_amount;
        $invoice->balance_amount = $invoice->total_amount - $invoice->paid_amount;
        $invoice->save();
        
        return $invoice;
    }
}

