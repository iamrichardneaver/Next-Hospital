<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'environment',
        'public_key',
        'secret_key',
        'merchant_id',
        'webhook_urls',
        'callback_url',
        'webhook_secret',
        'supported_currencies',
        'supported_payment_methods',
        'is_active'
    ];

    protected $casts = [
        'webhook_urls' => 'array',
        'supported_currencies' => 'array',
        'supported_payment_methods' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get current payment settings
     */
    public static function current()
    {
        return static::where('is_active', true)->first() ?? static::create([
            'provider' => 'hubtel',
            'environment' => 'sandbox',
            'supported_currencies' => ['GHS'],
            'is_active' => false
        ]);
    }

    /**
     * Update payment settings
     */
    public static function updateSettings($data)
    {
        // Deactivate current settings
        static::where('is_active', true)->update(['is_active' => false]);
        
        // Create new settings
        $settings = static::create(array_merge($data, ['is_active' => true]));
        
        return $settings;
    }

    /**
     * Get settings by provider
     */
    public static function getByProvider($provider)
    {
        return static::where('provider', $provider)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Check if provider is active
     */
    public static function isProviderActive($provider)
    {
        return static::where('provider', $provider)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Test payment configuration
     */
    public function testPayment($amount, $currency)
    {
        if (!$this->is_active) {
            throw new \Exception('Payment service is not active');
        }

        switch ($this->provider) {
            case 'hubtel':
                return $this->testHubtelPayment($amount, $currency);
            case 'paystack':
                return $this->testPaystackPayment($amount, $currency);
            default:
                throw new \Exception("Unsupported payment provider: {$this->provider}");
        }
    }

    /**
     * Test Hubtel payment
     */
    private function testHubtelPayment($amount, $currency)
    {
        // Mock test for Hubtel
        return [
            'success' => true,
            'message' => 'Hubtel payment test successful',
            'amount' => $amount,
            'currency' => $currency,
            'provider' => 'hubtel'
        ];
    }

    /**
     * Test Paystack payment
     */
    private function testPaystackPayment($amount, $currency)
    {
        // Mock test for Paystack
        return [
            'success' => true,
            'message' => 'Paystack payment test successful',
            'amount' => $amount,
            'currency' => $currency,
            'provider' => 'paystack'
        ];
    }

    /**
     * Get dynamic callback URL for Paystack
     */
    public function getCallbackUrl()
    {
        if ($this->callback_url) {
            return $this->callback_url;
        }
        
        return config('app.url') . '/api/paystack/callback';
    }

    /**
     * Get dynamic webhook URL for Paystack
     */
    public function getWebhookUrl()
    {
        return config('app.url') . '/api/paystack/webhook';
    }

    /**
     * Generate webhook secret if not exists
     */
    public function generateWebhookSecret()
    {
        if (!$this->webhook_secret) {
            $this->webhook_secret = bin2hex(random_bytes(32));
            $this->save();
        }
        
        return $this->webhook_secret;
    }

    /**
     * Get Paystack callback URL (static helper)
     */
    public static function getPaystackCallbackUrl()
    {
        $settings = static::getByProvider('paystack');
        return $settings ? $settings->getCallbackUrl() : config('app.url') . '/api/paystack/callback';
    }

    /**
     * Get Paystack webhook URL (static helper)
     */
    public static function getPaystackWebhookUrl()
    {
        $settings = static::getByProvider('paystack');
        return $settings ? $settings->getWebhookUrl() : config('app.url') . '/api/paystack/webhook';
    }
}
