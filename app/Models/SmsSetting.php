<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'api_url',
        'api_key',
        'api_secret',
        'sender_id',
        'custom_headers',
        'request_body_template',
        'response_success_field',
        'is_active'
    ];

    protected $casts = [
        'custom_headers' => 'array',
        'request_body_template' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get current SMS settings
     */
    public static function current()
    {
        return static::where('is_active', true)->first() ?? static::create([
            'provider' => 'custom',
            'is_active' => false
        ]);
    }

    /**
     * Update SMS settings
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
     * Send SMS using configured provider
     */
    public function sendSms($phoneNumber, $message)
    {
        if (!$this->is_active) {
            throw new \Exception('SMS service is not active');
        }

        switch ($this->provider) {
            case 'custom':
                return $this->sendCustomSms($phoneNumber, $message);
            default:
                throw new \Exception("Unsupported SMS provider: {$this->provider}");
        }
    }

    /**
     * Send SMS using custom HTTP API
     */
    private function sendCustomSms($phoneNumber, $message)
    {
        $headers = array_merge([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $this->custom_headers ?? []);

        $body = $this->request_body_template ?? [
            'phone' => $phoneNumber,
            'message' => $message,
            'sender_id' => $this->sender_id
        ];

        // Replace placeholders in body
        $body = array_map(function($value) use ($phoneNumber, $message) {
            if (is_string($value)) {
                return str_replace(['{phone}', '{message}', '{sender_id}'], 
                    [$phoneNumber, $message, $this->sender_id], $value);
            }
            return $value;
        }, $body);

        $response = \Http::withHeaders($headers)
            ->post($this->api_url, $body);

        if (!$response->successful()) {
            throw new \Exception('SMS sending failed: ' . $response->body());
        }

        $responseData = $response->json();
        $successField = $this->response_success_field ?? 'success';
        
        if (!isset($responseData[$successField]) || !$responseData[$successField]) {
            throw new \Exception('SMS API returned error: ' . json_encode($responseData));
        }

        return $responseData;
    }
}
