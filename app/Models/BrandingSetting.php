<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCrossPlatformFiles;
use App\Services\BrandingService;
use App\Services\CrossPlatformService;

class BrandingSetting extends Model
{
    use HasFactory, HasCrossPlatformFiles;

    protected static array $filePathAttributes = ['logo_path', 'favicon_path', 'mobile_logo_path'];

    protected $fillable = [
        'platform_name',
        'business_name',
        'business_address',
        'business_phone',
        'business_email',
        'business_website',
        'logo_path',
        'favicon_path',
        'mobile_logo_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'custom_css'
    ];

    /**
     * Get the current branding settings (singleton)
     */
    public static function current()
    {
        return static::first() ?? static::create([
            'platform_name' => config('app.name', 'Hospital'),
            'primary_color' => '#009ef7',
            'secondary_color' => '#f1f1f1',
            'accent_color' => '#ffc700'
        ]);
    }

    /**
     * Update branding settings
     */
    public static function updateSettings($data)
    {
        $settings = static::current();
        $settings->update(static::normalizeFilePathsInData($data));
        BrandingService::clearCache();

        return $settings->fresh();
    }

    /**
     * Normalize legacy absolute paths in the database record.
     */
    public static function repairStoredPaths(): void
    {
        $settings = static::first();
        if (!$settings) {
            return;
        }

        $updates = [];
        foreach (static::$filePathAttributes as $attribute) {
            $raw = $settings->getRawOriginal($attribute);
            $normalized = CrossPlatformService::normalizeStorageRelativePath($raw);
            if ($raw && $normalized && $raw !== $normalized) {
                $updates[$attribute] = $normalized;
            }
        }

        if (!empty($updates)) {
            $settings->updateQuietly($updates);
            BrandingService::clearCache();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeFilePathsInData(array $data): array
    {
        foreach (static::$filePathAttributes as $attribute) {
            if (!array_key_exists($attribute, $data) || $data[$attribute] === null || $data[$attribute] === '') {
                continue;
            }

            $data[$attribute] = CrossPlatformService::normalizeStorageRelativePath((string) $data[$attribute])
                ?? $data[$attribute];
        }

        return $data;
    }

    public function setLogoPathAttribute($value): void
    {
        $this->attributes['logo_path'] = CrossPlatformService::normalizeStorageRelativePath($value);
    }

    public function setFaviconPathAttribute($value): void
    {
        $this->attributes['favicon_path'] = CrossPlatformService::normalizeStorageRelativePath($value);
    }

    public function setMobileLogoPathAttribute($value): void
    {
        $this->attributes['mobile_logo_path'] = CrossPlatformService::normalizeStorageRelativePath($value);
    }

    /**
     * Get logo URL with cache busting
     */
    public function getLogoUrlAttribute()
    {
        $url = $this->getFileUrl('logo_path');
        
        // Add timestamp for cache busting
        if ($url && $this->updated_at) {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . 'v=' . $this->updated_at->timestamp;
        }
        
        return $url;
    }

    /**
     * Get favicon URL with cache busting
     */
    public function getFaviconUrlAttribute()
    {
        $url = $this->getFileUrl('favicon_path');
        
        // Add timestamp for cache busting
        if ($url && $this->updated_at) {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . 'v=' . $this->updated_at->timestamp;
        }
        
        return $url;
    }

    /**
     * Get mobile logo URL with cache busting
     */
    public function getMobileLogoUrlAttribute()
    {
        $url = $this->getFileUrl('mobile_logo_path');
        
        // Add timestamp for cache busting
        if ($url && $this->updated_at) {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . 'v=' . $this->updated_at->timestamp;
        }
        
        return $url;
    }

    /**
     * Absolute filesystem path for logo (not exposed as logo_path in arrays).
     */
    public function getLogoAbsolutePathAttribute()
    {
        $path = $this->getFilePath('logo_path');

        return ($path && file_exists($path)) ? $path : null;
    }

    /**
     * Get base64 encoded logo for PDF embedding (cross-platform)
     */
    public function getLogoBase64Attribute()
    {
        $absolutePath = $this->logo_absolute_path;
        
        if (!$absolutePath || !file_exists($absolutePath)) {
            return null;
        }

        $imageData = file_get_contents($absolutePath);
        $base64 = base64_encode($imageData);
        
        // Determine MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $absolutePath);
        finfo_close($finfo);
        
        return 'data:' . $mimeType . ';base64,' . $base64;
    }
}
