<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            // If it's an API request, return JSON
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Authentication required',
                    'error' => 'Unauthenticated'
                ], 401);
            }
            
            // For web requests, redirect to login
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        $user = auth()->user();
        
        // CRITICAL: Only load relationships if not already loaded
        // RefreshUserPermissions middleware already loads these, so we avoid redundant queries
        if (!$user->relationLoaded('permissions')) {
            $user->loadMissing('permissions');
        }
        if (!$user->relationLoaded('roles')) {
            $user->loadMissing('roles.permissions');
        }
        
        // Support pipe- or comma-separated permissions (OR logic): "permission1|permission2"
        // Some API routes use commas; web routes use pipes — both mean "any of these".
        $permissions = preg_split('/[|,]/', $permission);
        $hasPermission = false;
        
        foreach ($permissions as $perm) {
            $perm = trim($perm);
            if ($user->can($perm)) {
                $hasPermission = true;
                break;
            }
        }
        
        if (!$hasPermission) {
            // Log permission denial only when explicitly enabled (avoids verbose logs in production)
            if (config('app.debug') && filter_var(env('LOG_PERMISSION_CHECKS', false), FILTER_VALIDATE_BOOLEAN)) {
                \Log::debug('Permission denied', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'required_permission' => $permission,
                    'checked_permissions' => $permissions,
                    'user_roles' => $user->roles->pluck('name')->toArray(),
                    'role_permissions' => $user->getPermissionsViaRoles()->pluck('name')->toArray(),
                    'direct_permissions' => $user->getDirectPermissions()->pluck('name')->toArray(),
                    'all_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                ]);
            }
            
            // If it's an API request, return JSON
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Insufficient permissions',
                    'error' => 'Forbidden',
                    'required_permission' => $permission
                ], 403);
            }
            
            // For web requests, show friendly 403 error page
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
