<?php

namespace App\Traits;

use App\Services\CrossPlatformService;

trait PdfBrandingTrait
{
    /**
     * Dompdf-safe logo source (base64 preferred, then absolute filesystem path).
     */
    protected function extractLogoPath($branding)
    {
        $src = CrossPlatformService::resolvePdfLogoSrc($branding);

        if ($src && str_starts_with($src, 'data:')) {
            return $src;
        }

        if (!empty($branding['logo_path'])) {
            $fullPath = storage_path('app/public/' . ltrim($branding['logo_path'], '/'));
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return $src;
    }

    /**
     * Prepare complete branding data for PDF views
     */
    protected function preparePdfBranding($additionalData = [])
    {
        $settingsService = new \App\Services\SettingsService();
        $branding = $settingsService->getBrandingSettings();
        $logoPath = $this->extractLogoPath($branding);

        return array_merge([
            'branding' => $branding,
            'logo_full_path' => $logoPath,
            'hospital_name' => $branding['platform_name'] ?? $branding['business_name'] ?? config('app.name', 'Hospital'),
            'primary_color' => $branding['primary_color'] ?? '#2c5aa0',
        ], $additionalData);
    }

    /**
     * Get optimized PDF page margins (reduced from 20mm to 10mm)
     */
    protected function getOptimizedMargins()
    {
        return '10mm'; // 50% more usable space
    }
}

