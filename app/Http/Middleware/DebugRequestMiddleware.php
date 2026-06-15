<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class DebugRequestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log request details
        Log::channel('daily')->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        Log::channel('daily')->info('🔵 INCOMING REQUEST', [
            'timestamp' => now()->toDateTimeString(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        // Log authentication info
        if (auth()->check()) {
            Log::channel('daily')->info('👤 AUTHENTICATED USER', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()->email,
                'user_name' => auth()->user()->name,
            ]);
        } else {
            Log::channel('daily')->warning('🔓 UNAUTHENTICATED REQUEST');
        }
        
        // Log request headers (excluding sensitive ones)
        $headers = collect($request->headers->all())
            ->except(['authorization', 'cookie', 'php-auth-pw'])
            ->toArray();
        Log::channel('daily')->info('📋 REQUEST HEADERS', $headers);
        
        // Log request body for POST/PUT/PATCH
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $data = $request->except(['password', 'password_confirmation', '_token']);
            Log::channel('daily')->info('📦 REQUEST BODY', $data);
            
            // Log CSRF token presence
            if ($request->has('_token')) {
                Log::channel('daily')->info('🔒 CSRF Token present: ' . substr($request->input('_token'), 0, 20) . '...');
            } else {
                Log::channel('daily')->error('❌ CSRF Token MISSING!');
            }
        }
        
        // Log session info
        Log::channel('daily')->info('📝 SESSION INFO', [
            'session_id' => session()->getId(),
            'session_driver' => config('session.driver'),
            'session_lifetime' => config('session.lifetime'),
        ]);
        
        // Process request
        $response = $next($request);
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        // Log response details
        $statusCode = $response->getStatusCode();
        $statusTexts = [
            200 => 'OK',
            201 => 'Created',
            302 => 'Found',
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            419 => 'Page Expired',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
        ];
        
        Log::channel('daily')->info('🟢 RESPONSE', [
            'status_code' => $statusCode,
            'status_text' => $statusTexts[$statusCode] ?? 'HTTP Status ' . $statusCode,
            'duration_ms' => $duration,
        ]);
        
        // Log redirects
        if ($response->isRedirect()) {
            Log::channel('daily')->info('↪️  REDIRECT TO: ' . $response->headers->get('Location'));
        }
        
        // Log errors
        if ($response->isClientError() || $response->isServerError()) {
            Log::channel('daily')->error('🔴 ERROR RESPONSE', [
                'status_code' => $response->getStatusCode(),
                'content' => substr($response->getContent(), 0, 500),
            ]);
        }
        
        Log::channel('daily')->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        return $response;
    }
}

