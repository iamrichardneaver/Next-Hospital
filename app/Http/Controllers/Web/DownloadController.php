<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BrandingSetting;
use App\Models\MobileAppSetting;
use Illuminate\Support\Facades\File;

class DownloadController extends Controller
{
    public function index()
    {
        $branding = BrandingSetting::current();
        $mobileApp = MobileAppSetting::current();
        
        // Check if APK file exists in public folder
        $apkPath = public_path('nexthospital-app.apk');
        $apkExists = File::exists($apkPath);
        
        // Force check - sometimes File::exists doesn't work as expected
        if (!$apkExists) {
            $apkExists = file_exists($apkPath);
        }
        
        // Debug: Log the status
        \Log::info('APK Check', [
            'path' => $apkPath,
            'exists' => $apkExists,
            'file_size' => $apkExists ? File::size($apkPath) : 0
        ]);
        
        // Get APK file size if it exists
        $apkSize = $apkExists ? round(File::size($apkPath) / 1048576, 1) . 'MB' : null;
        
        return view('download.index', [
            'branding' => $branding,
            'mobileApp' => $mobileApp,
            'apkExists' => $apkExists,
            'apkSize' => $apkSize,
            'platformName' => $branding->platform_name ?? config('app.name', 'Hospital'),
            'businessName' => $branding->business_name ?? config('app.name', 'Hospital'),
            'businessEmail' => $branding->business_email ?? 'support@nexthospital.com',
            'businessPhone' => $branding->business_phone ?? '',
            'businessWebsite' => $branding->business_website ?? '',
            'logoUrl' => $branding->logo_url,
            'primaryColor' => $branding->primary_color ?? '#3b82f6',
            'secondaryColor' => $branding->secondary_color ?? '#1e3a8a',
            'accentColor' => $branding->accent_color ?? '#60a5fa',
            'appVersion' => $mobileApp->version ?? '1.0.0',
            'appDescription' => $mobileApp->app_description ?? 'Experience seamless healthcare management with ' . ($branding->platform_name ?? config('app.name', 'Hospital')),
        ]);
    }
}

