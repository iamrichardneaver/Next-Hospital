<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform',
        'version_code',
        'version_name',
        'build_number',
        'is_force_update',
        'min_supported_version',
        'download_url',
        'play_store_url',
        'app_store_url',
        'release_notes',
        'is_active',
    ];

    protected $casts = [
        'is_force_update' => 'boolean',
        'is_active' => 'boolean',
        'version_code' => 'integer',
        'build_number' => 'integer',
        'min_supported_version' => 'integer',
    ];

    /**
     * Get the latest active version for a specific platform
     */
    public static function getLatestVersion(string $platform = 'both')
    {
        return static::where('is_active', true)
            ->where(function ($query) use ($platform) {
                $query->where('platform', $platform)
                      ->orWhere('platform', 'both');
            })
            ->orderBy('version_code', 'desc')
            ->first();
    }

    /**
     * Get the latest active version for Android
     */
    public static function getLatestAndroidVersion()
    {
        return static::getLatestVersion('android');
    }

    /**
     * Get the latest active version for iOS
     */
    public static function getLatestIOSVersion()
    {
        return static::getLatestVersion('ios');
    }

    /**
     * Check if an update is required for a given version
     */
    public static function checkUpdateRequired(string $platform, int $currentVersionCode)
    {
        $latestVersion = static::getLatestVersion($platform);
        
        if (!$latestVersion) {
            return [
                'requires_update' => false,
                'force_update' => false,
                'latest_version' => null,
            ];
        }

        $requiresUpdate = $currentVersionCode < $latestVersion->version_code;
        $forceUpdate = $requiresUpdate && (
            $latestVersion->is_force_update || 
            ($latestVersion->min_supported_version && $currentVersionCode < $latestVersion->min_supported_version)
        );

        return [
            'requires_update' => $requiresUpdate,
            'force_update' => $forceUpdate,
            'latest_version' => $latestVersion,
        ];
    }

    /**
     * Get the appropriate download/store URL based on platform
     */
    public function getStoreUrl()
    {
        switch ($this->platform) {
            case 'android':
                return $this->download_url ?? $this->play_store_url;
            case 'ios':
                return $this->app_store_url;
            case 'both':
                // Return both URLs as an array
                return [
                    'android' => $this->download_url ?? $this->play_store_url,
                    'ios' => $this->app_store_url,
                ];
            default:
                return null;
        }
    }

    /**
     * Scope to get only active versions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get versions for a specific platform
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where(function ($q) use ($platform) {
            $q->where('platform', $platform)
              ->orWhere('platform', 'both');
        });
    }
}
