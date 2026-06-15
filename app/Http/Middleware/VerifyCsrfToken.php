<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/realtime/*',
        'api/*',
        'debug/*',
        'api/realtime/module-data',
        'api/realtime/data-change-summary',
        'api/realtime/polling-interval',
        'api/realtime/invalidate-cache',
        'api/realtime/active-modules',
        'api/realtime/update-activity',
    ];
}
