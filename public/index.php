<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Fix REQUEST_URI ONLY for API routes (to handle subdirectory deployments)
// This ensures /api/* works whether accessed via /api/* or /backend/public/api/*
// Web routes and assets are NOT affected - they work with Laravel's built-in path handling
// Works dynamically for any deployment path (local, staging, production)
if (isset($_SERVER['REQUEST_URI'])) {
    $requestUri = $_SERVER['REQUEST_URI'];
    
    // ONLY process API routes - leave web routes and assets untouched
    // This prevents breaking CSS, JS, images, and web page routes
    if (preg_match('#(/api/.*|/api$)#', $requestUri, $matches)) {
        // Extract clean API path from REQUEST_URI
        // Handles: /nexthospital/api/test -> /api/test
        // Handles: /nexthospital/backend/public/api/test -> /api/test
        $_SERVER['REQUEST_URI'] = $matches[1];
        if (isset($_SERVER['PATH_INFO'])) {
            $_SERVER['PATH_INFO'] = $matches[1];
        }
    }
    // Also handle paths that come through backend/public rewrite for API only
    elseif (preg_match('#/backend/public(/api/.*|/api$)#', $requestUri, $matches)) {
        $_SERVER['REQUEST_URI'] = $matches[1];
        if (isset($_SERVER['PATH_INFO'])) {
            $_SERVER['PATH_INFO'] = $matches[1];
        }
    }
    // For all other paths (web routes, assets, storage), let Laravel handle them normally
    // Laravel's asset() and route() helpers will work correctly with the base path
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
