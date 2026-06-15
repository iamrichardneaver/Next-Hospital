<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCrossPlatformFiles;

class MobileAppSetting extends Model
{
    use HasFactory, HasCrossPlatformFiles;

    protected $fillable = [
        'app_name',
        'app_short_name',
        'app_icon_path',
        'splash_screen_path',
        'app_logo_path',
        'package_name',
        'version',
        'app_description',
        'app_permissions',
        'enable_offline_mode',
        'enable_push_notifications',
        'enable_biometric_auth'
    ];

    protected $casts = [
        'app_permissions' => 'array',
        'enable_offline_mode' => 'boolean',
        'enable_push_notifications' => 'boolean',
        'enable_biometric_auth' => 'boolean',
    ];

    /**
     * Get current mobile app settings
     */
    public static function current()
    {
        return static::first() ?? static::create([
            'app_name' => config('app.name', 'Hospital App'),
            'app_short_name' => 'NextHosp',
            'package_name' => 'com.nexthospital.app',
            'version' => '1.0.0',
            'app_description' => 'Hospital Management System Mobile App',
            'app_permissions' => [
                'camera' => 'Camera access for patient photos',
                'storage' => 'Storage access for documents',
                'location' => 'Location access for emergency services',
                'notifications' => 'Push notifications for appointments'
            ],
            'enable_offline_mode' => true,
            'enable_push_notifications' => true,
            'enable_biometric_auth' => false
        ]);
    }

    /**
     * Update mobile app settings
     */
    public static function updateSettings($data)
    {
        $settings = static::current();
        $settings->update($data);
        return $settings;
    }

    /**
     * Get app icon URL
     */
    public function getAppIconUrlAttribute()
    {
        return $this->getFileUrl('app_icon_path');
    }

    /**
     * Get splash screen URL
     */
    public function getSplashScreenUrlAttribute()
    {
        return $this->getFileUrl('splash_screen_path');
    }

    /**
     * Get app logo URL
     */
    public function getAppLogoUrlAttribute()
    {
        return $this->getFileUrl('app_logo_path');
    }

    /**
     * Get app icon path
     */
    public function getAppIconPathAttribute()
    {
        $path = $this->getRawOriginal('app_icon_path');
        
        if (!$path) {
            return null;
        }

        return app(\App\Services\CrossPlatformService::class)->getStoragePath($path);
    }

    /**
     * Get splash screen path
     */
    public function getSplashScreenPathAttribute()
    {
        $path = $this->getRawOriginal('splash_screen_path');
        
        if (!$path) {
            return null;
        }

        return app(\App\Services\CrossPlatformService::class)->getStoragePath($path);
    }

    /**
     * Get app logo path
     */
    public function getAppLogoPathAttribute()
    {
        $path = $this->getRawOriginal('app_logo_path');
        
        if (!$path) {
            return null;
        }

        return app(\App\Services\CrossPlatformService::class)->getStoragePath($path);
    }

    /**
     * Generate app configuration JSON for mobile
     */
    public function toMobileConfig()
    {
        return [
            'app_name' => $this->app_name,
            'app_short_name' => $this->app_short_name,
            'package_name' => $this->package_name,
            'version' => $this->version,
            'description' => $this->app_description,
            'permissions' => $this->app_permissions,
            'features' => [
                'offline_mode' => $this->enable_offline_mode,
                'push_notifications' => $this->enable_push_notifications,
                'biometric_auth' => $this->enable_biometric_auth
            ],
            'assets' => [
                'app_icon' => $this->app_icon_url,
                'splash_screen' => $this->splash_screen_url,
                'app_logo' => $this->app_logo_url
            ]
        ];
    }
}
