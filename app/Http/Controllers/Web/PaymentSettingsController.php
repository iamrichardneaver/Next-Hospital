<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PaymentSettingsController extends Controller
{
    /**
     * Display payment settings page
     */
    public function index()
    {
        $paymentSettings = PaymentSetting::where('provider', 'paystack')->first();
        
        // If no settings exist, create default
        if (!$paymentSettings) {
            $paymentSettings = PaymentSetting::create([
                'provider' => 'paystack',
                'environment' => 'sandbox',
                'supported_currencies' => ['GHS'],
                'supported_payment_methods' => ['card', 'bank', 'mobile_money'],
                'is_active' => false
            ]);
        }

        // Generate dynamic URLs
        $webhookUrl = PaymentSetting::getPaystackWebhookUrl();
        $callbackUrl = PaymentSetting::getPaystackCallbackUrl();

        return view('settings.payment', compact('paymentSettings', 'webhookUrl', 'callbackUrl'));
    }

    /**
     * Update payment settings
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'environment' => 'required|in:sandbox,live',
            'public_key' => 'required|string|max:255',
            'secret_key' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
            'payment_methods' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please check your input and try again.');
        }

        try {
            // Deactivate all existing payment settings
            PaymentSetting::where('is_active', true)->update(['is_active' => false]);

            // Find or create Paystack settings
            $paymentSettings = PaymentSetting::where('provider', 'paystack')->first();

            if (!$paymentSettings) {
                $paymentSettings = new PaymentSetting();
                $paymentSettings->provider = 'paystack';
            }

            // Update settings
            $paymentSettings->environment = $request->environment;
            $paymentSettings->public_key = $request->public_key;
            $paymentSettings->secret_key = $request->secret_key;
            $paymentSettings->is_active = $request->boolean('is_active');
            $paymentSettings->supported_currencies = ['GHS']; // Ghana Cedis
            $paymentSettings->supported_payment_methods = $request->payment_methods ?? ['card', 'bank', 'mobile_money'];
            
            // Paystack uses secret_key for webhook validation, no separate webhook_secret needed
            $paymentSettings->webhook_secret = null;
            
            // Dynamic URLs are auto-generated via model methods, no need to store them
            $paymentSettings->save();

            Log::info('Payment settings updated', [
                'provider' => 'paystack',
                'environment' => $request->environment,
                'is_active' => $request->boolean('is_active'),
                'updated_by' => auth()->id()
            ]);

            return redirect()->route('settings.payment')
                ->with('success', 'Payment settings updated successfully! Paystack is now ' . 
                    ($request->boolean('is_active') ? 'ENABLED' : 'DISABLED') . ' in ' . 
                    strtoupper($request->environment) . ' mode.');

        } catch (\Exception $e) {
            Log::error('Payment settings update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update payment settings: ' . $e->getMessage());
        }
    }

    /**
     * Test Paystack connection
     */
    public function testConnection(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'public_key' => 'required|string',
                'secret_key' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Public Key and Secret Key are required'
                ], 422);
            }

            // Create temporary Paystack service with provided keys
            $paystackService = new \App\Services\PaystackService();
            
            // Try to fetch Paystack banks (simple API test)
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $request->secret_key,
                'Content-Type' => 'application/json',
            ])->get('https://api.paystack.co/bank');

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful! Your Paystack API keys are valid.',
                    'environment' => str_contains($request->public_key, 'pk_test') ? 'Test/Sandbox' : 'Live'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid API keys. Please check your Public Key and Secret Key.'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Paystack connection test failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ], 500);
        }
    }
}

