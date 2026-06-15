<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refresh User Permissions Middleware
 * 
 * This middleware ensures that the authenticated user's permissions are always fresh
 * by reloading the permission relationships on each request. This is critical for
 * ensuring that direct permission assignments take effect immediately without requiring
 * the user to logout and login again.
 * 
 * The middleware runs early in the request lifecycle to ensure permissions are fresh
 * before any views (like the sidebar) are rendered.
 */
class RefreshUserPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only refresh permissions for authenticated users
        if (auth()->check()) {
            $user = auth()->user();
            
            // CRITICAL: Only load relationships if they're not already loaded
            // This prevents N+1 queries and performance issues
            // We don't clear cache here - that's only done when permissions are updated
            // The relationships will be loaded once per request, not on every can() call
            if (!$user->relationLoaded('permissions')) {
                $user->loadMissing('permissions');
            }
            if (!$user->relationLoaded('roles')) {
                $user->loadMissing('roles.permissions');
            }
        }
        
        return $next($request);
    }
}
