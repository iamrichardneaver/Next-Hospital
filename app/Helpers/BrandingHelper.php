<?php

namespace App\Helpers;

use App\Models\BrandingSetting;

class BrandingHelper
{
    /**
     * Get current branding settings (always fresh, no caching)
     */
    public static function getBranding()
    {
        // Force fresh query to avoid caching issues
        return BrandingSetting::first() ?? BrandingSetting::create([
            'platform_name' => config('app.name', 'Hospital'),
            'primary_color' => '#009ef7',
            'secondary_color' => '#f1f1f1',
            'accent_color' => '#ffc700'
        ]);
    }

    /**
     * Get platform name
     */
    public static function getPlatformName()
    {
        return static::getBranding()->platform_name ?? config('app.name', 'Hospital');
    }

    /**
     * Get business name
     */
    public static function getBusinessName()
    {
        return static::getBranding()->business_name ?? '';
    }

    /**
     * Get logo URL
     */
    public static function getLogoUrl()
    {
        $branding = static::getBranding();
        return $branding->logo_url ?? null;
    }

    /**
     * Get favicon URL
     */
    public static function getFaviconUrl()
    {
        $branding = static::getBranding();
        return $branding->favicon_url ?? null;
    }

    /**
     * Get primary color
     */
    public static function getPrimaryColor()
    {
        return static::getBranding()->primary_color ?? '#009ef7';
    }

    /**
     * Get secondary color
     */
    public static function getSecondaryColor()
    {
        return static::getBranding()->secondary_color ?? '#f1f1f1';
    }

    /**
     * Get accent color
     */
    public static function getAccentColor()
    {
        return static::getBranding()->accent_color ?? '#ffc700';
    }

    /**
     * Get business address
     */
    public static function getBusinessAddress()
    {
        return static::getBranding()->business_address ?? '';
    }

    /**
     * Get business phone
     */
    public static function getBusinessPhone()
    {
        return static::getBranding()->business_phone ?? '';
    }

    /**
     * Get business email
     */
    public static function getBusinessEmail()
    {
        return static::getBranding()->business_email ?? '';
    }

    /**
     * Get business website
     */
    public static function getBusinessWebsite()
    {
        return static::getBranding()->business_website ?? '';
    }

    /**
     * Check if logo exists
     */
    public static function hasLogo()
    {
        return !empty(static::getLogoUrl());
    }

    /**
     * Check if favicon exists
     */
    public static function hasFavicon()
    {
        return !empty(static::getFaviconUrl());
    }

    /**
     * Get CSS variables for branding
     */
    public static function getCssVariables()
    {
        return [
            '--primary-color' => static::getPrimaryColor(),
            '--secondary-color' => static::getSecondaryColor(),
            '--accent-color' => static::getAccentColor(),
        ];
    }

    /**
     * Branding payload for PDF/print templates (dompdf-safe logo paths).
     */
    public static function getPdfBrandingData(): array
    {
        return app(\App\Services\SettingsService::class)->getBrandingSettings();
    }

    /**
     * Tagline shown under hospital name (business name from branding settings).
     */
    public static function getTagline(): string
    {
        return static::getBusinessName();
    }

    /**
     * Generate CSS for branding
     */
    public static function generateCss()
    {
        $variables = static::getCssVariables();
        $css = '';
        
        foreach ($variables as $property => $value) {
            $css .= "{$property}: {$value};\n";
        }
        
        return $css;
    }
}
