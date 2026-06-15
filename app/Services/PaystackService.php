<?php

namespace App\Services;

use App\Models\PaymentSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected $secretKey;
    protected $publicKey;
    protected $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $settings = PaymentSetting::getByProvider('paystack');
        if ($settings) {
            $this->secretKey = $settings->secret_key;
            $this->publicKey = $settings->public_key;
            
            // Use sandbox URL if in test environment
            if ($settings->environment === 'test' || $settings->environment === 'sandbox') {
                $this->baseUrl = 'https://api.paystack.co'; // Paystack uses same URL for test and live
            }
        }
    }

    /**
     * Verify Paystack-settled amount matches expected GHS amount (tolerance for rounding).
     */
    public function amountMatchesExpected(float $expectedAmountGhs, array $paystackData, float $tolerance = 0.02): bool
    {
        $paystackAmount = round((float) ($paystackData['amount'] ?? 0) / 100, 2);
        $expected = round($expectedAmountGhs, 2);

        return abs($paystackAmount - $expected) <= $tolerance;
    }

    /**
     * Verify a payment transaction
     */
    public function verifyTransaction($reference)
    {
        try {
            Log::info('Paystack: Verifying transaction', ['reference' => $reference]);

            if (!$this->secretKey) {
                throw new \Exception('Paystack secret key not configured');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/transaction/verify/{$reference}");

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Paystack: Transaction verified', [
                    'reference' => $reference,
                    'status' => $data['data']['status'] ?? 'unknown'
                ]);

                return [
                    'success' => true,
                    'data' => $data['data'],
                ];
            }

            Log::error('Paystack: Verification failed', [
                'reference' => $reference,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Payment verification failed',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack: Verification error', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Initialize a transaction
     */
    public function initializeTransaction($email, $amount, $reference, $metadata = [], $callbackUrl = null)
    {
        try {
            Log::info('Paystack: Initializing transaction', [
                'email' => $email,
                'amount' => $amount,
                'reference' => $reference
            ]);

            if (!$this->secretKey) {
                throw new \Exception('Paystack secret key not configured');
            }

            // Amount should be in kobo/pesewas (x100)
            $amountInKobo = $amount * 100;

            // Use dynamic callback URL if not provided
            if (!$callbackUrl) {
                $callbackUrl = \App\Models\PaymentSetting::getPaystackCallbackUrl();
            }

            $payload = [
                'email' => $email,
                'amount' => $amountInKobo,
                'reference' => $reference,
                'currency' => 'GHS',
                'callback_url' => $callbackUrl,
                'metadata' => $metadata,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/transaction/initialize", $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Paystack: Transaction initialized', [
                    'reference' => $reference,
                    'authorization_url' => $data['data']['authorization_url'] ?? null,
                    'callback_url' => $callbackUrl
                ]);

                return [
                    'success' => true,
                    'data' => $data['data'],
                ];
            }

            Log::error('Paystack: Initialization failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Payment initialization failed',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack: Initialization error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get transaction details
     */
    public function getTransaction($reference)
    {
        try {
            if (!$this->secretKey) {
                throw new \Exception('Paystack secret key not configured');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/transaction/verify/{$reference}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Paystack: Get transaction error', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Check if Paystack is configured
     */
    public function isConfigured()
    {
        return !empty($this->secretKey) && !empty($this->publicKey);
    }

    /**
     * Get public key
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * Validate webhook signature
     */
    public function validateWebhookSignature($payload, $signature)
    {
        if (!$this->secretKey) {
            Log::error('Paystack: Cannot validate webhook - secret key not configured');
            return false;
        }

        $computedSignature = hash_hmac('sha512', $payload, $this->secretKey);
        
        $isValid = hash_equals($computedSignature, $signature);
        
        Log::info('Paystack: Webhook signature validation', [
            'is_valid' => $isValid
        ]);

        return $isValid;
    }

    /**
     * Handle charge.success event
     */
    public function handleChargeSuccess($data)
    {
        try {
            Log::info('Paystack: Processing charge.success event', [
                'reference' => $data['reference'] ?? null,
                'amount' => $data['amount'] ?? null
            ]);

            $reference = $data['reference'] ?? null;
            $status = $data['status'] ?? null;
            $amount = ($data['amount'] ?? 0) / 100; // Convert from kobo to cedis

            if (!$reference) {
                Log::error('Paystack: charge.success missing reference');
                return ['success' => false, 'message' => 'Missing reference'];
            }

            // Find payment record by reference
            $payment = \App\Models\Payment::where('reference_number', $reference)->first();

            if ($payment) {
                if (!$this->amountMatchesExpected((float) $payment->amount, $data)) {
                    Log::error('Paystack: Amount mismatch on charge.success', [
                        'reference' => $reference,
                        'expected' => $payment->amount,
                        'received' => ($data['amount'] ?? 0) / 100,
                    ]);
                    return ['success' => false, 'message' => 'Payment amount verification failed'];
                }

                $payment->status = 'completed';
                $payment->transaction_id = $data['id'] ?? null;
                // Merge with existing metadata if any, otherwise set new
                $existingMetadata = $payment->metadata ?? [];
                $payment->metadata = array_merge($existingMetadata, $data);
                $payment->save();

                Log::info('Paystack: Payment updated', ['payment_id' => $payment->id]);

                // Update invoice if exists
                if ($payment->invoice_id) {
                    $this->updateInvoiceStatus($payment->invoice_id);
                }

                return ['success' => true, 'payment_id' => $payment->id];
            }

            Log::warning('Paystack: Payment record not found for reference', ['reference' => $reference]);
            return ['success' => false, 'message' => 'Payment record not found'];

        } catch (\Exception $e) {
            Log::error('Paystack: Error handling charge.success', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle charge.failed event
     */
    public function handleChargeFailed($data)
    {
        try {
            Log::info('Paystack: Processing charge.failed event', [
                'reference' => $data['reference'] ?? null
            ]);

            $reference = $data['reference'] ?? null;

            if (!$reference) {
                return ['success' => false, 'message' => 'Missing reference'];
            }

            $payment = \App\Models\Payment::where('reference_number', $reference)->first();

            if ($payment) {
                $payment->status = 'failed';
                // Merge with existing metadata if any, otherwise set new
                $existingMetadata = $payment->metadata ?? [];
                $payment->metadata = array_merge($existingMetadata, $data);
                $payment->notes = 'Payment failed: ' . ($data['gateway_response'] ?? 'Unknown error');
                $payment->save();

                Log::info('Paystack: Payment marked as failed', ['payment_id' => $payment->id]);
                return ['success' => true, 'payment_id' => $payment->id];
            }

            return ['success' => false, 'message' => 'Payment record not found'];

        } catch (\Exception $e) {
            Log::error('Paystack: Error handling charge.failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle transfer.success event (for refunds)
     */
    public function handleTransferSuccess($data)
    {
        try {
            Log::info('Paystack: Processing transfer.success event', [
                'reference' => $data['reference'] ?? null,
                'amount' => $data['amount'] ?? null
            ]);

            // Handle refund logic here
            return ['success' => true, 'message' => 'Transfer successful'];

        } catch (\Exception $e) {
            Log::error('Paystack: Error handling transfer.success', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle transfer.failed event
     */
    public function handleTransferFailed($data)
    {
        try {
            Log::info('Paystack: Processing transfer.failed event', [
                'reference' => $data['reference'] ?? null
            ]);

            // Handle failed transfer logic here
            return ['success' => true, 'message' => 'Transfer failed logged'];

        } catch (\Exception $e) {
            Log::error('Paystack: Error handling transfer.failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle refund.processed event
     */
    public function handleRefundProcessed($data)
    {
        try {
            Log::info('Paystack: Processing refund.processed event', [
                'reference' => $data['transaction_reference'] ?? null,
                'amount' => $data['amount'] ?? null
            ]);

            // Handle refund logic here
            $reference = $data['transaction_reference'] ?? null;

            if ($reference) {
                $payment = \App\Models\Payment::where('reference_number', $reference)->first();

                if ($payment) {
                    $payment->status = 'refunded';
                    // Merge with existing metadata if any, otherwise set new
                    $existingMetadata = $payment->metadata ?? [];
                    $payment->metadata = array_merge($existingMetadata, $data);
                    $payment->notes = 'Payment refunded';
                    $payment->save();

                    Log::info('Paystack: Payment marked as refunded', ['payment_id' => $payment->id]);
                    return ['success' => true, 'payment_id' => $payment->id];
                }
            }

            return ['success' => false, 'message' => 'Payment record not found'];

        } catch (\Exception $e) {
            Log::error('Paystack: Error handling refund.processed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update invoice status based on payments
     */
    private function updateInvoiceStatus($invoiceId)
    {
        try {
            $invoice = \App\Models\Invoice::find($invoiceId);

            if (!$invoice) {
                return;
            }

            $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');

            if ($totalPaid >= $invoice->total_amount) {
                $invoice->status = 'paid';
            } elseif ($totalPaid > 0) {
                $invoice->status = 'partial';
            } else {
                $invoice->status = 'pending';
            }

            $invoice->save();

            Log::info('Paystack: Invoice status updated', [
                'invoice_id' => $invoiceId,
                'status' => $invoice->status
            ]);

        } catch (\Exception $e) {
            Log::error('Paystack: Error updating invoice status', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);
        }
    }
}

