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

// Force HTTPS detection early in production environment
if (getenv('APP_ENV') === 'production' || getenv('FORCE_HTTPS') === 'true') {
    // Check for proxy headers indicating HTTPS
    $httpsHeaders = [
        $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '',
        $_SERVER['HTTP_X_FORWARDED_SSL'] ?? '',
        $_SERVER['HTTPS'] ?? ''
    ];
    
    if (in_array('https', $httpsHeaders) || in_array('on', $httpsHeaders)) {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = 443;
        $_SERVER['REQUEST_SCHEME'] = 'https';
    }
    
    // Force all URLs to use HTTPS
    $_ENV['APP_URL'] = str_replace('http://', 'https://', $_ENV['APP_URL'] ?? '');
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
