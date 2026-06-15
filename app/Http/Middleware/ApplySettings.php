<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\SettingsService;
use Symfony\Component\HttpFoundation\Response;

class ApplySettings
{
    protected $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Apply all settings to the application
            $this->settingsService->applyAllSettings();
        } catch (\Exception $e) {
            // Log error but don't break the request
            \Log::error('Failed to apply settings: ' . $e->getMessage());
        }

        return $next($request);
    }
}
