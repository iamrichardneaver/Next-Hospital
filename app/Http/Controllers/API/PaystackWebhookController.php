<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    /**
     * Handle Paystack webhook events
     */
    public function handleWebhook(Request $request)
    {
        try {
            // Get the raw POST data
            $payload = $request->getContent();
            
            // Get Paystack signature from header
            $signature = $request->header('X-Paystack-Signature');

            if (!$signature) {
                Log::error('Paystack Webhook: Missing signature header');
                return response()->json([
                    'success' => false,
                    'message' => 'Missing signature'
                ], 400);
            }

            // Validate webhook signature
            $paystackService = new PaystackService();
            
            if (!$paystackService->validateWebhookSignature($payload, $signature)) {
                Log::error('Paystack Webhook: Invalid signature');
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature'
                ], 401);
            }

            // Parse webhook data
            $data = json_decode($payload, true);
            $event = $data['event'] ?? null;
            $eventData = $data['data'] ?? [];

            Log::info('Paystack Webhook: Event received', [
                'event' => $event,
                'reference' => $eventData['reference'] ?? null
            ]);

            // Handle different webhook events
            $result = null;
            
            switch ($event) {
                case 'charge.success':
                    $result = $paystackService->handleChargeSuccess($eventData);
                    break;

                case 'charge.failed':
                    $result = $paystackService->handleChargeFailed($eventData);
                    break;

                case 'transfer.success':
                    $result = $paystackService->handleTransferSuccess($eventData);
                    break;

                case 'transfer.failed':
                    $result = $paystackService->handleTransferFailed($eventData);
                    break;

                case 'refund.processed':
                    $result = $paystackService->handleRefundProcessed($eventData);
                    break;

                default:
                    Log::info('Paystack Webhook: Unhandled event type', ['event' => $event]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Event received but not processed'
                    ]);
            }

            // Log the result
            Log::info('Paystack Webhook: Event processed', [
                'event' => $event,
                'result' => $result
            ]);

            // Always return 200 to acknowledge receipt
            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'event' => $event
            ]);

        } catch (\Exception $e) {
            Log::error('Paystack Webhook: Error processing webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Still return 200 to prevent Paystack from retrying
            return response()->json([
                'success' => false,
                'message' => 'Webhook received but processing failed'
            ], 200);
        }
    }

    /**
     * Handle Paystack callback (for web payments)
     */
    public function handleCallback(Request $request)
    {
        try {
            $reference = $request->query('reference');
            $trxref = $request->query('trxref');
            $appointmentId = $request->query('appointment_id');
            
            // Use trxref if reference is not provided
            if (!$reference && $trxref) {
                $reference = $trxref;
            }

            Log::info('Paystack Callback: Received', [
                'reference' => $reference,
                'appointment_id' => $appointmentId,
                'query_params' => $request->query()
            ]);

            if (!$reference) {
                // If appointment_id is present, redirect to appointment processing
                if ($appointmentId) {
                    return redirect()->route('appointments.process-payment', ['reference' => $reference, 'appointment_id' => $appointmentId])
                        ->with('error', 'Payment verification failed: Missing reference');
                }
                return redirect()->route('payments.index')->with('error', 'Payment verification failed: Missing reference');
            }

            // If appointment_id is in query params, redirect to appointment-specific payment processing
            if ($appointmentId) {
                return redirect()->route('appointments.process-payment', ['reference' => $reference, 'appointment_id' => $appointmentId]);
            }

            // Check if payment metadata contains appointment_id
            $payment = \App\Models\Payment::where('reference_number', $reference)->first();
            if ($payment && $payment->metadata) {
                // Metadata is automatically cast as array by Payment model
                $metadata = $payment->metadata;
                $metadataAppointmentId = $metadata['appointment_id'] ?? $metadata['reference_id'] ?? null;
                
                if ($metadataAppointmentId && isset($metadata['payment_type']) && $metadata['payment_type'] === 'appointment') {
                    return redirect()->route('appointments.process-payment', ['reference' => $reference, 'appointment_id' => $metadataAppointmentId]);
                }
            }

            // Verify the transaction
            $paystackService = new PaystackService();
            $verification = $paystackService->verifyTransaction($reference);

            if (!$verification['success']) {
                Log::error('Paystack Callback: Verification failed', ['reference' => $reference]);
                return redirect()->route('payments.index')->with('error', 'Payment verification failed');
            }

            $transactionData = $verification['data'];
            $status = $transactionData['status'] ?? null;

            if ($status === 'success') {
                // Payment successful
                Log::info('Paystack Callback: Payment successful', ['reference' => $reference]);
                
                // Find and update payment record
                if ($payment && $payment->status !== 'completed') {
                    $payment->status = 'completed';
                    $payment->transaction_id = $transactionData['id'] ?? null;
                    $payment->source_platform = 'webhook'; // TAG: Webhook payment
                    $payment->notes = ($payment->notes ?? '') . ' | Verified via Paystack webhook';
                    $payment->save();

                    // Update invoice status
                    if ($payment->invoice_id) {
                        $invoice = \App\Models\Invoice::find($payment->invoice_id);
                        if ($invoice) {
                            $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');
                            if ($totalPaid >= $invoice->total_amount) {
                                $invoice->status = 'paid';
                            } elseif ($totalPaid > 0) {
                                $invoice->status = 'partial';
                            }
                            $invoice->save();
                        }
                    }
                }

                return redirect()->route('payments.index')->with('success', 'Payment completed successfully!');
            } else {
                // Payment failed or pending
                Log::warning('Paystack Callback: Payment not successful', [
                    'reference' => $reference,
                    'status' => $status
                ]);
                
                return redirect()->route('payments.index')->with('error', 'Payment was not successful. Status: ' . $status);
            }

        } catch (\Exception $e) {
            Log::error('Paystack Callback: Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Try to redirect to appointment processing if appointment_id is available
            $appointmentId = $request->query('appointment_id');
            if ($appointmentId) {
                return redirect()->route('appointments.process-payment', ['reference' => $request->query('reference'), 'appointment_id' => $appointmentId])
                    ->with('error', 'Payment processing error: ' . $e->getMessage());
            }

            return redirect()->route('payments.index')->with('error', 'Payment processing error: ' . $e->getMessage());
        }
    }
}

