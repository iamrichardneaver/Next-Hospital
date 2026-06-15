<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Branch;
use App\Models\RevenueTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected $debtorService;
    protected PaymentPolicyService $paymentPolicyService;

    public function __construct(DebtorService $debtorService, PaymentPolicyService $paymentPolicyService)
    {
        $this->debtorService = $debtorService;
        $this->paymentPolicyService = $paymentPolicyService;
    }

    /**
     * Record a payment for an invoice.
     * 
     * @param int $invoiceId
     * @param float $amount
     * @param string $paymentMethod
     * @param array $metadata
     * @return array
     */
    public function recordPayment($invoiceId, float $amount, $paymentMethod, array $metadata = [])
    {
        DB::beginTransaction();

        try {
            // Validate invoice exists
            $invoice = Invoice::findOrFail($invoiceId);

            $paymentMethod = PaymentMethod::normalize($paymentMethod) ?? $paymentMethod;
            PaymentMethod::validateRecording($paymentMethod, $metadata);

            // Validate payment amount
            if ($amount <= 0) {
                throw new \Exception('Payment amount must be greater than zero');
            }

            $visit = isset($metadata['visit']) && $metadata['visit'] instanceof \App\Models\Visit
                ? $metadata['visit']
                : null;
            $this->paymentPolicyService->validatePaymentAmount($visit, $invoice, $amount);

            // Get patient_id and branch_id from invoice
            $patientId = $metadata['patient_id'] ?? $invoice->patient_id;
            $branchId = $metadata['branch_id'] ?? $invoice->branch_id;

            if (!$patientId || !$branchId) {
                throw new \Exception('Patient ID and Branch ID are required');
            }

            // Determine source platform
            $sourcePlatform = $metadata['source_platform'] ?? $this->detectSourcePlatform();

            // Extract metadata fields that should be stored separately
            $extractedFields = [
                'device_info',
                'ip_address',
                'payment_date',
                'reference_number',
                'transaction_id',
                'status',
                'notes',
                'processed_by',
                'patient_id',
                'branch_id',
                'source_platform'
            ];
            
            // Handle nested metadata: if metadata array contains a 'metadata' key, extract and merge it
            $paymentMetadata = $metadata;
            if (isset($metadata['metadata']) && is_array($metadata['metadata'])) {
                // Merge nested metadata with top-level metadata, giving priority to nested
                $nestedMetadata = $metadata['metadata'];
                unset($paymentMetadata['metadata']); // Remove nested key
                $paymentMetadata = array_merge($paymentMetadata, $nestedMetadata);
            }
            
            // Create payment record
            $payment = Payment::create([
                'invoice_id' => $invoiceId,
                'patient_id' => $patientId,
                'branch_id' => $branchId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'source_platform' => $sourcePlatform,
                'device_info' => $metadata['device_info'] ?? null,
                'ip_address' => $metadata['ip_address'] ?? request()->ip(),
                'payment_date' => $metadata['payment_date'] ?? now()->toDateString(),
                'reference_number' => $metadata['reference_number'] ?? $this->generateReferenceNumber(),
                'transaction_id' => $metadata['transaction_id'] ?? null,
                'status' => $metadata['status'] ?? 'completed',
                'notes' => $metadata['notes'] ?? null,
                'metadata' => $paymentMetadata, // Store full metadata for reference
                'processed_by' => $metadata['processed_by'] ?? auth()->id(),
                'processed_at' => now(),
                'created_by' => auth()->id()
            ]);

            // The PaymentObserver will handle:
            // - Updating invoice paid_amount and balance_amount
            // - Updating invoice payment_status
            // - Creating revenue transaction
            // - Updating debtor records

            DB::commit();

            // Load relationships
            $payment->load(['invoice.patient', 'patient', 'branch', 'processor']);

            return [
                'success' => true,
                'payment' => $payment,
                'invoice' => $payment->invoice->fresh(),
                'message' => 'Payment recorded successfully'
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Payment recording failed: ' . $e->getMessage(), [
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'payment' => null,
                'invoice' => null,
                'message' => 'Payment recording failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Record multiple payments at once.
     * 
     * @param array $payments Array of payment data
     * @return array
     */
    public function recordBatchPayments(array $payments)
    {
        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($payments as $paymentData) {
            $result = $this->recordPayment(
                $paymentData['invoice_id'],
                $paymentData['amount'],
                $paymentData['payment_method'],
                $paymentData['metadata'] ?? []
            );

            $results[] = $result;
            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        return [
            'success' => $failCount === 0,
            'total' => count($payments),
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'results' => $results,
            'message' => "Processed {$successCount} payments successfully, {$failCount} failed"
        ];
    }

    /**
     * Process payment refund.
     * 
     * @param int $paymentId
     * @param float $amount
     * @param string $reason
     * @return array
     */
    public function refundPayment($paymentId, $amount = null, $reason = null)
    {
        DB::beginTransaction();

        try {
            $payment = Payment::findOrFail($paymentId);

            // Validate payment can be refunded
            if ($payment->status === 'refunded') {
                throw new \Exception('Payment has already been refunded');
            }

            if ($payment->status !== 'completed') {
                throw new \Exception('Only completed payments can be refunded');
            }

            // Determine refund amount
            $refundAmount = $amount ?? $payment->amount;

            if ($refundAmount > $payment->amount) {
                throw new \Exception('Refund amount cannot exceed payment amount');
            }

            // Update payment status
            $payment->status = 'refunded';
            $payment->notes = ($payment->notes ? $payment->notes . ' | ' : '') . "Refunded: " . ($reason ?? 'No reason provided');
            $payment->save();

            // Create negative revenue transaction for refund
            RevenueTransaction::create([
                'patient_id' => $payment->patient_id,
                'branch_id' => $payment->branch_id,
                'source_type' => Payment::class,
                'source_id' => $payment->id,
                'service_type' => 'other',
                'amount' => -$refundAmount,
                'payment_method' => $payment->payment_method,
                'transaction_date' => now()->toDateString(),
                'status' => 'refunded',
                'metadata' => [
                    'original_payment_id' => $payment->id,
                    'refund_reason' => $reason,
                    'original_amount' => $payment->amount
                ],
                'recorded_by' => auth()->id()
            ]);

            // Update invoice payment tracking (observer will handle this)
            $payment->touch(); // Trigger observer

            DB::commit();

            return [
                'success' => true,
                'payment' => $payment->fresh(),
                'refund_amount' => $refundAmount,
                'message' => 'Payment refunded successfully'
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Payment refund failed: ' . $e->getMessage());

            return [
                'success' => false,
                'payment' => null,
                'refund_amount' => 0,
                'message' => 'Payment refund failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get payment summary for an invoice.
     * 
     * @param int $invoiceId
     * @return array
     */
    public function getInvoicePaymentSummary($invoiceId)
    {
        $invoice = Invoice::with('payments')->findOrFail($invoiceId);

        $completedPayments = $invoice->payments->where('status', 'completed');
        $refundedPayments = $invoice->payments->where('status', 'refunded');

        return [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'total_amount' => $invoice->total_amount,
            'paid_amount' => $invoice->paid_amount,
            'balance_amount' => $invoice->balance_amount,
            'payment_status' => $invoice->payment_status,
            'total_payments' => $invoice->payments->count(),
            'completed_payments' => $completedPayments->count(),
            'completed_amount' => $completedPayments->sum('amount'),
            'refunded_payments' => $refundedPayments->count(),
            'refunded_amount' => $refundedPayments->sum('amount'),
            'payment_history' => $invoice->payments
        ];
    }

    /**
     * Get payment summary for a patient.
     * 
     * @param int $patientId
     * @param int $branchId
     * @return array
     */
    public function getPatientPaymentSummary($patientId, $branchId = null)
    {
        $query = Payment::where('patient_id', $patientId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $payments = $query->get();
        $completedPayments = $payments->where('status', 'completed');

        return [
            'patient_id' => $patientId,
            'total_payments' => $payments->count(),
            'total_paid' => $completedPayments->sum('amount'),
            'by_method' => $completedPayments->groupBy('payment_method')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount')
                ];
            }),
            'last_payment_date' => $completedPayments->max('payment_date'),
            'recent_payments' => $payments->sortByDesc('created_at')->take(10)->values()
        ];
    }

    /**
     * Generate unique payment reference number.
     * 
     * @return string
     */
    protected function generateReferenceNumber()
    {
        return 'PAY-' . strtoupper(uniqid());
    }

    /**
     * Validate payment method.
     * 
     * @param string $method
     * @return bool
     */
    public function isValidPaymentMethod($method)
    {
        $normalized = PaymentMethod::normalize($method);
        return $normalized && in_array($normalized, PaymentMethod::allValues(), true);
    }

    /**
     * Detect source platform from request context.
     * 
     * @return string
     */
    protected function detectSourcePlatform()
    {
        // Check if request is from API route
        if (request()->is('api/*')) {
            // Check user agent for mobile indicators
            $userAgent = request()->header('User-Agent', '');

            if (
                str_contains(strtolower($userAgent), 'dart') ||
                str_contains(strtolower($userAgent), 'flutter') ||
                str_contains(strtolower($userAgent), 'okhttp')
            ) {
                return 'mobile';
            }

            return 'api';
        }

        return 'web';
    }

    /**
     * Calculate change for cash payment.
     * 
     * @param float $amountDue
     * @param float $amountReceived
     * @return array
     */
    public function calculateChange($amountDue, $amountReceived)
    {
        $change = $amountReceived - $amountDue;

        return [
            'amount_due' => $amountDue,
            'amount_received' => $amountReceived,
            'change' => max(0, $change),
            'overpayment' => $change > 0,
            'underpayment' => $change < 0,
            'shortage' => $change < 0 ? abs($change) : 0
        ];
    }
}

