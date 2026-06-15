<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Cross-platform bootstrap safeguards
|--------------------------------------------------------------------------
|
| Windows/XAMPP bootstrap/cache/*.php or .env overrides must never define
| paths on a Linux server. Strip poisoned env keys and delete stale cache
| files before Laravel resolves PackageManifest paths.
|
*/
$foreignPathPattern = '/[Cc]:[\\\\\\/]|[\\\\\\/]xampp[\\\\\\/]|\\/Applications\\/XAMPP\\//';

foreach (['APP_BASE_PATH', 'APP_PACKAGES_CACHE', 'APP_SERVICES_CACHE', 'APP_CONFIG_CACHE', 'APP_ROUTES_CACHE', 'COMPOSER_VENDOR_DIR'] as $envKey) {
    foreach ([&$_ENV, &$_SERVER] as $bag) {
        if (! isset($bag[$envKey])) {
            continue;
        }

        if (preg_match($foreignPathPattern, (string) $bag[$envKey])) {
            unset($bag[$envKey]);
        }
    }
}

$bootstrapCacheDir = __DIR__.'/cache';

if (is_dir($bootstrapCacheDir)) {
    foreach (glob($bootstrapCacheDir.'/*.php') ?: [] as $cacheFile) {
        $contents = @file_get_contents($cacheFile);

        if ($contents !== false && preg_match($foreignPathPattern, $contents)) {
            @unlink($cacheFile);
        }
    }
}

$basePath = realpath(dirname(__DIR__)) ?: dirname(__DIR__);

return Application::configure(basePath: $basePath)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES', '*'),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->alias([
            'api.token' => \App\Http\Middleware\EnsureApiToken::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'apply.settings' => \App\Http\Middleware\ApplySettings::class,
            'scope.by.branch' => \App\Http\Middleware\ScopeByBranch::class,
            'debug.request' => \App\Http\Middleware\DebugRequestMiddleware::class,
        ]);
        
        // CRITICAL: Lightweight middleware to ensure permissions are loaded once per request
        // This prevents N+1 queries when can() is called multiple times (e.g., in sidebar)
        // Only loads if not already loaded (e.g., from login eager loading)
        $middleware->web(prepend: [
            \App\Http\Middleware\RefreshUserPermissions::class,
        ]);
        
        // Apply branch scoping to all web routes
        $middleware->web(append: [
            \App\Http\Middleware\ScopeByBranch::class,
        ]);
        
        // Debug middleware temporarily disabled - using controller logging instead
        // The middleware was causing 500 errors
        // if (app()->environment('local')) {
        //     $middleware->web(append: [
        //         \App\Http\Middleware\DebugRequestMiddleware::class,
        //     ]);
        // }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 419) {
                return redirect()->back()->with('error', 'Your session has expired. Please try again.');
            }
        });
    })->create();
