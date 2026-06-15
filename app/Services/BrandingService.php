<?php

namespace App\Services;

use App\Models\BrandingSetting;
use Illuminate\Support\Facades\Cache;

class BrandingService
{
    /**
     * Get hospital branding with short-lived application cache.
     */
    public static function getBranding(): array
    {
        if (session()->has('hospital_branding')) {
            return session('hospital_branding');
        }

        $branding = Cache::remember('hospital_branding_data', 300, function () {
            BrandingSetting::repairStoredPaths();

            return BrandingSetting::first();
        });

        if (!$branding) {
            $branding = BrandingSetting::current();
        }

        $data = [
            'name' => $branding->platform_name ?? config('app.name', 'Hospital'),
            'logo' => $branding->logo_url ?? null,
            'logo_url' => $branding->logo_url ?? null,
            'logo_path' => $branding->getRawOriginal('logo_path'),
            'favicon' => $branding->favicon_url ?? null,
            'favicon_url' => $branding->favicon_url ?? null,
            'tagline' => $branding->business_name ?? 'Your Health, Our Priority',
            'address' => $branding->business_address ?? '',
            'phone' => $branding->business_phone ?? '',
            'email' => $branding->business_email ?? '',
            'website' => $branding->business_website ?? '',
            'primary_color' => $branding->primary_color ?? '#009ef7',
            'secondary_color' => $branding->secondary_color ?? '#f1f1f1',
            'accent_color' => $branding->accent_color ?? '#ffc700',
            'platform_name' => $branding->platform_name ?? config('app.name', 'Hospital'),
            'business_name' => $branding->business_name ?? '',
        ];

        session(['hospital_branding' => $data]);

        return $data;
    }

    /**
     * Clear branding cache (call when branding is updated).
     */
    public static function clearCache(): void
    {
        session()->forget('hospital_branding');
        Cache::forget('hospital_branding_data');
    }

    public static function getHospitalName(): string
    {
        return self::getBranding()['name'];
    }

    public static function getHospitalLogo(): ?string
    {
        return self::getBranding()['logo_url'];
    }

    public static function getPrimaryColor(): string
    {
        return self::getBranding()['primary_color'];
    }
}
