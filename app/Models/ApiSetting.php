<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'frontend_api_url',
        'mobile_api_url',
        'websocket_url',
        'api_version',
        'api_timeout',
        'max_retry_attempts',
        'enable_api_caching',
        'api_cache_ttl',
        'enable_rate_limiting',
        'rate_limit_per_minute',
        'allowed_origins',
        'enable_api_logging'
    ];

    protected $casts = [
        'enable_api_caching' => 'boolean',
        'enable_rate_limiting' => 'boolean',
        'enable_api_logging' => 'boolean',
        'allowed_origins' => 'array'
    ];

    /**
     * Get current API settings.
     * Production canonical base: https://portal.omanyeclinic.com/api (configure in Admin → API Settings).
     */
    protected static function defaultApiBaseUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . '/api';
    }

    public static function current()
    {
        return static::first() ?? static::create([
            'frontend_api_url' => static::defaultApiBaseUrl(),
            'mobile_api_url' => static::defaultApiBaseUrl(),
            'websocket_url' => str_replace(['http://', 'https://'], ['ws://', 'wss://'], config('app.url')) . ':6001',
            'api_version' => 'v1',
            'api_timeout' => 30,
            'max_retry_attempts' => 3,
            'enable_api_caching' => true,
            'api_cache_ttl' => 300,
            'enable_rate_limiting' => true,
            'rate_limit_per_minute' => 60,
            'allowed_origins' => ['*'],
            'enable_api_logging' => true
        ]);
    }

    /**
     * Update API settings
     */
    public static function updateSettings($data)
    {
        $settings = static::current();
        $settings->update($data);
        return $settings;
    }

    /**
     * Get public API configuration for frontend/mobile
     */
    public static function getPublicConfig()
    {
        $settings = static::current();
        
        return [
            'frontend_api_url' => $settings->frontend_api_url,
            'mobile_api_url' => $settings->mobile_api_url,
            'websocket_url' => $settings->websocket_url,
            'api_version' => $settings->api_version,
            'api_timeout' => $settings->api_timeout,
            'max_retry_attempts' => $settings->max_retry_attempts,
            'enable_api_caching' => $settings->enable_api_caching,
            'api_cache_ttl' => $settings->api_cache_ttl,
            'enable_rate_limiting' => $settings->enable_rate_limiting,
            'rate_limit_per_minute' => $settings->rate_limit_per_minute,
            'allowed_origins' => $settings->allowed_origins,
            'enable_api_logging' => $settings->enable_api_logging
        ];
    }

    /**
     * Get frontend-specific API configuration
     */
    public static function getFrontendConfig()
    {
        $settings = static::current();
        
        return [
            'api_url' => $settings->frontend_api_url,
            'websocket_url' => $settings->websocket_url,
            'api_version' => $settings->api_version,
            'timeout' => $settings->api_timeout,
            'max_retry_attempts' => $settings->max_retry_attempts,
            'enable_caching' => $settings->enable_api_caching,
            'cache_ttl' => $settings->api_cache_ttl
        ];
    }

    /**
     * Public mobile API URL returned to apps after bootstrap.
     * Deployer: set Admin → Settings → API → mobile_api_url to your cloud domain, e.g.
     * https://your-hospital.com/api (must be reachable from patient devices on LAN/cellular).
     */
    public static function getMobileConfig()
    {
        $settings = static::current();
        $apiUrl = $settings->mobile_api_url;

        if (app()->environment('local') && !str_contains($apiUrl, 'localhost') && !str_contains($apiUrl, '10.0.2.2')) {
            $apiUrl = static::defaultApiBaseUrl();
        }

        return [
            'api_url' => $apiUrl,
            'websocket_url' => $settings->websocket_url,
            'api_version' => $settings->api_version,
            'timeout' => $settings->api_timeout,
            'max_retry_attempts' => $settings->max_retry_attempts,
            'enable_caching' => $settings->enable_api_caching,
            'cache_ttl' => $settings->api_cache_ttl
        ];
    }

    /**
     * Validate API URL format
     */
    public static function validateApiUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Get allowed origins as array
     */
    public function getAllowedOriginsArray()
    {
        if (is_string($this->allowed_origins)) {
            return json_decode($this->allowed_origins, true) ?? [];
        }
        
        return $this->allowed_origins ?? [];
    }
}
